<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIGeneratorService
{
    /**
     * Generate website HTML based on prompt and settings.
     * 
     * @param string $prompt User prompt
     * @param array $formData Form data including 'ai_provider' (openrouter|google_gemini)
     */
    public function generateFromPrompt(string $prompt, array $formData = []): array
    {
        $provider = $formData['ai_provider'] ?? 'openrouter';
        
        if ($provider === 'google_gemini') {
            return $this->generateWithGoogleGemini($prompt, $formData);
        }
        
        return $this->generateWithOpenRouter($prompt, $formData);
    }

    /**
     * Generate using OpenRouter API
     */
    private function generateWithOpenRouter(string $prompt, array $formData = []): array
    {
        $apiKey = config('services.openrouter.key');
        $baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1/chat/completions');
        $referer = config('services.openrouter.referer', config('app.url'));
        $title = config('services.openrouter.title', 'AI Web Generator');
        // Gunakan model yang pintar coding. Rekomendasi: anthropic/claude-3.5-sonnet, openai/gpt-4o, atau google/gemini-pro-1.5
        $model = config('services.openrouter.model', 'anthropic/claude-3.5-sonnet'); 

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENROUTER_API_KEY tidak ditemukan di file .env. Silakan tambahkan API key Anda dari https://openrouter.ai/keys');
        }

        // Validasi format API key (harus dimulai dengan sk-)
        if (!str_starts_with($apiKey, 'sk-') && !str_starts_with($apiKey, 'sk-or-')) {
            Log::warning('OpenRouter API Key format mungkin tidak valid', [
                'key_prefix' => substr($apiKey, 0, 10) . '...',
            ]);
        }

        // 1. Build The System Prompt
        $systemPrompt = $this->buildSystemPrompt($formData);

        // 2. Build The User Prompt (Contextualized)
        $userPrompt = $this->buildUserPrompt($prompt, $formData);

        // 3. Estimasi token dan batasi panjang prompt
        // Rough estimate: 1 token ≈ 4 characters untuk bahasa Inggris, lebih untuk bahasa lain
        // Model limit biasanya 65535 tokens total (input + output)
        // Kita batasi: input maksimal 45000 tokens, output maksimal 16000 tokens
        
        $estimatedSystemTokens = intval(strlen($systemPrompt) / 3.5); // Lebih konservatif
        $estimatedUserTokens = intval(strlen($userPrompt) / 3.5);
        $totalInputTokens = $estimatedSystemTokens + $estimatedUserTokens;
        
        // Jika total input terlalu panjang, potong user prompt
        $maxInputTokens = 45000; // Safe limit untuk input
        if ($totalInputTokens > $maxInputTokens) {
            $maxUserPromptLength = intval(($maxInputTokens - $estimatedSystemTokens) * 3.5);
            if ($maxUserPromptLength > 1000) { // Minimal 1000 chars untuk user prompt
                $originalUserPrompt = $userPrompt;
                $userPrompt = substr($userPrompt, 0, $maxUserPromptLength) . "\n\n[Catatan: Prompt dipotong karena terlalu panjang. Silakan gunakan prompt yang lebih singkat untuk hasil optimal.]";
                Log::warning('User prompt terlalu panjang, dipotong', [
                    'original_length' => strlen($originalUserPrompt),
                    'truncated_length' => strlen($userPrompt),
                    'estimated_tokens' => $totalInputTokens,
                ]);
            }
        }

        // Tentukan max_tokens berdasarkan model
        // Amazon Nova memiliki limit 65535 total tokens
        // Claude 3.5 Sonnet memiliki limit 8192 output tokens
        $maxOutputTokens = 16000; // Default untuk model umum
        if (str_contains(strtolower($model), 'nova')) {
            $maxOutputTokens = 15000; // Lebih konservatif untuk Nova
        } elseif (str_contains(strtolower($model), 'claude')) {
            $maxOutputTokens = 8000; // Claude limit
        } elseif (str_contains(strtolower($model), 'gpt-4')) {
            $maxOutputTokens = 8000; // GPT-4 limit
        }

        // Prepare messages - include image if available for vision-capable models
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        
        // Jika ada gambar referensi, coba gunakan format vision untuk model yang mendukung
        if (!empty($formData['reference_image_base64']) && !empty($formData['reference_image_mime_type'])) {
            // Cek apakah model mendukung vision (GPT-4 Vision, Claude dengan vision, dll)
            $visionModels = ['gpt-4-vision', 'gpt-4o', 'claude-3', 'claude-3.5'];
            $supportsVision = false;
            foreach ($visionModels as $visionModel) {
                if (str_contains(strtolower($model), strtolower($visionModel))) {
                    $supportsVision = true;
                    break;
                }
            }
            
            if ($supportsVision) {
                // Format untuk vision-capable models (OpenAI/Anthropic format)
                $imageUrl = 'data:' . $formData['reference_image_mime_type'] . ';base64,' . $formData['reference_image_base64'];
                
                $imageAnalysisPrompt = "\n\nANALISA GAMBAR REFERENSI:\nGambar referensi telah disertakan. Analisa gambar ini secara detail dan gunakan sebagai referensi untuk:\n- Layout dan struktur halaman (grid, spacing, alignment)\n- Skema warna dan palet (extract warna dominan dari gambar)\n- Gaya visual dan estetika (modern, minimalis, bold, dll)\n- Komposisi elemen (card design, button style, typography)\n- Mood dan tone desain (professional, playful, elegant, dll)\n\nPastikan website yang dihasilkan mencerminkan gaya visual dari gambar referensi ini.";
                
                $messages[] = [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $userPrompt . $imageAnalysisPrompt
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl
                            ]
                        ]
                    ]
                ];
                
                Log::info('OpenRouter Vision API - Image included', [
                    'model' => $model,
                    'mime_type' => $formData['reference_image_mime_type'],
                ]);
            } else {
                // Model tidak mendukung vision, gunakan text prompt saja
                $referenceImageNote = "\n\nREFERENCE IMAGE:\nUser telah mengupload gambar referensi untuk desain. Gunakan gambar referensi ini sebagai inspirasi untuk layout, warna, gaya visual, dan komposisi elemen.";
                $messages[] = ['role' => 'user', 'content' => $userPrompt . $referenceImageNote];
            }
        } else {
            // Tidak ada gambar, gunakan prompt biasa
            $messages[] = ['role' => 'user', 'content' => $userPrompt];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => $referer,
                'X-Title' => $title,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($baseUrl, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7, // Kreatif tapi terstruktur
                'max_tokens' => $maxOutputTokens,
            ]);

            if (!$response->successful()) {
                $this->handleApiError($response);
            }

            // Log full response untuk debugging
            $responseBody = $response->json();
            Log::info('OpenRouter API Response', [
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            // Cek berbagai kemungkinan struktur response
            $content = $response->json('choices.0.message.content');
            
            // Fallback: coba struktur alternatif
            if (empty($content)) {
                $content = $response->json('choices.0.text') 
                    ?? $response->json('data.0.text')
                    ?? $response->json('content');
            }
            
            if (empty($content)) {
                // Log response lengkap untuk debugging
                Log::error('AI returned empty response', [
                    'response_status' => $response->status(),
                    'response_body' => $responseBody,
                    'response_text' => $response->body(),
                ]);
                
                $errorMsg = 'AI service mengembalikan response kosong. ';
                if (isset($responseBody['error'])) {
                    $errorMsg .= 'Error dari API: ' . ($responseBody['error']['message'] ?? json_encode($responseBody['error']));
                } else {
                    $errorMsg .= 'Silakan coba lagi dalam beberapa saat atau periksa koneksi internet Anda.';
                }
                
                throw new \RuntimeException($errorMsg);
            }

            // 3. Post-Processing
            $content = $this->cleanOutput($content);
            $content = $this->injectDependencies($content);
            
            // JANGAN extract JavaScript - biarkan tetap di HTML
            // JavaScript akan tetap di dalam HTML untuk kemudahan editing

            return [
                'html' => $content,
                'css' => null, // Tailwind handle CSS
                'js' => null,  // JavaScript sudah di dalam HTML
            ];

        } catch (\Exception $e) {
            Log::error('OpenRouter AI Generation Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate using Google Gemini API
     */
    private function generateWithGoogleGemini(string $prompt, array $formData = []): array
    {
        $apiKey = config('services.google_gemini.key');
        $baseUrl = config('services.google_gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        
        if (empty($apiKey)) {
            throw new \RuntimeException('GOOGLE_GEMINI_API_KEY tidak ditemukan di file .env. Silakan tambahkan API key Anda dari https://makersuite.google.com/app/apikey');
        }

        // Get model from config
        $requestedModel = config('services.google_gemini.model', 'gemini-2.5-flash');
        
        // Model yang tersedia untuk v1beta (urutkan dari yang paling direkomendasikan/terbaru)
        // Catatan: gemini-pro dan gemini-1.5-flash sudah deprecated
        $availableModels = [
            'gemini-2.5-flash',      // Model flash generasi baru (lebih cepat & murah)
            'gemini-2.0-flash',      // Model flash alternatif
            'gemini-1.5-pro-latest', // Model pro yang lebih stabil
            'gemini-1.5-pro',        // Model pro alternatif
        ];
        
        // Jika model yang diminta tidak dalam daftar, tambahkan ke awal untuk dicoba dulu
        if (!in_array($requestedModel, $availableModels)) {
            array_unshift($availableModels, $requestedModel);
        } else {
            // Pindahkan model yang diminta ke posisi pertama
            $key = array_search($requestedModel, $availableModels);
            if ($key !== false) {
                unset($availableModels[$key]);
                array_unshift($availableModels, $requestedModel);
            }
        }

        // 1. Build The System Prompt
        $systemPrompt = $this->buildSystemPrompt($formData);

        // 2. Build The User Prompt (Contextualized)
        $userPrompt = $this->buildUserPrompt($prompt, $formData);

        // Combine system and user prompt for Gemini (Gemini doesn't have separate system role)
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

        // Prepare content parts - include image if available
        $contentParts = [
            ['text' => $fullPrompt]
        ];
        
        // Jika ada gambar referensi, tambahkan ke content parts untuk Vision API
        if (!empty($formData['reference_image_base64']) && !empty($formData['reference_image_mime_type'])) {
            $contentParts[] = [
                'inline_data' => [
                    'mime_type' => $formData['reference_image_mime_type'],
                    'data' => $formData['reference_image_base64']
                ]
            ];
            
            // Tambahkan instruksi khusus untuk analisa gambar
            $imageAnalysisPrompt = "\n\nANALISA GAMBAR REFERENSI:\nGambar referensi telah disertakan di atas. Analisa gambar ini secara detail dan gunakan sebagai referensi untuk:\n- Layout dan struktur halaman (grid, spacing, alignment)\n- Skema warna dan palet (extract warna dominan dari gambar)\n- Gaya visual dan estetika (modern, minimalis, bold, dll)\n- Komposisi elemen (card design, button style, typography)\n- Mood dan tone desain (professional, playful, elegant, dll)\n\nPastikan website yang dihasilkan mencerminkan gaya visual dari gambar referensi ini.";
            $contentParts[0]['text'] = $fullPrompt . $imageAnalysisPrompt;
            
            Log::info('Google Gemini Vision API - Image included', [
                'mime_type' => $formData['reference_image_mime_type'],
                'image_size' => strlen($formData['reference_image_base64']) . ' bytes (base64)',
            ]);
        }

        // Try to generate with available models (urutkan dari yang diminta user, lalu fallback)
        $lastError = null;
        $response = null;
        $successfulModel = null;
        
        foreach ($availableModels as $tryModel) {
            // Gemini API endpoint
            // Format: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={apiKey}
            $url = "{$baseUrl}/models/{$tryModel}:generateContent?key={$apiKey}";
            
            // Log untuk debugging
            Log::info('Google Gemini API Request', [
                'url' => str_replace($apiKey, '***', $url),
                'model' => $tryModel,
                'has_image' => !empty($formData['reference_image_base64']),
            ]);

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(120)->post($url, [
                    'contents' => [
                        [
                            'parts' => $contentParts
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 32768, // Increase lebih tinggi untuk memastikan HTML lengkap
                    ],
                ]);

                if ($response->successful()) {
                    $successfulModel = $tryModel; // Update model yang berhasil
                    break; // Success, exit loop
                }
                
                // Jika error 400/404 tentang model not found, coba model berikutnya
                if ($response->status() === 400 || $response->status() === 404) {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? '';
                    if (str_contains(strtolower($errorMsg), 'not found') || str_contains(strtolower($errorMsg), 'not supported')) {
                        $lastError = $errorMsg;
                        Log::warning("Model {$tryModel} tidak tersedia, mencoba model berikutnya", [
                            'error' => $errorMsg,
                        ]);
                        continue; // Try next model
                    }
                }
                
                // Jika bukan error model not found, throw error
                $this->handleGeminiApiError($response);
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                // Continue to next model
                continue;
            }
        }
        
        // Jika semua model gagal
        if (!$response || !$response->successful()) {
            $modelsList = implode(', ', $availableModels);
            if ($lastError) {
                throw new \RuntimeException("Semua model yang dicoba gagal. Error terakhir: {$lastError}. Model yang tersedia untuk v1beta: {$modelsList}");
            }
            $this->handleGeminiApiError($response);
        }

        // Log response untuk debugging
        $responseBody = $response->json();
        Log::info('Google Gemini API Response', [
            'status' => $response->status(),
            'body' => $responseBody,
            'model_used' => $successfulModel ?? $requestedModel,
            'requested_model' => $requestedModel,
        ]);

        // Extract content from Gemini response
        $content = $response->json('candidates.0.content.parts.0.text');
        
        if (empty($content)) {
            Log::error('Google Gemini returned empty response', [
                'response_status' => $response->status(),
                'response_body' => $responseBody,
                'response_text' => $response->body(),
            ]);
            
            $errorMsg = 'Google Gemini mengembalikan response kosong. ';
            if (isset($responseBody['error'])) {
                $errorMsg .= 'Error dari API: ' . ($responseBody['error']['message'] ?? json_encode($responseBody['error']));
            } else {
                $errorMsg .= 'Silakan coba lagi dalam beberapa saat atau periksa koneksi internet Anda.';
            }
            
            throw new \RuntimeException($errorMsg);
        }

        // 3. Post-Processing
        $content = $this->cleanOutput($content);
        $content = $this->injectDependencies($content);
        
        // JANGAN extract JavaScript - biarkan tetap di HTML
        // JavaScript akan tetap di dalam HTML untuk kemudahan editing

        return [
            'html' => $content,
            'css' => null, // Tailwind handle CSS
            'js' => null,  // JavaScript sudah di dalam HTML
        ];
    }

    /**
     * Handle Google Gemini API errors
     */
    private function handleGeminiApiError($response): void
    {
        $body = $response->json();
        $statusCode = $response->status();
        $errorMsg = $body['error']['message'] ?? $response->body();
        
        Log::error('Google Gemini API Error', [
            'status' => $statusCode,
            'response' => $body,
        ]);

        // Handle specific error codes
        if ($statusCode === 401 || $statusCode === 403) {
            $message = "API Key tidak valid atau tidak memiliki akses. ";
            $message .= "Silakan periksa GOOGLE_GEMINI_API_KEY di file .env dan pastikan API key Anda valid di https://makersuite.google.com/app/apikey";
            throw new \RuntimeException($message);
        }

        if ($statusCode === 429) {
            $message = "Rate limit tercapai untuk Google Gemini API. ";
            $message .= "Silakan coba lagi dalam beberapa detik.";
            throw new \RuntimeException($message);
        }

        if ($statusCode === 400 || $statusCode === 404) {
            $message = "Request tidak valid ke Google Gemini API. ";
            if (isset($body['error']['message'])) {
                $errorMessage = $body['error']['message'];
                $message .= $errorMessage;
                
                // Jika error tentang model tidak ditemukan, berikan saran
                if (str_contains(strtolower($errorMessage), 'not found') || str_contains(strtolower($errorMessage), 'not supported')) {
                    $message .= "\n\nSaran: Model yang tersedia untuk v1beta API: gemini-2.5-flash, gemini-2.0-flash, gemini-1.5-pro-latest, atau gemini-1.5-pro. ";
                    $message .= "Ubah GOOGLE_GEMINI_MODEL di file .env menjadi salah satu model tersebut. ";
                    $message .= "Sistem akan otomatis mencoba model lain jika model yang diminta tidak tersedia.";
                }
            } else {
                $message .= "Periksa format request dan pastikan model yang digunakan tersedia.";
            }
            throw new \RuntimeException($message);
        }

        // Generic error message
        $message = "Gagal generate website dengan Google Gemini: $errorMsg";
        if ($statusCode >= 500) {
            $message .= " (Server error dari Google Gemini. Silakan coba lagi nanti.)";
        }

        throw new \RuntimeException($message);
    }

    /**
     * Membangun System Prompt yang Universal dan Fleksibel (IMPROVED VERSION)
     */
    private function buildSystemPrompt(array $formData): string
    {
        // Style Logic yang Lebih Modern
        $style = $formData['style_tone'] ?? 'modern';
        $styleInstructions = match ($style) {
            'corporate' => 'Style: "Trustworthy Enterprise". Use deep blues/greys, ample whitespace, serif headings (Playfair Display) mixed with sans-serif body. Box-shadows should be soft and diffuse. No hard borders.',
            'creative' => 'Style: "Digital Agency". Use heavy gradients, large typography (8xl), dark mode aesthetics, and glassmorphism (backdrop-blur). Use abstract shapes in backgrounds.',
            'minimalist' => 'Style: "Swiss Design". Strict grid usage, high contrast black/white, very little color usage (only for CTAs), massive whitespace.',
            'playful' => 'Style: "Modern Startup". Soft rounded corners (rounded-2xl), pastel colors, bouncy animations, illustrations vibe.',
            'luxury' => 'Style: "High-End". Gold/Black or Cream/Charcoal palette. Uppercase tracking-widest typography. Thin 1px borders. Elegant transitions.',
            default => 'Style: "Modern SaaS/Tech". Think Linear.app, Stripe, or Vercel design. Subtle borders (border-white/10), gradients, soft glows, inter typography, rounded-xl components.',
        };

        // Color Logic
        $colorPrompt = "";
        if (!empty($formData['primary_color'])) {
            $p = $formData['primary_color'];
            $s = $formData['secondary_color'] ?? '#1f2937';
            $a = $formData['accent_color'] ?? '#3b82f6';
            
            $colorPrompt = <<<EOT
            COLOR RULES (STRICT):
            - Primary Brand Color: $p (Use for main buttons, headers, key text)
            - Secondary Color: $s (Use for footers, backgrounds)
            - Accent Color: $a (Use for CTAs, highlights, icons)
            - YOU MUST convert these hex codes to Tailwind arbitrary values (e.g., bg-[$p], text-[$a]) or closest Tailwind classes.
            EOT;
        } else {
            $colorPrompt = "Choose a professional color palette derived from the context of the user request. Ensure high contrast and accessibility.";
        }

        return <<<EOT
You are a World-Class UI/UX Designer and Senior Frontend Developer.

Your goal is to build a website that looks EXPENSIVE, TRUSTWORTHY, and HIGH-CONVERSION.

### DESIGN SYSTEM RULES (MANDATORY):

1. **Typography Hierarchy**:
   - Use `font-sans` for body. Headings must be bold and tight (tracking-tight).
   - Hero H1 size must be minimum `text-5xl md:text-7xl`.
   - Use `text-gray-500` for subtitles, never pure black.

2. **Visual Polish (The "Premium" Look)**:
   - **Shadows**: NEVER use default shadows. Use `shadow-[0_8px_30px_rgb(0,0,0,0.12)]` or similar soft shadows.
   - **Borders**: Use subtle borders: `border border-gray-100` (light) or `border border-white/10` (dark).
   - **Gradients**: Use subtle background gradients to add depth (e.g., `bg-gradient-to-br from-gray-50 to-gray-100`).
   - **Glassmorphism**: Use `backdrop-blur-md bg-white/70` for sticky navbars or overlay cards.
   - **Spacing**: Use EXTREME whitespace. Section padding should be `py-20` or `py-24`. Gap between grid items should be `gap-8` or `gap-12`.

3. **Micro-Interactions**:
   - Every button must have: `transition-all duration-300 hover:scale-105 active:scale-95`.
   - Every card must have: `group hover:-translate-y-2 hover:shadow-2xl transition-all duration-300`.
   - Images must have `hover:scale-105 transition-transform duration-700 ease-out` inside an `overflow-hidden` container.

### PEXELS IMAGE RULES (STRICT):

Use the following strict logic for images. DO NOT hallucinate IDs.

Format: `https://images.pexels.com/photos/[ID]/pexels-photo-[ID].jpeg?auto=compress&cs=tinysrgb&w=[WIDTH]`

**IDs to Rotate (Pick Randomly):**

- Business/Office: `3183150`, `3184291`, `3184292`, `1181605`, `3182812`
- Tech/Code: `1181244`, `1181263`, `577585`, `546819`, `1714208`
- Medical: `416778`, `3259628`, `3259623`, `5215024`, `3038740`
- Food: `1640777`, `1267320`, `958545`, `262978`, `699953`
- Nature/General: `3225517`, `1761279`, `1287145`, `1323550`
- Mechanic/Auto: `4489749`, `3806249`, `2244746`, `190574`, `3807386`

**Image Styling**:
- ALWAYS wrap images in a container with `rounded-2xl overflow-hidden shadow-lg`.
- ALWAYS use `object-cover w-full h-full` class on the `<img>` tag.

### COMPONENT STRUCTURE:

**IMPORTANT**: The user will specify which sections to include and their order. Follow that specification EXACTLY.

Common sections and their requirements:
1. **Navbar**: Sticky, Glassmorphism, Logo left, Links center, CTA right.
2. **Hero**: 
   - Split layout (Text Left, Image Right) OR Center align with large background image + overlay.
   - H1 must be huge (text-5xl md:text-7xl).
   - Add "Trusted by" section below Hero with grayscale logos (use FontAwesome icons as fake logos).
3. **About**: Story section with image and text, or split layout.
4. **Services**: Use Bento Grid layout (Grid spans) or Cards with icons.
5. **Features**: Grid of feature cards with icons and descriptions.
6. **Portfolio/Gallery**: Masonry grid or clean grid with images.
7. **Testimonials**: Use a Masonry grid or a clean row with Avatar, Stars (yellow-400), and Quote.
8. **Pricing**: Clean pricing tables with comparison.
9. **Team**: Grid of team members with photos and roles.
10. **FAQ**: Accordion-style FAQ section.
11. **Blog**: Grid of blog posts with thumbnails.
12. **Contact**: Contact form with map or contact information.
13. **Footer**: Links, Socials, Copyright, Newsletter signup.

### OUTPUT FORMAT:

- Return ONLY valid HTML from `<!DOCTYPE html>` to `</html>`.
- Include `<script src="https://cdn.tailwindcss.com"></script>` in head.
- Include FontAwesome CDN.
- **Inject Modern Fonts**: Include Google Fonts link for 'Outfit' and 'Plus Jakarta Sans'.
- **Animation Script**: Include a simple Intersection Observer script at the bottom to fade-in elements when they scroll into view (add class `opacity-0 translate-y-10` initially, remove on scroll).

$colorPrompt

STYLE GUIDE:
$styleInstructions

Start generating now.

EOT;
    }

    /**
     * Membungkus prompt user dengan instruksi struktur
     */
    private function buildUserPrompt(string $rawPrompt, array $formData = []): string
    {
        // Jika gambar dikirim sebagai vision (base64), tidak perlu tambahkan note di sini
        // Instruksi analisa gambar akan ditambahkan di generateWithGoogleGemini
        $referenceImageNote = '';
        if (!empty($formData['reference_image_path']) && empty($formData['reference_image_base64'])) {
            // Hanya tambahkan note jika gambar tidak dikirim sebagai vision (fallback untuk OpenRouter)
            $referenceImageNote = "\n\nREFERENCE IMAGE:\nUser telah mengupload gambar referensi untuk desain. Gunakan gambar referensi ini sebagai inspirasi untuk:\n- Layout dan struktur halaman\n- Skema warna dan palet\n- Gaya visual dan estetika\n- Komposisi elemen\n- Mood dan tone desain\n\nPastikan website yang dihasilkan mencerminkan gaya dan estetika dari gambar referensi yang diberikan.";
        }

        // Section configuration
        $sectionsNote = '';
        if (!empty($formData['sections']) && is_array($formData['sections'])) {
            $sectionLabels = [
                'navbar' => 'Navbar (Navigation Bar)',
                'hero' => 'Hero Section (Main Banner)',
                'about' => 'About Us Section',
                'services' => 'Services Section',
                'features' => 'Features Section',
                'portfolio' => 'Portfolio/Gallery Section',
                'testimonials' => 'Testimonials Section',
                'pricing' => 'Pricing Section',
                'team' => 'Team Section',
                'gallery' => 'Gallery Section',
                'faq' => 'FAQ Section',
                'blog' => 'Blog Section',
                'contact' => 'Contact Section',
                'footer' => 'Footer Section',
            ];
            $sectionList = [];
            foreach ($formData['sections'] as $index => $section) {
                $label = $sectionLabels[$section] ?? ucfirst($section);
                $sectionList[] = ($index + 1) . ". {$label}";
            }
            $sectionsNote = "\n\nMANDATORY SECTIONS (BUILD IN THIS EXACT ORDER):\n" . implode("\n", $sectionList) . "\n\nCRITICAL: You MUST include ALL these sections in the exact order specified above. Do NOT skip any section. Each section must be complete and well-designed.";
        }

        return <<<EOT
PROJECT REQUEST:
"$rawPrompt"$referenceImageNote$sectionsNote

INSTRUCTIONS:
1. Analyze the request to determine the Website Type (e.g., E-commerce, Portfolio, Corporate, Landing Page).
2. Plan the sections layout based on the sections specified above (if any).
3. Generate the COMPLETE, FULL HTML code using Tailwind CSS - do NOT truncate or cut off.
4. Make sure the copy (text) is persuasive, in INDONESIAN (Bahasa Indonesia), and relevant to the request.
5. CRITICAL: The HTML MUST be complete from <!DOCTYPE html> to </html> - include ALL sections and JavaScript inside the HTML.
6. Do NOT stop generating mid-way - complete ALL sections before ending with </html> tag.
7. Follow the section order EXACTLY as specified above.
EOT;
    }

    /**
     * Membersihkan output dari AI
     */
    private function cleanOutput(string $content): string
    {
        // Hapus Markdown fences
        $content = preg_replace('/```(html|php)?/i', '', $content);
        $content = str_replace('```', '', $content);
        
        // Hapus teks "Here is your code..." di awal jika ada
        if (($pos = strpos($content, '<!DOCTYPE html>')) !== false) {
            $content = substr($content, $pos);
        } else if (($pos = strpos($content, '<html')) !== false) {
            $content = substr($content, $pos);
        }
        
        // Pastikan HTML lengkap - jika tidak ada </html>, cari </body> atau tambahkan
        if (!str_contains($content, '</html>')) {
            // Jika ada </body>, pastikan ada </html> setelahnya
            if (str_contains($content, '</body>')) {
                // Cek apakah sudah ada </html> setelah </body>
                $bodyPos = strrpos($content, '</body>');
                $afterBody = substr($content, $bodyPos + 7);
                if (!str_contains($afterBody, '</html>')) {
                    $content = str_replace('</body>', "</body>\n</html>", $content);
                }
            }
        }

        return trim($content);
    }

    /**
     * Memastikan library yang dibutuhkan ada (IMPROVED VERSION)
     */
    private function injectDependencies(string $html): string
    {
        // 1. Cek & Tambahkan Basic Meta/Structure
        if (!str_contains($html, '<head>')) {
            $html = "<!DOCTYPE html>\n<html lang='id' class='scroll-smooth'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n" . $html;
        } else {
            $html = str_replace('<html', '<html class="scroll-smooth"', $html);
        }

        // 2. Bersihkan Tailwind versi lama
        $oldTailwindPatterns = [
            '/<link[^>]*href=["\']https:\/\/cdn\.jsdelivr\.net\/npm\/tailwindcss@[^"\']+["\'][^>]*>/i',
            '/<link[^>]*href=["\']https:\/\/unpkg\.com\/tailwindcss@[^"\']+["\'][^>]*>/i',
            '/<link[^>]*href=["\']https:\/\/cdn\.tailwindcss\.com\/[^"\']+["\'][^>]*>/i',
        ];

        foreach ($oldTailwindPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Hapus script Tailwind yang mungkin sudah ada (akan di-inject ulang dengan config)
        $html = preg_replace('/<script[^>]*src=["\']https:\/\/cdn\.tailwindcss\.com["\'][^>]*><\/script>/i', '', $html);

        // 3. Siapkan Dependency Baru (Fonts & Config)
        
        // Font: Outfit (Modern Headings) & Plus Jakarta Sans (Modern Body)
        $googleFonts = '<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">';

        $tailwindCDN = '<script src="https://cdn.tailwindcss.com"></script>';
        
        // Config Tailwind Custom agar terlihat Professional
        $tailwindConfig = <<<SCRIPT
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                            heading: ['"Outfit"', 'sans-serif'],
                        },
                        colors: {
                            primary: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                500: '#0ea5e9', // Sky Blue modern
                                600: '#0284c7',
                                900: '#0c4a6e',
                            },
                            dark: '#0f172a',
                        },
                        boxShadow: {
                            'soft': '0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01)',
                            'glow': '0 0 20px rgba(14, 165, 233, 0.5)',
                        }
                    }
                }
            }
        </script>
        <style>
            .glass {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            .fade-up {
                opacity: 0;
                transform: translateY(30px);
                transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            }
            .fade-up.visible {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
        SCRIPT;

        // 4. Inject ke dalam <head>
        $injectionBlock = $googleFonts . "\n" . $tailwindCDN . "\n" . $tailwindConfig;
        
        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', $injectionBlock . "\n</head>", $html);
        } else {
            $html = preg_replace('/(<head[^>]*>)/i', '$1' . "\n" . $injectionBlock, $html);
        }

        // 5. Inject FontAwesome (Hanya jika belum ada)
        if (!str_contains($html, 'font-awesome') && !str_contains($html, 'fontawesome')) {
            $fa = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />';
            $html = str_replace('</head>', $fa . "\n</head>", $html);
        }

        // 6. Inject Script Animasi Scroll (Intersection Observer)
        $scrollScript = <<<SCRIPT
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mobile Menu Logic
                const btn = document.querySelector('button[id*="mobile"], button[class*="hamburger"]');
                const menu = document.querySelector('#mobile-menu, .mobile-menu');
                if(btn && menu) {
                    btn.addEventListener('click', () => {
                        menu.classList.toggle('hidden');
                    });
                }

                // Scroll Animation Logic
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                        }
                    });
                }, { threshold: 0.1 });

                // Apply to sections and cards
                document.querySelectorAll('section, .card, .bg-white, h1, h2, img').forEach(el => {
                    el.classList.add('fade-up');
                    observer.observe(el);
                });
            });
        </script>
        SCRIPT;

        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $scrollScript . "\n</body>", $html);
        } else {
            $html .= $scrollScript . "\n</body>\n</html>";
        }
        
        // Pastikan HTML lengkap
        if (!str_contains($html, '</html>')) {
            $html .= "\n</html>";
        }

        return $html;
    }

    private function handleApiError($response)
    {
        $body = $response->json();
        $statusCode = $response->status();
        $errorCode = $body['error']['code'] ?? null;
        $errorMsg = $body['error']['message'] ?? $response->body();
        
        Log::error('OpenRouter Error', [
            'status' => $statusCode,
            'code' => $errorCode,
            'response' => $body,
        ]);

        // Handle specific error codes
        if ($statusCode === 401 || $errorCode === 401) {
            $message = "API Key tidak valid atau tidak ditemukan. ";
            $message .= "Silakan periksa OPENROUTER_API_KEY di file .env dan pastikan API key Anda valid di https://openrouter.ai/keys";
            throw new \RuntimeException($message);
        }

        if ($statusCode === 429 || $errorCode === 429) {
            $message = "Traffic sedang tinggi atau rate limit tercapai. ";
            if (isset($body['error']['metadata']['raw'])) {
                $message .= $body['error']['metadata']['raw'];
            } else {
                $message .= "Silakan coba lagi dalam beberapa detik.";
            }
            throw new \RuntimeException($message);
        }

        if ($statusCode === 400 || $errorCode === 400) {
            $message = "Request tidak valid. ";
            if (isset($body['error']['message'])) {
                $errorMessage = $body['error']['message'];
                // Handle token limit error specifically
                if (str_contains(strtolower($errorMessage), 'token') || str_contains(strtolower($errorMessage), 'exceed')) {
                    $message = "Prompt terlalu panjang atau melebihi batas token model. ";
                    $message .= "Silakan gunakan prompt yang lebih singkat atau kurangi detail yang diminta.";
                } else {
                    $message .= $errorMessage;
                }
            } else {
                $message .= "Periksa model yang digunakan dan pastikan format request benar.";
            }
            throw new \RuntimeException($message);
        }

        // Generic error message
        $message = "Gagal generate website: $errorMsg";
        if ($statusCode >= 500) {
            $message .= " (Server error dari OpenRouter. Silakan coba lagi nanti.)";
        }

        throw new \RuntimeException($message);
    }

    /**
     * Extract JavaScript dari HTML
     */
    private function extractJavaScript(string $html): ?string
    {
        preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $matches);
        
        if (empty($matches[1])) {
            return null;
        }

        $js = implode("\n\n", $matches[1]);
        return trim($js) ?: null;
    }

    /**
     * Improve website dengan hanya mengubah bagian spesifik yang diminta
     * (font, warna, teks, bentuk) tanpa merubah struktur total
     * 
     * @param string $existingHtml HTML yang sudah ada
     * @param string $improvePrompt Instruksi perbaikan spesifik
     * @param array $formData Form data termasuk ai_provider
     */
    public function improveWebsite(string $existingHtml, string $improvePrompt, array $formData = []): array
    {
        $provider = $formData['ai_provider'] ?? 'openrouter';
        
        if ($provider === 'google_gemini') {
            return $this->improveWithGoogleGemini($existingHtml, $improvePrompt, $formData);
        }
        
        return $this->improveWithOpenRouter($existingHtml, $improvePrompt, $formData);
    }

    /**
     * Improve website menggunakan OpenRouter API
     */
    private function improveWithOpenRouter(string $existingHtml, string $improvePrompt, array $formData = []): array
    {
        $apiKey = config('services.openrouter.key');
        $baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1/chat/completions');
        $referer = config('services.openrouter.referer', config('app.url'));
        $title = config('services.openrouter.title', 'AI Web Generator');
        $model = config('services.openrouter.model', 'anthropic/claude-3.5-sonnet');

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENROUTER_API_KEY tidak ditemukan di file .env.');
        }

        // Build system prompt khusus untuk improve
        $systemPrompt = $this->buildImproveSystemPrompt();

        // Build user prompt dengan HTML existing dan improve instruction
        $userPrompt = $this->buildImproveUserPrompt($existingHtml, $improvePrompt);

        // Estimasi token
        $estimatedSystemTokens = intval(strlen($systemPrompt) / 3.5);
        $estimatedUserTokens = intval(strlen($userPrompt) / 3.5);
        $totalInputTokens = $estimatedSystemTokens + $estimatedUserTokens;
        
        $maxInputTokens = 45000;
        if ($totalInputTokens > $maxInputTokens) {
            $maxUserPromptLength = intval(($maxInputTokens - $estimatedSystemTokens) * 3.5);
            if ($maxUserPromptLength > 1000) {
                $userPrompt = substr($userPrompt, 0, $maxUserPromptLength) . "\n\n[Catatan: HTML dipotong karena terlalu panjang.]";
                Log::warning('Improve prompt terlalu panjang, dipotong', [
                    'estimated_tokens' => $totalInputTokens,
                ]);
            }
        }

        // Tentukan max_tokens
        $maxOutputTokens = 16000;
        if (str_contains(strtolower($model), 'nova')) {
            $maxOutputTokens = 15000;
        } elseif (str_contains(strtolower($model), 'claude')) {
            $maxOutputTokens = 8000;
        } elseif (str_contains(strtolower($model), 'gpt-4')) {
            $maxOutputTokens = 8000;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => $referer,
                'X-Title' => $title,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($baseUrl, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.5, // Lebih rendah untuk lebih konsisten
                'max_tokens' => $maxOutputTokens,
            ]);

            if (!$response->successful()) {
                $this->handleApiError($response);
            }

            $responseBody = $response->json();
            Log::info('OpenRouter Improve API Response', [
                'status' => $response->status(),
            ]);

            $content = $response->json('choices.0.message.content');
            
            if (empty($content)) {
                $content = $response->json('choices.0.text') 
                    ?? $response->json('data.0.text')
                    ?? $response->json('content');
            }
            
            if (empty($content)) {
                Log::error('AI returned empty response for improve', [
                    'response_status' => $response->status(),
                    'response_body' => $responseBody,
                ]);
                
                throw new \RuntimeException('AI service mengembalikan response kosong. Silakan coba lagi dalam beberapa saat.');
            }

            // Post-Processing
            $content = $this->cleanOutput($content);
            
            // Validasi bahwa HTML masih lengkap setelah cleanOutput
            if (empty($content) || trim($content) === '') {
                Log::error('HTML menjadi kosong setelah cleanOutput', [
                    'original_length' => strlen($response->json('choices.0.message.content') ?? ''),
                ]);
                throw new \RuntimeException('HTML menjadi kosong setelah pemrosesan. Silakan coba lagi.');
            }

            // Pastikan HTML memiliki struktur dasar
            if (!str_contains($content, '<html') && !str_contains($content, '<!DOCTYPE')) {
                Log::warning('HTML mungkin tidak lengkap setelah improve', [
                    'content_preview' => substr($content, 0, 200),
                ]);
                // Tetap kembalikan, mungkin AI mengembalikan partial HTML
            }

            // Jangan inject dependencies lagi karena sudah ada di HTML existing
            // Hanya pastikan struktur HTML valid

            return [
                'html' => $content,
                'css' => null,
                'js' => null,
            ];

        } catch (\Exception $e) {
            Log::error('OpenRouter Improve Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Improve website menggunakan Google Gemini API
     */
    private function improveWithGoogleGemini(string $existingHtml, string $improvePrompt, array $formData = []): array
    {
        $apiKey = config('services.google_gemini.key');
        $baseUrl = config('services.google_gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        
        if (empty($apiKey)) {
            throw new \RuntimeException('GOOGLE_GEMINI_API_KEY tidak ditemukan di file .env.');
        }

        $requestedModel = config('services.google_gemini.model', 'gemini-2.5-flash');
        $availableModels = [
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-pro-latest',
            'gemini-1.5-pro',
        ];
        
        if (!in_array($requestedModel, $availableModels)) {
            array_unshift($availableModels, $requestedModel);
        } else {
            $key = array_search($requestedModel, $availableModels);
            if ($key !== false) {
                unset($availableModels[$key]);
                array_unshift($availableModels, $requestedModel);
            }
        }

        $systemPrompt = $this->buildImproveSystemPrompt();
        $userPrompt = $this->buildImproveUserPrompt($existingHtml, $improvePrompt);
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $contentParts = [
            ['text' => $fullPrompt]
        ];

        $lastError = null;
        $response = null;
        $successfulModel = null;
        
        foreach ($availableModels as $tryModel) {
            $url = "{$baseUrl}/models/{$tryModel}:generateContent?key={$apiKey}";
            
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(120)->post($url, [
                    'contents' => [
                        [
                            'parts' => $contentParts
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.5, // Lebih rendah untuk lebih konsisten
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 32768,
                    ],
                ]);

                if ($response->successful()) {
                    $successfulModel = $tryModel;
                    break;
                }
                
                if ($response->status() === 400 || $response->status() === 404) {
                    $errorBody = $response->json();
                    $errorMsg = $errorBody['error']['message'] ?? '';
                    if (str_contains(strtolower($errorMsg), 'not found') || str_contains(strtolower($errorMsg), 'not supported')) {
                        $lastError = $errorMsg;
                        continue;
                    }
                }
                
                $this->handleGeminiApiError($response);
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }
        
        if (!$response || !$response->successful()) {
            $modelsList = implode(', ', $availableModels);
            if ($lastError) {
                throw new \RuntimeException("Semua model yang dicoba gagal. Error terakhir: {$lastError}. Model yang tersedia: {$modelsList}");
            }
            $this->handleGeminiApiError($response);
        }

        $responseBody = $response->json();
        Log::info('Google Gemini Improve API Response', [
            'status' => $response->status(),
            'model_used' => $successfulModel ?? $requestedModel,
        ]);

        $content = $response->json('candidates.0.content.parts.0.text');
        
        if (empty($content)) {
            Log::error('Google Gemini returned empty response for improve', [
                'response_status' => $response->status(),
                'response_body' => $responseBody,
            ]);
            
            throw new \RuntimeException('Google Gemini mengembalikan response kosong. Silakan coba lagi dalam beberapa saat.');
        }

        $content = $this->cleanOutput($content);
        
        // Validasi bahwa HTML masih lengkap setelah cleanOutput
        if (empty($content) || trim($content) === '') {
            Log::error('HTML menjadi kosong setelah cleanOutput (Gemini)', [
                'original_length' => strlen($response->json('candidates.0.content.parts.0.text') ?? ''),
            ]);
            throw new \RuntimeException('HTML menjadi kosong setelah pemrosesan. Silakan coba lagi.');
        }

        // Pastikan HTML memiliki struktur dasar
        if (!str_contains($content, '<html') && !str_contains($content, '<!DOCTYPE')) {
            Log::warning('HTML mungkin tidak lengkap setelah improve (Gemini)', [
                'content_preview' => substr($content, 0, 200),
            ]);
            // Tetap kembalikan, mungkin AI mengembalikan partial HTML
        }

        return [
            'html' => $content,
            'css' => null,
            'js' => null,
        ];
    }

    /**
     * Build system prompt khusus untuk improve (hanya mengubah spesifik)
     */
    private function buildImproveSystemPrompt(): string
    {
        return <<<EOT
You are a skilled Frontend Developer specializing in making PRECISE, TARGETED modifications to existing HTML code.

Your task is to modify ONLY the specific parts requested by the user, while keeping the ENTIRE rest of the code EXACTLY the same.

### CRITICAL RULES:

1. **PRESERVE STRUCTURE**: Do NOT change the overall structure, layout, or sections of the website. Keep all sections, divs, and containers exactly as they are.

2. **TARGETED MODIFICATIONS ONLY**: Only modify:
   - Font styles (font-family, font-size, font-weight, etc.)
   - Colors (text colors, background colors, border colors)
   - Text content (specific text changes requested)
   - Shape/styling (border-radius, shadows, borders, etc.)
   - Specific CSS classes or inline styles

3. **DO NOT CHANGE**:
   - HTML structure and element hierarchy
   - Section order or layout
   - JavaScript code (unless specifically requested)
   - Meta tags, head content (unless specifically requested)
   - Overall design structure

4. **OUTPUT FORMAT**:
   - Return the COMPLETE HTML code from <!DOCTYPE html> to </html>
   - Include ALL original code with ONLY the requested modifications applied
   - Maintain all original classes, IDs, and attributes
   - Keep all scripts, styles, and dependencies intact

5. **MODIFICATION APPROACH**:
   - If user asks to change font: Only modify font-family classes or styles
   - If user asks to change color: Only modify color-related classes or styles, AND update tailwind.config if colors are defined there
   - If user asks to change text: Only modify the text content in the specified elements
   - If user asks to change shape: Only modify border-radius, shape-related classes
   - If there are duplicate configurations (like multiple tailwind.config blocks), remove duplicates and keep only one consistent configuration

6. **COLOR THEME CONSISTENCY**:
   - When changing colors, ensure consistency across:
     * tailwind.config color definitions
     * CSS classes using those colors (bg-primary, text-primary, etc.)
     * Inline styles
   - Remove duplicate color definitions and keep only one source of truth
   - Update all references to use the same color values

Remember: Your goal is to make MINIMAL, PRECISE changes while preserving everything else. Ensure color consistency across the entire document.
EOT;
    }

    /**
     * Build user prompt untuk improve dengan HTML existing
     */
    private function buildImproveUserPrompt(string $existingHtml, string $improvePrompt): string
    {
        return <<<EOT
EXISTING HTML CODE:
```
{$existingHtml}
```

IMPROVEMENT REQUEST:
"{$improvePrompt}"

INSTRUCTIONS:
1. Analyze the existing HTML code carefully.
2. Identify ONLY the specific parts that need to be modified based on the improvement request.
3. Make PRECISE modifications to ONLY those parts (font, color, text, shape, etc.).
4. Keep ALL other code EXACTLY the same - do not change structure, layout, or sections.
5. Return the COMPLETE modified HTML code from <!DOCTYPE html> to </html>.
6. Ensure all modifications are applied correctly while preserving the original design structure.

Examples of what to modify:
- "Ubah warna header menjadi biru" → Only change color classes/styles in header section
- "Ganti font menjadi Arial" → Only change font-family classes/styles
- "Ubah teks 'Selamat Datang' menjadi 'Welcome'" → Only change that specific text
- "Buat tombol lebih bulat" → Only change border-radius on buttons
- "Ubah warna background section hero" → Only change background color in hero section
- "Perbaiki warna tema menjadi biru-putih" → Update tailwind.config colors AND all color classes throughout the HTML to match the blue-white theme
- "Hapus duplikasi konfigurasi Tailwind" → Remove duplicate tailwind.config blocks and keep only one consistent configuration
EOT;
    }
}