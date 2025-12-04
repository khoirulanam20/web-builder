<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DownloadController extends Controller
{
    public function show(Project $project)
    {
        abort_unless($project->user_id === Auth::id(), 403);

        $sourceDir = "projects/{$project->id}";

        if (! Storage::disk('local')->exists("{$sourceDir}/index.html")) {
            abort(404, 'File belum lengkap.');
        }

        $zipFileName = "{$project->slug}.zip";
        $tempPath = storage_path('app/tmp_'.uniqid().'.zip');

        $zip = new ZipArchive();
        
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Gagal membuat file ZIP.');
        }

        // Tambahkan file HTML (dengan Tailwind CDN diganti ke file lokal)
        if (Storage::disk('local')->exists("{$sourceDir}/index.html")) {
            $htmlContent = Storage::disk('local')->get("{$sourceDir}/index.html");
            
            // Replace Tailwind CDN script dengan link ke file lokal
            $htmlContent = preg_replace(
                '/<script[^>]*src=["\']https?:\/\/cdn\.tailwindcss\.com[^"\']*["\'][^>]*><\/script>/i',
                '<link rel="stylesheet" href="tailwind.css">',
                $htmlContent
            );
            
            $zip->addFromString('index.html', $htmlContent);
        }

        // Tambahkan file Tailwind CSS
        if (Storage::disk('local')->exists("{$sourceDir}/tailwind.css")) {
            $cssContent = Storage::disk('local')->get("{$sourceDir}/tailwind.css");
            $zip->addFromString('tailwind.css', $cssContent);
        } else {
            // Jika tidak ada, download Tailwind CSS dari CDN
            $tailwindCSS = $this->downloadTailwindCSS();
            $zip->addFromString('tailwind.css', $tailwindCSS);
        }

        // Tambahkan file CSS tambahan jika ada
        if (Storage::disk('local')->exists("{$sourceDir}/style.css")) {
            $cssContent = Storage::disk('local')->get("{$sourceDir}/style.css");
            $zip->addFromString('style.css', $cssContent);
        }

        // Tambahkan file JS jika ada
        if (Storage::disk('local')->exists("{$sourceDir}/script.js")) {
            $jsContent = Storage::disk('local')->get("{$sourceDir}/script.js");
            $zip->addFromString('script.js', $jsContent);
        }

        $zip->close();

        return response()->download($tempPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download Tailwind CSS untuk production
     */
    private function downloadTailwindCSS(): string
    {
        // Coba download dari Tailwind Play CDN atau gunakan fallback
        $urls = [
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css',
            'https://unpkg.com/tailwindcss@3.4.1/dist/tailwind.min.css',
        ];

        foreach ($urls as $url) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0',
                ],
            ]);
            
            $content = @file_get_contents($url, false, $context);
            if ($content !== false && strlen($content) > 1000) {
                return $content;
            }
        }

        // Fallback: return minimal CSS dengan Tailwind CDN script tag
        return <<<'CSS'
/* Tailwind CSS - Please use Tailwind CDN for full functionality */
/* Add this to your HTML: <script src="https://cdn.tailwindcss.com"></script> */
/* Or install Tailwind CSS: npm install -D tailwindcss && npx tailwindcss init */
CSS;
    }
}


