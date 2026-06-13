<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteAdminController extends Controller {
    /**
     * GET /api/admin/quotes
     * Paginated list with filters and search.
     */
    public function index(Request $request): JsonResponse {
        $query = QuoteRequest::with('images')->latest();

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_reference', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($serviceType = $request->query('service_type')) {
            $query->where('service_type', $serviceType);
        }
        if ($propertyType = $request->query('property_type')) {
            $query->where('property_type', $propertyType);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $quotes  = $query->paginate($perPage);

        return response()->json($quotes);
    }

    /**
     * GET /api/admin/quotes/{id}
     * Full detail view for admin.
     */
    public function show(int $id): JsonResponse {
        $quote = QuoteRequest::with('images')->find($id);

        if (!$quote) {
            return response()->json(['error' => 'Quote not found.'], 404);
        }

        return response()->json($quote);
    }

    /**
     * PATCH /api/admin/quotes/{id}
     * Update admin notes or manual price override.
     */
    public function update(Request $request, int $id): JsonResponse {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['error' => 'Quote not found.'], 404);
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
            'quote_min'   => 'nullable|numeric|min:0',
            'quote_max'   => 'nullable|numeric|min:0',
        ]);

        $quote->update($validated);

        return response()->json(['message' => 'Quote updated.', 'quote' => $quote->fresh()]);
    }

    /**
     * POST /api/admin/quotes/{id}/approve
     */
    public function approve(int $id): JsonResponse {
        return $this->transition($id, 'approved', ['pending', 'reviewing']);
    }

    /**
     * POST /api/admin/quotes/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $quote = QuoteRequest::find($id);
        if (!$quote) {
            return response()->json(['error' => 'Quote not found.'], 404);
        }

        if (!in_array($quote->status, ['pending', 'reviewing', 'approved'])) {
            return response()->json(['error' => "Cannot reject a quote with status '{$quote->status}'."], 422);
        }

        $quote->update([
            'status'      => 'rejected',
            'admin_notes' => $validated['admin_notes'] ?? $quote->admin_notes,
        ]);

        return response()->json(['message' => 'Quote rejected.', 'status' => 'rejected']);
    }

    /**
     * POST /api/admin/quotes/{id}/convert
     * Convert approved quote to booking.
     */
    public function convert(int $id): JsonResponse {
        return $this->transition($id, 'converted_to_booking', ['approved']);
    }

    /**
     * GET /api/admin/quotes-analytics
     * Summary analytics for the admin dashboard.
     */
    public function analytics(): JsonResponse {
        $total   = QuoteRequest::count();
        $pending = QuoteRequest::where('status', 'pending')->count();

        $approved   = QuoteRequest::where('status', 'approved')->count();
        $rejected   = QuoteRequest::where('status', 'rejected')->count();
        $converted  = QuoteRequest::where('status', 'converted_to_booking')->count();
        $reviewing  = QuoteRequest::where('status', 'reviewing')->count();

        $approvalRate   = $total > 0 ? round(($approved + $converted) / $total * 100, 1) : 0;
        $conversionRate = ($approved + $converted) > 0
            ? round($converted / ($approved + $converted) * 100, 1)
            : 0;

        $avgQuoteMin = QuoteRequest::whereNotNull('quote_min')->avg('quote_min');
        $avgQuoteMax = QuoteRequest::whereNotNull('quote_max')->avg('quote_max');

        $byServiceType   = QuoteRequest::selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')->pluck('count', 'service_type');
        $byPropertyType  = QuoteRequest::selectRaw('property_type, COUNT(*) as count')
            ->groupBy('property_type')->pluck('count', 'property_type');

        $recentVolume = QuoteRequest::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'total'           => $total,
            'pending'         => $pending,
            'reviewing'       => $reviewing,
            'approved'        => $approved,
            'rejected'        => $rejected,
            'converted'       => $converted,
            'approval_rate'   => $approvalRate,
            'conversion_rate' => $conversionRate,
            'avg_quote_min'   => round($avgQuoteMin ?? 0, 2),
            'avg_quote_max'   => round($avgQuoteMax ?? 0, 2),
            'by_service_type' => $byServiceType,
            'by_property_type'=> $byPropertyType,
            'recent_volume'   => $recentVolume,
        ]);
    }

    private function transition(int $id, string $newStatus, array $allowedFromStatuses): JsonResponse {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['error' => 'Quote not found.'], 404);
        }

        if (!in_array($quote->status, $allowedFromStatuses)) {
            return response()->json([
                'error' => "Cannot transition from '{$quote->status}' to '{$newStatus}'."
            ], 422);
        }

        $quote->update(['status' => $newStatus]);

        return response()->json(['message' => "Quote {$newStatus}.", 'status' => $newStatus]);
    }
}
