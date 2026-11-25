<?php
namespace App\Http\Controllers;

use App\Models\BackupRecord;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\DatabaseBackupService;

class BackupController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasPermission('backups.manage'),403);
        $backups=BackupRecord::with('creator')->latest('created_at')->paginate(20);
        return view('admin.backups',compact('backups'));
    }
    public function runbook(Request $request)
    {
        abort_unless($request->user()->hasPermission('backups.manage'), 403);
        $path = base_path('docs/BACKUP_RESTORE.md');
        abort_unless(is_file($path), 404);
        return response()->download($path, 'IT-Inventory-Backup-Restore-Runbook.md', ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function create(Request $request, DatabaseBackupService $backups)
    {
        abort_unless($request->user()->hasPermission('backups.manage'),403);
        try {
            $record = $backups->create($request->user()->id);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
        Audit::record('database_backup_created','backups',BackupRecord::class,$record->id);
        return back()->with('success','Database backup created successfully.');
    }
    public function download(Request $request, BackupRecord $backup)
    {
        abort_unless($request->user()->hasPermission('backups.manage'),403);
        abort_unless(Storage::disk('local')->exists($backup->disk_path),404);
        Audit::record('database_backup_downloaded','backups',BackupRecord::class,$backup->id);
        return Storage::disk('local')->download($backup->disk_path,$backup->filename);
    }
    public function destroy(Request $request, BackupRecord $backup)
    {
        abort_unless($request->user()->hasPermission('backups.manage'),403);
        Storage::disk('local')->delete($backup->disk_path);
        $id=$backup->id; $backup->delete();
        Audit::record('database_backup_deleted','backups',BackupRecord::class,$id);
        return back()->with('success','Backup file deleted.');
    }
}
