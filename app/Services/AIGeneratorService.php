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
        $model = config('services.openrouter.model', 'anthropic/claude-3.5-sonnet'); 

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENROUTER_API_KEY tidak ditemukan di file .env. Silakan tambahkan API key Anda dari https://openrouter.ai/keys');
        }

        if (!str_starts_with($apiKey, 'sk-') && !str_starts_with($apiKey, 'sk-or-')) {
            Log::warning('OpenRouter API Key format mungkin tidak valid', [
                'key_prefix' => substr($apiKey, 0, 10) . '...',
            ]);
        }

        // 1. Build The System Prompt (dengan user prompt untuk detect style)
        $systemPrompt = $this->buildSystemPrompt($prompt, $formData);

        // 2. Build The User Prompt (Contextualized)
        $userPrompt = $this->buildUserPrompt($prompt, $formData);

        // 3. Estimasi token dan batasi panjang prompt
        $estimatedSystemTokens = intval(strlen($systemPrompt) / 3.5);
        $estimatedUserTokens = intval(strlen($userPrompt) / 3.5);
        $totalInputTokens = $estimatedSystemTokens + $estimatedUserTokens;
        
        $maxInputTokens = 45000;
        if ($totalInputTokens > $maxInputTokens) {
            $maxUserPromptLength = intval(($maxInputTokens - $estimatedSystemTokens) * 3.5);
            if ($maxUserPromptLength > 1000) {
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
        $maxOutputTokens = 16000;
        if (str_contains(strtolower($model), 'nova')) {
            $maxOutputTokens = 15000;
        } elseif (str_contains(strtolower($model), 'claude')) {
            $maxOutputTokens = 8000;
        } elseif (str_contains(strtolower($model), 'gpt-4')) {
            $maxOutputTokens = 8000;
        }

        // Prepare messages - include image if available for vision-capable models
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        
        if (!empty($formData['reference_image_base64']) && !empty($formData['reference_image_mime_type'])) {
            $visionModels = ['gpt-4-vision', 'gpt-4o', 'claude-3', 'claude-3.5'];
            $supportsVision = false;
            foreach ($visionModels as $visionModel) {
                if (str_contains(strtolower($model), strtolower($visionModel))) {
                    $supportsVision = true;
                    break;
                }
            }
            
            if ($supportsVision) {
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
                $referenceImageNote = "\n\nREFERENCE IMAGE:\nUser telah mengupload gambar referensi untuk desain. Gunakan gambar referensi ini sebagai inspirasi untuk layout, warna, gaya visual, dan komposisi elemen.";
                $messages[] = ['role' => 'user', 'content' => $userPrompt . $referenceImageNote];
            }
        } else {
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
                'temperature' => 0.85, // Naikkan untuk hasil lebih kreatif
                'max_tokens' => $maxOutputTokens,
            ]);

            if (!$response->successful()) {
                $this->handleApiError($response);
            }

            $responseBody = $response->json();
            Log::info('OpenRouter API Response', [
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            $content = $response->json('choices.0.message.content');
            
            if (empty($content)) {
                $content = $response->json('choices.0.text') 
                    ?? $response->json('data.0.text')
                    ?? $response->json('content');
            }
            
            if (empty($content)) {
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

            $content = $this->cleanOutput($content);
            $content = $this->injectDependencies($content);

            return [
                'html' => $content,
                'css' => null,
                'js' => null,
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

        // 1. Build The System Prompt (dengan user prompt untuk detect style)
        $systemPrompt = $this->buildSystemPrompt($prompt, $formData);

        // 2. Build The User Prompt (Contextualized)
        $userPrompt = $this->buildUserPrompt($prompt, $formData);

        // Combine system and user prompt for Gemini
        $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

        $contentParts = [
            ['text' => $fullPrompt]
        ];
        
        if (!empty($formData['reference_image_base64']) && !empty($formData['reference_image_mime_type'])) {
            $contentParts[] = [
                'inline_data' => [
                    'mime_type' => $formData['reference_image_mime_type'],
                    'data' => $formData['reference_image_base64']
                ]
            ];
            
            $imageAnalysisPrompt = "\n\nANALISA GAMBAR REFERENSI:\nGambar referensi telah disertakan di atas. Analisa gambar ini secara detail dan gunakan sebagai referensi untuk:\n- Layout dan struktur halaman (grid, spacing, alignment)\n- Skema warna dan palet (extract warna dominan dari gambar)\n- Gaya visual dan estetika (modern, minimalis, bold, dll)\n- Komposisi elemen (card design, button style, typography)\n- Mood dan tone desain (professional, playful, elegant, dll)\n\nPastikan website yang dihasilkan mencerminkan gaya visual dari gambar referensi ini.";
            $contentParts[0]['text'] = $fullPrompt . $imageAnalysisPrompt;
            
            Log::info('Google Gemini Vision API - Image included', [
                'mime_type' => $formData['reference_image_mime_type'],
                'image_size' => strlen($formData['reference_image_base64']) . ' bytes (base64)',
            ]);
        }

        $lastError = null;
        $response = null;
        $successfulModel = null;
        
        foreach ($availableModels as $tryModel) {
            $url = "{$baseUrl}/models/{$tryModel}:generateContent?key={$apiKey}";
            
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
                        'temperature' => 0.85, // Naikkan untuk hasil lebih kreatif
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
                        Log::warning("Model {$tryModel} tidak tersedia, mencoba model berikutnya", [
                            'error' => $errorMsg,
                        ]);
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
                throw new \RuntimeException("Semua model yang dicoba gagal. Error terakhir: {$lastError}. Model yang tersedia untuk v1beta: {$modelsList}");
            }
            $this->handleGeminiApiError($response);
        }

        $responseBody = $response->json();
        Log::info('Google Gemini API Response', [
            'status' => $response->status(),
            'body' => $responseBody,
            'model_used' => $successfulModel ?? $requestedModel,
            'requested_model' => $requestedModel,
        ]);

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

        $content = $this->cleanOutput($content);
        $content = $this->injectDependencies($content);

        return [
            'html' => $content,
            'css' => null,
            'js' => null,
        ];
    }

    /**
     * Detect design style dari prompt user
     */
    private function detectDesignStyle(string $prompt, ?string $manualStyle = null): string
    {
        // Jika ada manual style dari form, gunakan itu
        if (!empty($manualStyle)) {
            return $manualStyle;
        }

        $promptLower = mb_strtolower($prompt);
        
        // Keyword mapping untuk detect style
        $styleKeywords = [
            'playful' => ['fun', 'funny', 'lucu', 'ceria', 'gembira', 'warna-warni', 'colorful', 'playful', 'main-main', 'santai', 'casual', 'relax'],
            'luxury' => ['mewah', 'luxury', 'premium', 'high-end', 'exclusive', 'elite', 'mahal', 'berkelas', 'elegant', 'sophisticated', 'refined'],
            'corporate' => ['kantor', 'corporate', 'bisnis', 'business', 'profesional', 'formal', 'enterprise', 'perusahaan', 'resmi', 'serius'],
            'creative' => ['creative', 'kreatif', 'unik', 'unique', 'artistik', 'artistic', 'bold', 'berani', 'vibrant', 'energik', 'dynamic'],
            'minimalist' => ['minimalis', 'minimalist', 'simple', 'sederhana', 'clean', 'bersih', 'swiss', 'grid', 'geometric'],
            'tech' => ['tech', 'teknologi', 'futuristic', 'modern', 'digital', 'cyber', 'ai', 'software', 'startup', 'saas'],
        ];

        // Hitung score untuk setiap style
        $scores = [];
        foreach ($styleKeywords as $style => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($promptLower, $keyword)) {
                    $score += substr_count($promptLower, $keyword);
                }
            }
            if ($score > 0) {
                $scores[$style] = $score;
            }
        }

        // Return style dengan score tertinggi, atau default 'modern'
        if (!empty($scores)) {
            arsort($scores);
            return array_key_first($scores);
        }

        return 'modern';
    }

    /**
     * Get font pairing berdasarkan style
     */
    private function getFontPairing(string $styleKey): array
    {
        $fontPairings = [
            'playful' => [
                'heading' => 'Comfortaa',
                'body' => 'Nunito',
                'url' => 'https://fonts.googleapis.com/css2?family=Comfortaa:wght@300;400;500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap',
            ],
            'luxury' => [
                'heading' => 'Playfair Display',
                'body' => 'Cormorant Garamond',
                'url' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@300;400;500;600;700&display=swap',
            ],
            'corporate' => [
                'heading' => 'Inter',
                'body' => 'Open Sans',
                'url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap',
            ],
            'creative' => [
                'heading' => 'Bebas Neue',
                'body' => 'Poppins',
                'url' => 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500;600;700;800&display=swap',
            ],
            'minimalist' => [
                'heading' => 'Space Grotesk',
                'body' => 'Work Sans',
                'url' => 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Work+Sans:wght@400;500;600;700&display=swap',
            ],
            'tech' => [
                'heading' => 'JetBrains Mono',
                'body' => 'DM Sans',
                'url' => 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap',
            ],
            'modern' => [
                'heading' => 'Outfit',
                'body' => 'Plus Jakarta Sans',
                'url' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
            ],
        ];

        return $fontPairings[$styleKey] ?? $fontPairings['modern'];
    }

    /**
     * Build System Prompt dengan Design Persona Dinamis
     */
    private function buildSystemPrompt(string $userPrompt, array $formData = []): string
    {
        // Detect design style dari prompt
        $detectedStyle = $this->detectDesignStyle($userPrompt, $formData['style_tone'] ?? null);
        $fontPairing = $this->getFontPairing($detectedStyle);

        // Style instructions berdasarkan detected style
        $styleInstructions = match ($detectedStyle) {
            'playful' => 'Style: "Playful & Vibrant". Use soft rounded corners (rounded-2xl, rounded-3xl), pastel or bright colors, bouncy animations (bounce, scale), playful illustrations vibe, generous spacing. Typography: Comfortable and friendly.',
            'luxury' => 'Style: "High-End Luxury". Use gold/black or cream/charcoal palette. Uppercase tracking-widest typography. Thin 1px borders. Elegant transitions. Serif headings (Playfair Display) for sophistication. Minimal but impactful.',
            'corporate' => 'Style: "Trustworthy Enterprise". Use deep blues/greys, ample whitespace, serif headings (Inter) mixed with clean sans-serif body (Open Sans). Box-shadows should be soft and diffuse. Professional and trustworthy.',
            'creative' => 'Style: "Bold Creative Agency". Use heavy gradients, large typography (text-8xl), dark mode aesthetics, glassmorphism (backdrop-blur). Use abstract shapes in backgrounds. Bold and energetic.',
            'minimalist' => 'Style: "Swiss Design Minimalism". Strict grid usage, high contrast black/white, very little color usage (only for CTAs), massive whitespace. Clean geometric shapes.',
            'tech' => 'Style: "Modern Tech/SaaS". Think Linear.app, Stripe, or Vercel design. Subtle borders (border-white/10), gradients, soft glows, monospace headings for tech feel, rounded-xl components.',
            default => 'Style: "Modern & Clean". Think contemporary SaaS products. Subtle borders, gradients, soft glows, modern typography, rounded-xl components.',
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
            $colorPrompt = "\n\nCOLOR RULES:\nChoose a professional color palette derived from the context of the user request. Ensure high contrast and accessibility. Use colors that match the detected style: {$detectedStyle}.";
        }

        // Pexels Image IDs - lebih variatif dan dikategorikan
        $pexelsIds = [
            'Business/Office' => ['3183150', '3184291', '3184292', '1181605', '3182812', '3184357', '3184360', '3184416', '3184423', '3184436'],
            'Tech/Code' => ['1181244', '1181263', '577585', '546819', '1714208', '1181396', '1181467', '1181675', '1181695', '1181735'],
            'Medical/Health' => ['416778', '3259628', '3259623', '5215024', '3038740', '40568', '40569', '40570', '40571', '40572'],
            'Food/Restaurant' => ['1640777', '1267320', '958545', '262978', '699953', '1267321', '1267322', '1267323', '1267324', '1267325'],
            'Nature/General' => ['3225517', '1761279', '1287145', '1323550', '1323551', '1323552', '1323553', '1323554', '1323555', '1323556'],
            'Mechanic/Auto' => ['4489749', '3806249', '2244746', '190574', '3807386', '3807390', '3807395', '3807400', '3807405', '3807410'],
            'Creative/Art' => ['1552242', '1552243', '1552244', '1552245', '1552246', '1552247', '1552248', '1552249', '1552250', '1552251'],
            'Lifestyle/Fashion' => ['1926769', '1926770', '1926771', '1926772', '1926773', '1926774', '1926775', '1926776', '1926777', '1926778'],
        ];

        $pexelsList = [];
        foreach ($pexelsIds as $category => $ids) {
            $pexelsList[] = "- {$category}: " . implode(', ', $ids);
        }
        $pexelsListString = implode("\n", $pexelsList);

        return <<<EOT
You are a World-Class UI/UX Designer and Senior Frontend Developer with a unique creative vision.

Your goal is to build a website that looks EXPENSIVE, TRUSTWORTHY, and HIGH-CONVERSION, but with a DISTINCTIVE visual identity that stands out.

### DESIGN PERSONA: {$detectedStyle}
{$styleInstructions}

### TYPOGRAPHY SYSTEM:
- Heading Font: {$fontPairing['heading']} (Use for all h1, h2, h3)
- Body Font: {$fontPairing['body']} (Use for paragraphs, body text)
- Font URL: {$fontPairing['url']}
- Hero H1 size must be minimum `text-5xl md:text-7xl`.
- Use proper font-weight hierarchy (400-800).

### LAYOUT PHILOSOPHY:
1. **Asymmetric Layouts**: Break away from rigid grids. Use offset columns, overlapping elements, and varied section widths.
2. **Bento Grids**: Use CSS Grid with varied spans (col-span-2, row-span-2) for visual interest.
3. **Extreme Whitespace**: Section padding should be `py-20` or `py-24`. Gap between grid items should be `gap-8` or `gap-12`.
4. **Visual Hierarchy**: Use size, color, and spacing to create clear visual hierarchy.

### VISUAL POLISH:
- **Shadows**: NEVER use default shadows. Use custom soft shadows: `shadow-[0_8px_30px_rgb(0,0,0,0.12)]` or `shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1)]`.
- **Borders**: Use subtle borders: `border border-gray-100` (light) or `border border-white/10` (dark).
- **Gradients**: Use subtle background gradients to add depth (e.g., `bg-gradient-to-br from-gray-50 to-gray-100`).
- **Glassmorphism**: Use `backdrop-blur-md bg-white/70` for sticky navbars or overlay cards when appropriate.
- **Micro-Interactions**: Every button must have: `transition-all duration-300 hover:scale-105 active:scale-95`. Every card must have: `group hover:-translate-y-2 hover:shadow-2xl transition-all duration-300`.

### PEXELS IMAGE RULES (STRICT):
Use the following strict logic for images. DO NOT hallucinate IDs.
Format: `https://images.pexels.com/photos/[ID]/pexels-photo-[ID].jpeg?auto=compress&cs=tinysrgb&w=[WIDTH]`

**IDs to Rotate (Pick Randomly by Category):**
{$pexelsListString}

**Image Styling**:
- ALWAYS wrap images in a container with `rounded-2xl overflow-hidden shadow-lg`.
- ALWAYS use `object-cover w-full h-full` class on the `<img>` tag.

### COMPONENT STRUCTURE:
**IMPORTANT**: The user will specify which sections to include and their order. Follow that specification EXACTLY.

Common sections and their requirements:
1. **Navbar**: Sticky, Glassmorphism, Logo left, Links center, CTA right.
2. **Hero**: Split layout (Text Left, Image Right) OR Center align with large background image + overlay. H1 must be huge (text-5xl md:text-7xl).
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

### CRITICAL OUTPUT REQUIREMENTS:

1. **Tailwind Config**: YOU MUST include a `<script>tailwind.config = {...}</script>` tag in the HTML output with:
   - Custom `fontFamily` using the fonts specified above ({$fontPairing['heading']} for headings, {$fontPairing['body']} for body)
   - Custom `colors` based on the color palette (use arbitrary values like `bg-[#hex]` or define custom color names)
   - Custom `boxShadow` values for soft shadows
   - Any other theme extensions that match the design style

2. **Google Fonts**: Include the Google Fonts link in `<head>`: `{$fontPairing['url']}`

3. **Complete HTML**: Return ONLY valid HTML from `<!DOCTYPE html>` to `</html>`.

4. **Tailwind CDN**: Include `<script src="https://cdn.tailwindcss.com"></script>` in head.

5. **FontAwesome**: Include FontAwesome CDN for icons.

6. **Animation Script**: Include a simple Intersection Observer script at the bottom to fade-in elements when they scroll into view.

7. **JavaScript**: Include all JavaScript directly in the HTML (inside `<script>` tags before `</body>`).

{$colorPrompt}

### UNIQUENESS REQUIREMENT:
Each website MUST have a unique visual identity. Do NOT use the same color schemes, layouts, or typography combinations. Vary:
- Color palettes (warm vs cool, saturated vs muted)
- Layout structures (centered vs split, grid vs flex)
- Typography sizes and weights
- Spacing patterns
- Shadow styles

Start generating now with a UNIQUE and DISTINCTIVE design.
EOT;
    }

    /**
     * Membungkus prompt user dengan instruksi struktur
     */
    private function buildUserPrompt(string $rawPrompt, array $formData = []): string
    {
        $referenceImageNote = '';
        if (!empty($formData['reference_image_path']) && empty($formData['reference_image_base64'])) {
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
8. Include the Tailwind config script with custom fonts and colors as specified in the system prompt.
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
        
        // Pastikan HTML lengkap
        if (!str_contains($content, '</html>')) {
            if (str_contains($content, '</body>')) {
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
     * Inject Dependencies - Basic Setup Only (No Hardcoded Styles)
     */
    private function injectDependencies(string $html): string
    {
        // 1. Cek & Tambahkan Basic Meta/Structure
        if (!str_contains($html, '<head>')) {
            $html = "<!DOCTYPE html>\n<html lang='id' class='scroll-smooth'>\n<head>\n<meta charset='UTF-8'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n" . $html;
        } else {
            $html = str_replace('<html', '<html class="scroll-smooth"', $html);
        }

        // 2. Bersihkan Tailwind versi lama/duplikat
        $oldTailwindPatterns = [
            '/<link[^>]*href=["\']https:\/\/cdn\.jsdelivr\.net\/npm\/tailwindcss@[^"\']+["\'][^>]*>/i',
            '/<link[^>]*href=["\']https:\/\/unpkg\.com\/tailwindcss@[^"\']+["\'][^>]*>/i',
            '/<link[^>]*href=["\']https:\/\/cdn\.tailwindcss\.com\/[^"\']+["\'][^>]*>/i',
        ];

        foreach ($oldTailwindPatterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Hapus script Tailwind yang mungkin sudah ada (jika AI sudah inject, biarkan)
        // Kita hanya akan inject jika belum ada
        $hasTailwindScript = str_contains($html, 'cdn.tailwindcss.com');
        
        // 3. Inject Tailwind CDN (hanya jika belum ada)
        if (!$hasTailwindScript) {
            $tailwindCDN = '<script src="https://cdn.tailwindcss.com"></script>';
            
            if (str_contains($html, '</head>')) {
                $html = str_replace('</head>', $tailwindCDN . "\n</head>", $html);
            } else {
                $html = preg_replace('/(<head[^>]*>)/i', '$1' . "\n" . $tailwindCDN, $html);
            }
        }

        // 4. Inject FontAwesome (Hanya jika belum ada)
        if (!str_contains($html, 'font-awesome') && !str_contains($html, 'fontawesome')) {
            $fa = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />';
            if (str_contains($html, '</head>')) {
                $html = str_replace('</head>', $fa . "\n</head>", $html);
            } else {
                $html = preg_replace('/(<head[^>]*>)/i', '$1' . "\n" . $fa, $html);
            }
        }

        // 5. Inject Script Animasi Scroll Dasar (Intersection Observer)
        // Hanya jika belum ada script serupa
        if (!str_contains($html, 'IntersectionObserver') && !str_contains($html, 'fade-up')) {
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

                // Scroll Animation Logic (basic)
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                        }
                    });
                }, { threshold: 0.1 });

                // Apply to sections and cards
                document.querySelectorAll('section, .card, .bg-white, h1, h2, img').forEach(el => {
                    if (!el.classList.contains('fade-up')) {
                        el.classList.add('fade-up');
                        observer.observe(el);
                    }
                });
            });
        </script>
        SCRIPT;

            if (str_contains($html, '</body>')) {
                $html = str_replace('</body>', $scrollScript . "\n</body>", $html);
            } else {
                $html .= $scrollScript . "\n</body>\n</html>";
            }
        }
        
        // Pastikan HTML lengkap
        if (!str_contains($html, '</html>')) {
            $html .= "\n</html>";
        }

        return $html;
    }

    /**
     * Handle OpenRouter API errors
     */
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

        $message = "Gagal generate website: $errorMsg";
        if ($statusCode >= 500) {
            $message .= " (Server error dari OpenRouter. Silakan coba lagi nanti.)";
        }

        throw new \RuntimeException($message);
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

        $message = "Gagal generate website dengan Google Gemini: $errorMsg";
        if ($statusCode >= 500) {
            $message .= " (Server error dari Google Gemini. Silakan coba lagi nanti.)";
        }

        throw new \RuntimeException($message);
    }
}
