<?php

namespace App\Http\Controllers;

use App\Models\GeneratedFile;
use App\Models\Project;
use App\Services\AIGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GenerateController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Projects/Create');
    }

    public function importCode(): Response
    {
        return Inertia::render('Projects/ImportCode');
    }

    public function jsonPrompt(): Response
    {
        return Inertia::render('Projects/JsonPrompt');
    }

    public function store(Request $request, AIGeneratorService $ai): RedirectResponse
    {
        $data = $request->validate([
            'ai_provider' => ['nullable', 'string', 'in:openrouter,google_gemini'],
            'prompt' => ['nullable', 'string', 'max:5000'],
            'website_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'style_tone' => ['nullable', 'string', 'max:50'],
            'icon_library' => ['nullable', 'string', 'max:50'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'reference_image' => ['nullable', 'image', 'max:5120'], // Max 5MB
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'in:navbar,hero,about,services,features,portfolio,testimonials,pricing,team,gallery,faq,blog,contact,footer'],
        ]);

        // Set default AI provider jika tidak dipilih
        if (empty($data['ai_provider'])) {
            $data['ai_provider'] = 'openrouter';
        }

        // Set default sections jika tidak ada
        if (empty($data['sections']) || !is_array($data['sections'])) {
            $data['sections'] = ['navbar', 'hero', 'about', 'services', 'contact', 'footer'];
        }

        // Validasi: minimal harus ada prompt atau salah satu field lain
        if (empty($data['prompt']) && 
            empty($data['website_name']) && 
            empty($data['description'])) {
            return back()
                ->withErrors([
                    'prompt' => 'Minimal isi prompt atau nama website dan deskripsi.',
                ])
                ->with('error', 'Minimal isi prompt atau nama website dan deskripsi.')
                ->withInput();
        }

        $user = Auth::user();

        $projectId = (string) Str::uuid();

        // Handle reference image upload dan convert ke base64 untuk Vision API
        $referenceImageBase64 = null;
        $referenceImageMimeType = null;
        if ($request->hasFile('reference_image')) {
            $image = $request->file('reference_image');
            $referenceImagePath = $image->store("projects/{$projectId}/reference", 'local');
            $data['reference_image_path'] = $referenceImagePath;
            
            // Convert gambar ke base64 untuk Vision API
            $imageContent = Storage::disk('local')->get($referenceImagePath);
            $referenceImageBase64 = base64_encode($imageContent);
            $referenceImageMimeType = $image->getMimeType();
            $data['reference_image_base64'] = $referenceImageBase64;
            $data['reference_image_mime_type'] = $referenceImageMimeType;
        }

        // Build prompt dari form data
        $prompt = $this->buildPromptFromFormData($data);

        $project = Project::create([
            'id' => $projectId,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'slug' => Str::slug(Str::limit($prompt, 40, '')) . '-' . Str::random(6),
            'status' => 'draft',
        ]);

        try {
            // Pass all form data including ai_provider to the service
            $result = $ai->generateFromPrompt($prompt, $data);
        } catch (\Exception $e) {
            \Log::error('AI Generation Failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            $project->delete();

            return back()
                ->with('error', 'Gagal generate website: ' . $e->getMessage())
                ->withInput();
        }

        $basePath = "projects/{$project->id}";

        // Simpan HTML
        Storage::disk('local')->put("{$basePath}/index.html", $result['html'] ?? '');

        GeneratedFile::create([
            'project_id' => $project->id,
            'type' => 'html',
            'path' => "{$basePath}/index.html",
        ]);

        // Copy Tailwind CSS file ke project
        $this->copyTailwindCSS($basePath, $project->id);

        if (! empty($result['css'])) {
            Storage::disk('local')->put("{$basePath}/style.css", $result['css']);
            GeneratedFile::create([
                'project_id' => $project->id,
                'type' => 'css',
                'path' => "{$basePath}/style.css",
            ]);
        }

        // JavaScript sudah termasuk di dalam HTML, tidak perlu disimpan terpisah

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Website berhasil digenerate');
    }

    public function storeFromCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'website_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon_library' => ['nullable', 'string', 'max:50'],
            'html_code' => ['nullable', 'string'],
            'css_code' => ['nullable', 'string'],
            'js_code' => ['nullable', 'string'],
        ]);

        if (
            empty($data['html_code']) &&
            empty($data['css_code']) &&
            empty($data['js_code'])
        ) {
            return back()
                ->withErrors([
                    'html_code' => 'Minimal isi salah satu dari HTML, CSS, atau JavaScript.',
                ])
                ->withInput();
        }

        $user = Auth::user();
        $projectId = (string) Str::uuid();

        $promptData = [
            'website_name' => $data['website_name'] ?? null,
            'description' => $data['description'] ?? null,
            'icon_library' => $data['icon_library'] ?? null,
            'prompt' => 'Project dibuat dari kode manual (HTML/CSS/JS) yang diimport oleh user.',
        ];

        $prompt = $this->buildPromptFromFormData($promptData);

        $project = Project::create([
            'id' => $projectId,
            'user_id' => $user->id,
            'prompt' => $prompt,
            'slug' => Str::slug(Str::limit($prompt, 40, '')) . '-' . Str::random(6),
            'status' => 'draft',
        ]);

        $basePath = "projects/{$project->id}";

        $html = $data['html_code'] ?? '';
        $css = $data['css_code'] ?? '';
        $js = $data['js_code'] ?? '';

        $hasFullHtml = str_contains($html, '<html');

        if ($hasFullHtml) {
            if (!empty($css)) {
                if (str_contains($html, '</head>')) {
                    $html = str_replace(
                        '</head>',
                        "<style>\n{$css}\n</style>\n</head>",
                        $html
                    );
                } else {
                    $html = "<style>\n{$css}\n</style>\n" . $html;
                }
            }

            if (!empty($js)) {
                if (str_contains($html, '</body>')) {
                    $html = str_replace(
                        '</body>',
                        "<script>\n{$js}\n</script>\n</body>",
                        $html
                    );
                } else {
                    $html .= "\n<script>\n{$js}\n</script>";
                }
            }
        } else {
            $styleTag = !empty($css) ? "<style>\n{$css}\n</style>\n" : '';
            $scriptTag = !empty($js) ? "<script>\n{$js}\n</script>\n" : '';

            $bodyContent = $html ?: '<!-- Kode HTML belum diisi, hanya CSS/JS yang tersedia -->';

            $html = "<!DOCTYPE html>\n"
                . "<html lang=\"id\">\n"
                . "<head>\n"
                . "<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
                . $styleTag
                . "</head>\n"
                . "<body>\n"
                . $bodyContent . "\n"
                . $scriptTag
                . "</body>\n"
                . "</html>";
        }

        Storage::disk('local')->put("{$basePath}/index.html", $html);

        GeneratedFile::create([
            'project_id' => $project->id,
            'type' => 'html',
            'path' => "{$basePath}/index.html",
        ]);

        $this->copyTailwindCSS($basePath, $project->id);

        if (!empty($css)) {
            Storage::disk('local')->put("{$basePath}/style.css", $css);

            GeneratedFile::create([
                'project_id' => $project->id,
                'type' => 'css',
                'path' => "{$basePath}/style.css",
            ]);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Website dari kode berhasil disimpan.');
    }

    /**
     * Copy Tailwind CSS file ke project directory
     * Untuk download ZIP, kita akan download Tailwind CSS dari CDN yang menyediakan file CSS
     */
    private function copyTailwindCSS(string $basePath, string $projectId): void
    {
        // Download Tailwind CSS dari CDN yang menyediakan file CSS
        $tailwindSource = storage_path('app/tailwind-standalone.css');
        
        if (! file_exists($tailwindSource) || filesize($tailwindSource) < 1000) {
            $tailwindContent = $this->downloadTailwindCSSFile();
            Storage::disk('local')->put('tailwind-standalone.css', $tailwindContent);
        }

        // Copy ke project directory
        $tailwindContent = Storage::disk('local')->get('tailwind-standalone.css');
        Storage::disk('local')->put("{$basePath}/tailwind.css", $tailwindContent);

        GeneratedFile::create([
            'project_id' => $projectId,
            'type' => 'css',
            'path' => "{$basePath}/tailwind.css",
        ]);
    }

    /**
     * Download Tailwind CSS file dari CDN
     */
    private function downloadTailwindCSSFile(): string
    {
        // Coba download dari berbagai sumber CDN yang menyediakan Tailwind CSS file
        $urls = [
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css',
            'https://unpkg.com/tailwindcss@3.4.1/dist/tailwind.min.css',
        ];

        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (compatible; AI Web Generator)',
            ],
        ]);

        foreach ($urls as $url) {
            $content = @file_get_contents($url, false, $context);
            if ($content !== false && strlen($content) > 1000) {
                return $content;
            }
        }

        // Fallback: return placeholder CSS dengan instruksi
        return <<<'CSS'
