<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiQuoteAnalyzerService {
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    private const FALLBACK_RESULT = [
        'cleanliness_score'    => 5,
        'difficulty_multiplier' => 1.2,
        'estimated_hours'      => 4.0,
        'recommended_cleaners' => 1,
        'condition_summary'    => 'Unable to complete AI analysis. Default values applied.',
        'high_risk_areas'      => [],
        'confidence_score'     => 0,
    ];

    /**
     * Analyse a quote request using Gemini 2.5 Flash.
     *
     * @param  array  $quoteData   Structured property/service data (NO raw user text).
     * @param  array  $imagePaths  Storage disk paths to uploaded images.
     * @return array  Validated AI assessment (never contains pricing).
     */
    public function analyse(array $quoteData, array $imagePaths): array {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey) || $apiKey === 'your-key-here') {
            Log::warning('GeminiQuoteAnalyzerService: API key not configured. Using fallback values.');
            return self::FALLBACK_RESULT;
        }

        try {
            $prompt = $this->buildStructuredPrompt($quoteData);
            $parts  = [['text' => $prompt]];

            // Attach images as inline base64 data (never expose user names/emails to Gemini)
            foreach ($imagePaths as $path) {
                if (!Storage::disk('public')->exists($path)) {
                    continue;
                }
                $binary   = Storage::disk('public')->get($path);
                $mimeType = $this->detectMimeType($path);
                $parts[]  = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data'      => base64_encode($binary),
                    ],
                ];
            }

            $payload = [
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.1,
                    'maxOutputTokens'  => 1024,
                ],
                'systemInstruction' => [
                    'parts' => [[
                        'text' => $this->systemInstruction(),
                    ]],
                ],
            ];

            Log::info('GeminiQuoteAnalyzerService: Sending request', [
                'property_type' => $quoteData['property_type'] ?? 'unknown',
                'service_type'  => $quoteData['service_type'] ?? 'unknown',
                'image_count'   => count($imagePaths),
            ]);

            $response = Http::withQueryParameters(['key' => $apiKey])
                ->timeout(60)
                ->post(self::API_URL, $payload);

            if (!$response->successful()) {
                Log::error('GeminiQuoteAnalyzerService: API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return self::FALLBACK_RESULT;
            }

            $responseData = $response->json();

            Log::info('GeminiQuoteAnalyzerService: Response received', [
                'response_keys' => array_keys($responseData ?? []),
            ]);

            $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (empty($text)) {
                Log::error('GeminiQuoteAnalyzerService: Empty response text');
                return self::FALLBACK_RESULT;
            }

            $result = json_decode($text, true);

            if (!$result || json_last_error() !== JSON_ERROR_NONE) {
                Log::error('GeminiQuoteAnalyzerService: Invalid JSON in response', ['text' => $text]);
                return self::FALLBACK_RESULT;
            }

            return $this->validateAndClamp($result);

        } catch (\Throwable $e) {
            Log::error('GeminiQuoteAnalyzerService: Exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return self::FALLBACK_RESULT;
        }
    }

    /**
     * Build a fully structured prompt — no raw user text is ever interpolated.
     * All values come from validated, enumerated form fields.
     */
    private function buildStructuredPrompt(array $d): string {
        $propertyDetails = $d['property_details'] ?? [];
        $condition       = $d['condition'] ?? [];
        $addons          = $d['addons'] ?? [];

        $specialConditions = implode(', ', $condition['specialConditions'] ?? []) ?: 'None';
        $addonsList        = implode(', ', $addons) ?: 'None';

        $rooms = array_filter([
            isset($propertyDetails['bedrooms'])    ? "Bedrooms: {$propertyDetails['bedrooms']}"      : null,
            isset($propertyDetails['bathrooms'])   ? "Bathrooms: {$propertyDetails['bathrooms']}"     : null,
            isset($propertyDetails['kitchens'])    ? "Kitchens: {$propertyDetails['kitchens']}"       : null,
            isset($propertyDetails['livingRooms']) ? "Living rooms: {$propertyDetails['livingRooms']}" : null,
            isset($propertyDetails['officeRooms']) ? "Office rooms: {$propertyDetails['officeRooms']}" : null,
            isset($propertyDetails['meetingRooms'])? "Meeting rooms: {$propertyDetails['meetingRooms']}" : null,
            isset($propertyDetails['toilets'])     ? "Toilets: {$propertyDetails['toilets']}"         : null,
        ]);
        $roomsSummary = implode(', ', $rooms) ?: 'Not specified';

        $dirtLevel = $condition['dirtLevel'] ?? 5;

        return <<<EOT
        PROPERTY ASSESSMENT REQUEST

        === PROPERTY DETAILS ===
        Property Type   : {$d['property_type']}
        Total Floor Area: {$d['floor_area']} square metres
        Number of Floors: {$d['floors']}
        Room Breakdown  : {$roomsSummary}

        === SERVICE REQUIREMENTS ===
        Service Type: {$d['service_type']}
        Frequency   : {$d['frequency']}
        Urgency     : {$d['urgency']}

        === CURRENT CONDITION ===
        Overall Condition : {$condition['overallCondition']}
        Dirt Level        : {$dirtLevel} out of 10
        Special Conditions: {$specialConditions}

        === ADDITIONAL SERVICES REQUESTED ===
        {$addonsList}

        === TASK ===
        Analyse the property details and all attached images (if any).
        Assess cleaning complexity, labor requirements, and difficulty.
        Return ONLY a valid JSON object in the exact schema below.
        Do NOT include any explanatory text outside the JSON.
        EOT;
    }

    private function systemInstruction(): string {
        return <<<EOT
        You are a professional cleaning operations estimator in the United Kingdom.

        You are NOT responsible for pricing. You must NOT include any price, cost, or monetary value in your response.

        Your role is ONLY to evaluate:
        1. Property cleanliness level
        2. Property condition and complexity
        3. Estimated labor hours required
        4. Number of cleaners required
        5. Difficulty multiplier (reflects how much harder this job is vs. standard)
        6. High-risk or problem areas

        Use UK commercial and residential cleaning standards.
        Base your estimates on the submitted property data and any attached images.

        You MUST return ONLY this exact JSON schema — no other text:
        {
          "cleanliness_score": <integer 1-10, where 10 = spotless, 1 = extremely dirty>,
          "difficulty_multiplier": <float 1.0-3.0, where 1.0 = standard, 3.0 = extreme>,
          "estimated_hours": <float, total labor hours for all cleaners combined>,
          "recommended_cleaners": <integer 1-10>,
          "condition_summary": "<string, max 200 chars, professional UK English>",
          "high_risk_areas": ["<area1>", "<area2>"],
          "confidence_score": <integer 0-100, your confidence in this assessment>
        }
        EOT;
    }

    /**
     * Validate and clamp all numeric fields to prevent malformed AI output from
     * affecting downstream pricing calculations.
     */
    private function validateAndClamp(array $result): array {
        return [
            'cleanliness_score'    => max(1, min(10, (int)   ($result['cleanliness_score']    ?? 5))),
            'difficulty_multiplier'=> max(1.0, min(3.0, (float)($result['difficulty_multiplier'] ?? 1.2))),
            'estimated_hours'      => max(0.5, min(200.0, (float)($result['estimated_hours']    ?? 4.0))),
            'recommended_cleaners' => max(1, min(10, (int)   ($result['recommended_cleaners']  ?? 1))),
            'condition_summary'    => substr(strip_tags((string)($result['condition_summary']  ?? '')), 0, 500),
            'high_risk_areas'      => array_slice(array_map('strval', (array)($result['high_risk_areas'] ?? [])), 0, 10),
            'confidence_score'     => max(0, min(100, (int)  ($result['confidence_score']      ?? 0))),
        ];
    }

    private function detectMimeType(string $path): string {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => 'image/jpeg',
        };
    }
}
