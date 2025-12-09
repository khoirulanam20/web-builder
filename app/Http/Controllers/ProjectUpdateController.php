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
            'ai_provider' => ['nullable', 'string', 'in:openrouter,google_gemini'],
        ]);

        // Set default AI provider jika tidak dipilih
        if (empty($data['ai_provider'])) {
            $data['ai_provider'] = 'openrouter';
        }

        try {
            $basePath = "projects/{$project->id}";
            $htmlPath = "{$basePath}/index.html";
            
            // Cek apakah file HTML ada
            if (!Storage::disk('local')->exists($htmlPath)) {
                throw new \RuntimeException('File HTML tidak ditemukan. Silakan generate website terlebih dahulu.');
            }
            
            // Ambil HTML yang sudah ada
            $existingHtml = Storage::disk('local')->get($htmlPath);
            
            if (empty($existingHtml) || trim($existingHtml) === '') {
                throw new \RuntimeException('File HTML kosong. Silakan generate website terlebih dahulu.');
            }

            // Cek panjang HTML (jika terlalu panjang, mungkin melebihi token limit)
            $htmlLength = strlen($existingHtml);
            $maxRecommendedLength = 200000; // ~200KB, estimasi aman untuk token limit
            
            if ($htmlLength > $maxRecommendedLength) {
                \Log::warning('HTML terlalu panjang untuk improve', [
                    'project_id' => $project->id,
                    'html_length' => $htmlLength,
                    'max_recommended' => $maxRecommendedLength,
                ]);
                // Tetap lanjutkan, tapi log warning
            }

            \Log::info('Starting improve website', [
                'project_id' => $project->id,
                'html_length' => $htmlLength,
                'improve_prompt_length' => strlen($data['improve_prompt']),
                'ai_provider' => $data['ai_provider'],
            ]);

            // Improve website dengan hanya mengubah bagian spesifik
            $result = $ai->improveWebsite($existingHtml, $data['improve_prompt'], [
                'ai_provider' => $data['ai_provider'],
            ]);

            // Validasi hasil
            if (empty($result['html']) || trim($result['html']) === '') {
                throw new \RuntimeException('AI mengembalikan HTML kosong. Silakan coba lagi dengan instruksi yang lebih spesifik.');
            }

            // Update HTML dengan hasil improve
            Storage::disk('local')->put($htmlPath, $result['html']);

            \Log::info('Improve website success', [
                'project_id' => $project->id,
                'new_html_length' => strlen($result['html']),
            ]);

            // Jangan update prompt di database karena ini hanya improve, bukan generate ulang
            // Prompt original tetap sama

            return redirect()
                ->route('projects.show', $project)
                ->with('success', 'Website berhasil diperbaiki sesuai permintaan.');
        } catch (\RuntimeException $e) {
            \Log::warning('Improve website failed (RuntimeException)', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('projects.show', $project)
                ->with('error', $e->getMessage())
                ->withErrors(['improve_prompt' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::error('Improve website failed (Exception)', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Terjadi kesalahan saat memperbaiki website. ';
            if (str_contains($e->getMessage(), 'API') || str_contains($e->getMessage(), 'API key')) {
                $errorMessage .= $e->getMessage();
            } else {
                $errorMessage .= 'Silakan coba lagi atau periksa log untuk detail lebih lanjut.';
            }

            return redirect()
                ->route('projects.show', $project)
                ->with('error', $errorMessage)
                ->withErrors(['improve_prompt' => $errorMessage]);
        }
    }

    protected function authorizeProject(Project $project): void
    {
        abort_unless($project->user_id === Auth::id(), 403);
    }
}

