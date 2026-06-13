<?php

namespace App\Jobs;

use App\Models\QuoteRequest;
use App\Models\QuoteImage;
use App\Services\GeminiQuoteAnalyzerService;
use App\Services\QuotePricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeQuoteJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts before marking as failed.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds (Gemini with images can take time).
     */
    public int $timeout = 90;

    public function __construct(private int $quoteRequestId) {}

    public function handle(
        GeminiQuoteAnalyzerService $geminiService,
        QuotePricingService $pricingService
    ): void {
        $quote = QuoteRequest::find($this->quoteRequestId);

        if (!$quote) {
            Log::error("AnalyzeQuoteJob: QuoteRequest #{$this->quoteRequestId} not found.");
            return;
        }

        Log::info("AnalyzeQuoteJob: Processing quote {$quote->quote_reference}");

        // Collect image storage paths from the quote_images table
        $imagePaths = QuoteImage::where('quote_request_id', $quote->id)
            ->pluck('image_url')
            ->map(fn($url) => $this->urlToStoragePath($url))
            ->filter()
            ->toArray();

        // Build structured data payload for Gemini (no PII — no names/emails)
        $quoteData = [
            'property_type'    => $quote->property_type,
            'floor_area'       => $quote->floor_area,
            'floors'           => $quote->floors,
            'service_type'     => $quote->service_type,
            'frequency'        => $quote->frequency,
            'urgency'          => $quote->urgency,
            'property_details' => $quote->property_details_json ?? [],
            'condition'        => $quote->condition_json ?? [],
            'addons'           => $quote->addons_json ?? [],
        ];

        // --- AI Analysis (assessment only, no pricing) ---
        $aiResult = $geminiService->analyse($quoteData, $imagePaths);

        // --- Backend Pricing Engine (no AI involvement) ---
        $pricingData = [
            'service_type' => $quote->service_type,
            'floor_area'   => $quote->floor_area,
            'urgency'      => $quote->urgency,
            'addons'       => $quote->addons_json ?? [],
        ];
        $priceRange = $pricingService->calculate($pricingData, $aiResult);

        // --- Persist results ---
        $quote->update([
            'ai_analysis_json'     => $aiResult,
            'estimated_hours'      => $aiResult['estimated_hours'],
            'difficulty_multiplier'=> $aiResult['difficulty_multiplier'],
            'confidence_score'     => $aiResult['confidence_score'],
            'recommended_cleaners' => $aiResult['recommended_cleaners'],
            'quote_min'            => $priceRange['quote_min'],
            'quote_max'            => $priceRange['quote_max'],
            'status'               => 'reviewing',
        ]);

        Log::info("AnalyzeQuoteJob: Completed for {$quote->quote_reference}", [
            'quote_min'   => $priceRange['quote_min'],
            'quote_max'   => $priceRange['quote_max'],
            'confidence'  => $aiResult['confidence_score'],
        ]);
    }

    public function failed(\Throwable $exception): void {
        Log::error("AnalyzeQuoteJob: Failed for quote #{$this->quoteRequestId}", [
            'error' => $exception->getMessage(),
        ]);

        // Mark quote as needing manual review
        QuoteRequest::where('id', $this->quoteRequestId)->update([
            'status'      => 'reviewing',
            'admin_notes' => 'AI analysis failed — please review manually. Error: ' . $exception->getMessage(),
        ]);
    }

    /**
     * Convert a public storage URL back to a relative storage path.
     * e.g. http://localhost/storage/quotes/xxx.jpg → quotes/xxx.jpg
     */
    private function urlToStoragePath(string $url): ?string {
        $storageBaseUrl = rtrim(config('app.url'), '/') . '/storage/';
        if (str_starts_with($url, $storageBaseUrl)) {
            return substr($url, strlen($storageBaseUrl));
        }
        // Fallback: try to extract path after /storage/
        $pos = strpos($url, '/storage/');
        if ($pos !== false) {
            return substr($url, $pos + strlen('/storage/'));
        }
        return null;
    }
}
