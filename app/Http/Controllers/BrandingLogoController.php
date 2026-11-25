<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandingLogoController extends Controller
{
    /**
     * Publicly serves the configured organization logo without relying on
     * the optional public/storage symlink. Only uploaded branding images are
     * eligible for delivery.
     */
    public function show(): StreamedResponse
    {
        $setting = SystemSetting::query()
            ->where('key', 'organization_logo_path')
            ->first();

        $path = data_get($setting?->value, 'value');

        abort_unless(
            is_string($path)
                && preg_match('#\Abranding/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp)\z#i', $path) === 1,
            404
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true), 404);

        return $disk->response($path, basename($path), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ], 'inline');
    }
}
