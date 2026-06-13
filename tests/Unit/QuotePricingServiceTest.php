<?php

namespace Tests\Unit;

use App\Services\QuotePricingService;
use Tests\TestCase;

class QuotePricingServiceTest extends TestCase {
    private QuotePricingService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new QuotePricingService();
    }

    private function baseAiResult(array $overrides = []): array {
        return array_merge([
            'cleanliness_score'    => 5,
            'difficulty_multiplier'=> 1.0,
            'estimated_hours'      => 4.0,
            'recommended_cleaners' => 1,
            'condition_summary'    => 'Test summary',
            'high_risk_areas'      => [],
            'confidence_score'     => 80,
        ], $overrides);
    }

    public function test_calculates_base_cost_correctly(): void {
        // 100 sqm × £2.50 (regular_cleaning) × 1.0 multiplier × no urgency = £250
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 100, 'urgency' => 'flexible', 'addons' => []];
        $ai   = $this->baseAiResult();

        $result = $this->service->calculate($data, $ai);

        $this->assertEquals(225.00, $result['quote_min']); // 250 * 0.9
        $this->assertEquals(275.00, $result['quote_max']); // 250 * 1.1
    }

    public function test_applies_difficulty_multiplier(): void {
        // 100 sqm × £2.50 × 2.0 multiplier = £500
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 100, 'urgency' => 'flexible', 'addons' => []];
        $ai   = $this->baseAiResult(['difficulty_multiplier' => 2.0]);

        $result = $this->service->calculate($data, $ai);

        $this->assertEquals(450.00, $result['quote_min']); // 500 * 0.9
        $this->assertEquals(550.00, $result['quote_max']); // 500 * 1.1
    }

    public function test_applies_same_day_urgency_surcharge(): void {
        // 100 sqm × £2.50 × 1.0 × 1.30 (same_day) = £325
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 100, 'urgency' => 'same_day', 'addons' => []];
        $ai   = $this->baseAiResult();

        $result = $this->service->calculate($data, $ai);

        $this->assertEquals(292.50, $result['quote_min']); // 325 * 0.9
        $this->assertEquals(357.50, $result['quote_max']); // 325 * 1.1
    }

    public function test_adds_addon_costs(): void {
        // 100 sqm × £2.50 = £250 + £40 (oven) + £25 (fridge) = £315
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 100, 'urgency' => 'flexible', 'addons' => ['oven_cleaning', 'fridge_cleaning']];
        $ai   = $this->baseAiResult();

        $result = $this->service->calculate($data, $ai);

        $this->assertEquals(283.50, $result['quote_min']); // 315 * 0.9
        $this->assertEquals(346.50, $result['quote_max']); // 315 * 1.1
    }

    public function test_enforces_minimum_quote(): void {
        // Very small job: 1 sqm × £2.50 = £2.50 → floored to £50
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 1, 'urgency' => 'flexible', 'addons' => []];
        $ai   = $this->baseAiResult();

        $result = $this->service->calculate($data, $ai);

        $this->assertGreaterThanOrEqual(45.00, $result['quote_min']); // min floor applied
    }

    public function test_clamps_difficulty_multiplier_to_safe_range(): void {
        // Multiplier of 99 should be clamped to 3.0
        $data = ['service_type' => 'regular_cleaning', 'floor_area' => 100, 'urgency' => 'flexible', 'addons' => []];
        $ai   = $this->baseAiResult(['difficulty_multiplier' => 99.0]);

        $result = $this->service->calculate($data, $ai);

        // 100 × £2.50 × 3.0 = £750 → min: £675, max: £825
        $this->assertEquals(675.00, $result['quote_min']);
        $this->assertEquals(825.00, $result['quote_max']);
    }

    public function test_uses_fallback_rate_for_unknown_service(): void {
        $data = ['service_type' => 'unknown_service', 'floor_area' => 100, 'urgency' => 'flexible', 'addons' => []];
        $ai   = $this->baseAiResult();

        $result = $this->service->calculate($data, $ai);

        // Should not throw — uses fallback rate of £3.00 per sqm
        $this->assertIsFloat($result['quote_min']);
        $this->assertIsFloat($result['quote_max']);
        $this->assertGreaterThan(0, $result['quote_min']);
    }
}
