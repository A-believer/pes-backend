<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Jobs\AnalyzeQuoteJob;
use App\Models\QuoteImage;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class QuoteController extends Controller {
    /**
     * POST /api/quotes
     *
     * Creates a quote request, stores images, and dispatches the AI analysis job.
     * Returns the reference immediately — the client polls GET /api/quotes/{reference}
     * for the final price range once the job completes.
     */
    public function store(StoreQuoteRequest $request): JsonResponse {
        $v = $request->validated();

        // --- Store uploaded images ---
        $imageUrls   = [];
        $storagePaths = [];
        foreach ($request->file('images') as $file) {
            $path = $file->store('quotes', 'public');
            $url  = Storage::disk('public')->url($path);
            $imageUrls[]    = $url;
            $storagePaths[] = $path;
        }

        // --- Create quote record ---
        $quote = QuoteRequest::create([
            'quote_reference'      => QuoteRequest::generateReference(),
            'customer_name'        => trim($v['firstName'] . ' ' . $v['lastName']),
            'email'                => $v['email'],
            'phone'                => $v['phone'],
            'address'              => $v['address'],
            'postcode'             => strtoupper($v['postcode']),
            'city'                 => $v['city'],
            'property_type'        => $v['propertyType'],
            'floor_area'           => $v['floorArea'],
            'floors'               => $v['floors'],
            'service_type'         => $v['serviceType'],
            'frequency'            => $v['frequency'],
            'preferred_date'       => $v['preferredDate'] ?? null,
            'urgency'              => $v['urgency'],
            'property_details_json' => array_filter([
                'bedrooms'    => $v['bedrooms']    ?? null,
                'bathrooms'   => $v['bathrooms']   ?? null,
                'kitchens'    => $v['kitchens']    ?? null,
                'livingRooms' => $v['livingRooms'] ?? null,
                'officeRooms' => $v['officeRooms'] ?? null,
                'meetingRooms'=> $v['meetingRooms']?? null,
                'toilets'     => $v['toilets']     ?? null,
            ], fn($v) => !is_null($v)),
            'condition_json'  => [
                'overallCondition'  => $v['overallCondition'],
                'dirtLevel'         => $v['dirtLevel'],
                'specialConditions' => $v['specialConditions'] ?? [],
            ],
            'addons_json'    => $v['addOns'] ?? [],
            'photos_json'    => $imageUrls,
            'status'         => 'pending',
        ]);

        // --- Persist image records ---
        foreach ($imageUrls as $url) {
            QuoteImage::create([
                'quote_request_id' => $quote->id,
                'image_url'        => $url,
                'created_at'       => now(),
            ]);
        }

        // --- Dispatch AI analysis job to queue ---
        AnalyzeQuoteJob::dispatch($quote->id);

        return response()->json([
            'reference' => $quote->quote_reference,
            'status'    => 'pending',
            'message'   => 'Your quote request has been received and is being analysed. Please poll the status endpoint.',
        ], 202);
    }

    /**
     * GET /api/quotes/{reference}
     *
     * Public status endpoint — customers poll this after submitting.
     * Returns safe, non-admin fields only.
     */
    public function show(string $reference): JsonResponse {
        $quote = QuoteRequest::where('quote_reference', $reference)->first();

        if (!$quote) {
            return response()->json(['error' => 'Quote not found.'], 404);
        }

        $data = [
            'reference'   => $quote->quote_reference,
            'status'      => $quote->status,
            'createdAt'   => $quote->created_at,
        ];

        // Only expose pricing once analysis is complete
        if ($quote->isAnalysisComplete()) {
            $data['quoteMin']          = (float) $quote->quote_min;
            $data['quoteMax']          = (float) $quote->quote_max;
            $data['estimatedHours']    = (float) $quote->estimated_hours;
            $data['cleaners']          = $quote->recommended_cleaners;
            $data['conditionSummary']  = $quote->ai_analysis_json['condition_summary'] ?? null;
        }

        return response()->json($data);
    }
}