/* Tailwind CSS - Please install Tailwind CSS for production */
/* For full Tailwind CSS, run: npm install -D tailwindcss && npx tailwindcss init */
/* Or use CDN script tag: <script src="https://cdn.tailwindcss.com"></script> */
CSS;
    }

    /**
     * Build prompt dari form data
     */
    private function buildPromptFromFormData(array $data): string
    {
        $parts = [];

        if (! empty($data['website_name'])) {
            $parts[] = "Nama website/brand: {$data['website_name']}";
        }

        if (! empty($data['description'])) {
            $parts[] = "Deskripsi: {$data['description']}";
        }

        if (! empty($data['target_audience'])) {
            $parts[] = "Target audiens: {$data['target_audience']}";
        }

        if (! empty($data['style_tone'])) {
            $styleMap = [
                'modern' => 'modern dan minimalis',
                'professional' => 'profesional dan formal',
                'casual' => 'casual dan friendly',
                'creative' => 'creative dan bold',
                'elegant' => 'elegant dan luxurious',
                'tech' => 'tech dan futuristic',
            ];
            $style = $styleMap[$data['style_tone']] ?? $data['style_tone'];
            $parts[] = "Style dan tone: {$style}";
        }

        if (! empty($data['icon_library'])) {
            $iconMap = [
                'fontawesome' => 'Font Awesome',
                'heroicons' => 'Heroicons',
                'phosphor' => 'Phosphor Icons',
                'lucide' => 'Lucide Icons',
            ];
            $iconLabel = $iconMap[$data['icon_library']] ?? $data['icon_library'];
            $parts[] = "Icon library utama: {$iconLabel}";
        }

        if (! empty($data['primary_color']) || ! empty($data['secondary_color']) || ! empty($data['accent_color'])) {
            $colors = [];
            if (! empty($data['primary_color'])) {
                $colors[] = "Primary: {$data['primary_color']}";
            }
            if (! empty($data['secondary_color'])) {
                $colors[] = "Secondary: {$data['secondary_color']}";
            }
            if (! empty($data['accent_color'])) {
                $colors[] = "Accent: {$data['accent_color']}";
            }
            $parts[] = 'Color palette: ' . implode(', ', $colors);
        }

        if (! empty($data['sections']) && is_array($data['sections'])) {
            $sectionLabels = [
                'navbar' => 'Navbar',
                'hero' => 'Hero Section',
                'about' => 'About Us',
                'services' => 'Services',
                'features' => 'Features',
                'portfolio' => 'Portfolio',
                'testimonials' => 'Testimonials',
                'pricing' => 'Pricing',
                'team' => 'Team',
                'gallery' => 'Gallery',
                'faq' => 'FAQ',
                'blog' => 'Blog',
                'contact' => 'Contact',
                'footer' => 'Footer',
            ];
            $sectionNames = array_map(function($section) use ($sectionLabels) {
                return $sectionLabels[$section] ?? $section;
            }, $data['sections']);
            $parts[] = 'Section yang harus dibuat (dalam urutan ini): ' . implode(' → ', $sectionNames);
        }

        if (! empty($data['prompt'])) {
            $parts[] = "Detail tambahan: {$data['prompt']}";
        }

        return implode('. ', $parts);
    }
}
