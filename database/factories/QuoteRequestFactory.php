<?php

namespace Database\Factories;

use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteRequestFactory extends Factory {
    protected $model = QuoteRequest::class;

    public function definition(): array {
        static $counter = 0;
        $counter++;
        $year = now()->year;

        return [
            'quote_reference'      => sprintf('QT-%d-%06d', $year, $counter),
            'customer_name'        => $this->faker->name(),
            'email'                => $this->faker->safeEmail(),
            'phone'                => $this->faker->phoneNumber(),
            'address'              => $this->faker->streetAddress(),
            'postcode'             => 'LS1 1AA',
            'city'                 => $this->faker->city(),
            'property_type'        => $this->faker->randomElement(['house', 'apartment', 'office']),
            'floor_area'           => $this->faker->numberBetween(50, 500),
            'floors'               => $this->faker->numberBetween(1, 5),
            'service_type'         => $this->faker->randomElement(['regular_cleaning', 'deep_cleaning', 'end_of_tenancy']),
            'frequency'            => 'one_time',
            'urgency'              => 'flexible',
            'status'               => 'pending',
            'condition_json'       => ['overallCondition' => 'good', 'dirtLevel' => 4, 'specialConditions' => []],
            'addons_json'          => [],
            'photos_json'          => [],
            'quote_min'            => $this->faker->randomFloat(2, 100, 500),
            'quote_max'            => $this->faker->randomFloat(2, 500, 1000),
            'estimated_hours'      => $this->faker->randomFloat(1, 2, 16),
            'recommended_cleaners' => $this->faker->numberBetween(1, 4),
        ];
    }
}
