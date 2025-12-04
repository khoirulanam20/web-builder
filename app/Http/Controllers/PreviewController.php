<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreviewController extends Controller
{
    public function show(Project $project)
    {
        $path = "projects/{$project->id}/index.html";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $html = Storage::disk('local')->get($path);
        $basePath = "projects/{$project->id}";

        // Pastikan Tailwind CDN script ada di HTML untuk preview
        if (stripos($html, 'cdn.tailwindcss.com') === false && stripos($html, '<head>') !== false) {
            $tailwindScript = '<script src="https://cdn.tailwindcss.com"></script>';
            $html = preg_replace(
                '/(<\/head>)/i',
                "    {$tailwindScript}\n$1",
                $html,
                1
            );
        }

        // JavaScript sudah termasuk di dalam HTML, tidak perlu di-inject terpisah

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
