<?php
namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private function definitionFor(Document $document): ?array
    {
        return collect(config('inventory.resources'))->first(fn ($definition) => $document->documentable_type === $definition['model']);
    }

    private function authorizeDocument(Request $request, Document $document, string $action = 'view'): array
    {
        $definition = $this->definitionFor($document);
        abort_unless($definition && $request->user()->hasPermission($definition['permission'].'.'.$action), 403);
        return $definition;
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('documents.view'), 403);
        $allowedTypes = collect(config('inventory.resources'))
            ->filter(fn ($definition) => $request->user()->hasPermission($definition['permission'].'.view'))
            ->pluck('model')->values()->all();
        abort_if(empty($allowedTypes), 403);

        $query = Document::with(['uploader', 'documentable'])
            ->whereIn('documentable_type', $allowedTypes);
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('title', 'like', '%'.$term.'%')
                    ->orWhere('original_name', 'like', '%'.$term.'%')
                    ->orWhere('type', 'like', '%'.$term.'%');
            });
        }
        if ($request->filled('type')) $query->where('type', $request->query('type'));
        $documents = $query->latest()->paginate(25)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    private function target(string $resource, int $id)
    {
        $definition = config("inventory.resources.$resource");
        abort_unless($definition && method_exists($definition['model'], 'documents'), 404);
        abort_unless(request()->user()->hasPermission($definition['permission'].'.view'), 403);
        return $definition['model']::findOrFail($id);
    }

    public function store(Request $request, string $resource, int $id)
    {
        $target = $this->target($resource, $id);
        $definition = config("inventory.resources.$resource");
        abort_unless($request->user()->hasPermission($definition['permission'].'.manage'), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt'],
        ]);
        $file = $data['file'];
        $documentType = $data['type'] ?: 'other';
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs("documents/$resource/$id", $filename, 'local');

        try {
            $document = DB::transaction(function () use ($target, $data, $file, $path, $documentType, $request) {
                // Serialize version allocation on the parent record to avoid duplicate version numbers.
                $lockedTarget = $target->newQuery()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
                $latestVersion = $lockedTarget->documents()
                    ->where('title', $data['title'])
                    ->where('type', $documentType)
                    ->max('version');

                return $lockedTarget->documents()->create([
                    'title' => $data['title'],
                    'type' => $documentType,
                    'version' => ((int) $latestVersion) + 1,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $request->user()->id,
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        Audit::record('document_uploaded', $resource, Document::class, $document->id, [], ['title' => $document->title, 'type' => $document->type, 'version' => $document->version]);
        return back()->with('success', 'Document uploaded as version '.$document->version.'. Previous versions were retained.');
    }

    public function preview(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document, 'view');
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        $mime = strtolower((string) $document->mime_type);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 415, 'Preview is available for PDF and image files only.');
        Audit::record('document_previewed', 'documents', Document::class, $document->id);

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_name ?: basename($document->path)).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document, 'view');
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        Audit::record('document_downloaded', 'documents', Document::class, $document->id);
        return Storage::disk('local')->download($document->path, $document->original_name ?: basename($document->path));
    }

    public function destroy(Request $request, Document $document)
    {
        $this->authorizeDocument($request, $document, 'manage');
        if ($document->type === 'asset_photo' && $document->documentable instanceof \App\Models\Asset) {
            app(\App\Services\AssetHistoryWriter::class)->record(
                $document->documentable,
                'gallery_image_removed',
                ['document_id' => $document->id, 'file_name' => $document->original_name],
                []
            );
        }
        Storage::disk('local')->delete($document->path);
        $id = $document->id;
        $document->delete();
        Audit::record('document_deleted', 'documents', Document::class, $id);
        return back()->with('success', 'Document version removed.');
    }
}
