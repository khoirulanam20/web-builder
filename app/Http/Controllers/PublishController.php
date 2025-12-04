<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class PublishController extends Controller
{
    public function store(Project $project): RedirectResponse
    {
        abort_unless($project->user_id === Auth::id(), 403);

        $slug = $project->slug;
        $sourceDir = "projects/{$project->id}";

        // Direktori target di dalam public agar bisa diakses langsung via APP_URL/sites/{slug}
        $publicTargetDir = public_path("sites/{$slug}");

        if (! Storage::disk('local')->exists("{$sourceDir}/index.html")) {
            return back()
                ->with('error', 'File index.html belum tersedia. Pastikan project sudah di-generate dengan benar.');
        }

        // Pastikan direktori publish di dalam public/sites bersih
        if (File::exists($publicTargetDir)) {
            File::deleteDirectory($publicTargetDir);
        }
        File::makeDirectory($publicTargetDir, 0755, true);

        foreach (['index.html', 'style.css', 'script.js'] as $file) {
            if (Storage::disk('local')->exists("{$sourceDir}/{$file}")) {
                File::put(
                    $publicTargetDir . DIRECTORY_SEPARATOR . $file,
                    Storage::disk('local')->get("{$sourceDir}/{$file}")
                );
            }
        }

        // Generate URL menggunakan domain bawaan aplikasi:
        // APP_URL + /sites/{slug}/index.html
        $publishedUrl = url("sites/{$slug}/index.html");

        $project->update([
            'status' => 'published',
            'preview_url' => $publishedUrl,
        ]);

        return back()->with('success', 'Project berhasil dipublish.');
    }
}


