<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AIGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProjectUpdateController extends Controller
{
    public function updateCode(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
        ]);

        $basePath = "projects/{$project->id}";

        if (isset($data['html'])) {
            Storage::disk('local')->put("{$basePath}/index.html", $data['html']);
        }

        if (isset($data['css'])) {
            Storage::disk('local')->put("{$basePath}/style.css", $data['css']);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Kode berhasil disimpan.');
    }

    public function improve(Request $request, Project $project, AIGeneratorService $ai): RedirectResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'improve_prompt' => ['required', 'string', 'max:2000'],
        ]);

        try {
            // Ambil prompt original dan tambahkan improve prompt
            $improvedPrompt = $project->prompt . "\n\nImprovement Request: " . $data['improve_prompt'];

            // Generate ulang dengan prompt yang diperbaiki
            $result = $ai->generateFromPrompt($improvedPrompt, []);

            $basePath = "projects/{$project->id}";

            // Update HTML (JavaScript sudah termasuk di dalam HTML)
            Storage::disk('local')->put("{$basePath}/index.html", $result['html'] ?? '');

            // Update prompt di database
            $project->update([
                'prompt' => $improvedPrompt,
            ]);

            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'Website berhasil diperbaiki.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('projects.show', $project)
                ->with('error', $e->getMessage())
                ->withErrors(['improve_prompt' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::error('Improve website failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('projects.show', $project)
                ->with('error', 'Terjadi kesalahan saat memperbaiki website. Silakan coba lagi.')
                ->withErrors(['improve_prompt' => 'Terjadi kesalahan saat memperbaiki website. Silakan coba lagi.']);
        }
    }

    protected function authorizeProject(Project $project): void
    {
        abort_unless($project->user_id === Auth::id(), 403);
    }
}

