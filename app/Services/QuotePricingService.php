<?php

namespace App\Services;

class QuotePricingService {
    /**
     * Calculate the quote price range from validated form data and AI assessment.
     *
     * The AI provides the difficulty_multiplier. All other pricing logic is
     * pure PHP business rules — never derived from AI output.
     *
     * @param  array  $data      Validated quote form fields
     * @param  array  $aiResult  Validated AI assessment (cleanliness, difficulty, hours, etc.)
     * @return array             ['quote_min', 'quote_max']
     */
    public function calculate(array $data, array $aiResult): array {
        $serviceType = $data['service_type'];
        $floorArea   = (float) $data['floor_area'];
        $urgency     = $data['urgency'];
        $addons      = $data['addons'] ?? [];

        // --- Step 1: Base cost = floor area × service rate ---
        $serviceRates = config('quotes.service_rates');
        $serviceRate  = $serviceRates[$serviceType] ?? 3.00; // safe fallback
        $baseCost     = $floorArea * $serviceRate;

        // --- Step 2: Apply AI difficulty multiplier (clamped 1.0–3.0) ---
        $difficultyMultiplier = max(1.0, min(3.0, (float)($aiResult['difficulty_multiplier'] ?? 1.0)));
        $adjustedCost         = $baseCost * $difficultyMultiplier;

        // --- Step 3: Urgency surcharge ---
        $urgencySurcharges = config('quotes.urgency_surcharges');
        $urgencySurcharge  = $urgencySurcharges[$urgency] ?? 0.0;
        $adjustedCost      = $adjustedCost * (1 + $urgencySurcharge);

        // --- Step 4: Add-on flat-rate costs ---
        $addonPrices = config('quotes.addon_prices');
        $addonTotal  = 0;
        foreach ($addons as $addon) {
            $addonTotal += $addonPrices[$addon] ?? 0;
        }
        $totalCost = $adjustedCost + $addonTotal;

        // --- Step 5: Apply minimum floor ---
        $minimumQuote = config('quotes.minimum_quote', 50.00);
        $totalCost    = max($totalCost, $minimumQuote);

        // --- Step 6: Generate ±margin range ---
        $margin   = config('quotes.quote_range_margin', 0.10);
        $quoteMin = round($totalCost * (1 - $margin), 2);
        $quoteMax = round($totalCost * (1 + $margin), 2);

        return [
            'quote_min' => $quoteMin,
            'quote_max' => $quoteMax,
        ];
    }
}
