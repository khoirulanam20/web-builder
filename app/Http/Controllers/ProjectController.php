<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::where('user_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project): Response
    {
        $this->authorizeProject($project);

        $basePath = "projects/{$project->id}";
        
        $html = Storage::disk('local')->exists("{$basePath}/index.html")
            ? Storage::disk('local')->get("{$basePath}/index.html")
            : null;
        
        $css = Storage::disk('local')->exists("{$basePath}/tailwind.css")
            ? Storage::disk('local')->get("{$basePath}/tailwind.css")
            : (Storage::disk('local')->exists("{$basePath}/style.css")
                ? Storage::disk('local')->get("{$basePath}/style.css")
                : null);
        
        // JavaScript sudah termasuk di dalam HTML, tidak perlu di-pass terpisah

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'html' => $html,
            'css' => $css,
        ]);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        Storage::disk('local')->deleteDirectory("projects/{$project->id}");

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project dihapus.');
    }

    protected function authorizeProject(Project $project): void
    {
        abort_unless($project->user_id === Auth::id(), 403);
    }
}
