<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Jobs\AnalyzeQuoteJob;

class QuoteApiTest extends TestCase {
    use RefreshDatabase;

    private string $adminToken = 'SUPER_SECRET_ADMIN_TOKEN_HERE';

    protected function setUp(): void {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    private function validPayload(array $overrides = []): array {
        return array_merge([
            'propertyType'     => 'house',
            'floorArea'        => 120,
            'floors'           => 2,
            'bedrooms'         => 3,
            'bathrooms'        => 2,
            'serviceType'      => 'deep_cleaning',
            'frequency'        => 'one_time',
            'preferredDate'    => now()->addDays(3)->toDateString(),
            'urgency'          => 'flexible',
            'overallCondition' => 'good',
            'dirtLevel'        => 4,
            'specialConditions'=> [],
            'addOns'           => ['oven_cleaning'],
            'firstName'        => 'Jane',
            'lastName'         => 'Doe',
            'email'            => 'jane@example.com',
            'phone'            => '07700000000',
            'postcode'         => 'LS1 1AA',
            'address'          => '10 Test Street',
            'city'             => 'Leeds',
        ], $overrides);
    }

    private function makeImages(int $count = 3): array {
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = UploadedFile::fake()->create("photo{$i}.jpg", 100, 'image/jpeg');
        }
        return $images;
    }

    public function test_it_creates_a_quote_and_dispatches_analysis_job(): void {
        $payload = $this->validPayload();
        $payload['images'] = $this->makeImages(3);

        $response = $this->postJson('/api/quotes', $payload);

        $response->assertStatus(202)
                 ->assertJsonStructure(['reference', 'status', 'message'])
                 ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('quote_requests', [
            'email'        => 'jane@example.com',
            'service_type' => 'deep_cleaning',
            'status'       => 'pending',
        ]);

        Queue::assertPushed(AnalyzeQuoteJob::class);
    }

    public function test_it_rejects_fewer_than_3_images(): void {
        $payload = $this->validPayload();
        $payload['images'] = $this->makeImages(2);

        $this->postJson('/api/quotes', $payload)->assertStatus(422);
    }

    public function test_it_rejects_more_than_20_images(): void {
        $payload = $this->validPayload();
        $payload['images'] = $this->makeImages(21);

        $this->postJson('/api/quotes', $payload)->assertStatus(422);
    }

    public function test_it_rejects_invalid_property_type(): void {
        $payload = $this->validPayload(['propertyType' => 'castle']);
        $payload['images'] = $this->makeImages(3);

        $this->postJson('/api/quotes', $payload)->assertStatus(422);
    }

    public function test_customer_can_poll_quote_status(): void {
        $quote = QuoteRequest::create([
            'quote_reference' => 'QT-2026-000001',
            'customer_name'   => 'Jane Doe',
            'email'           => 'jane@example.com',
            'phone'           => '07700000000',
            'address'         => '10 Test St',
            'postcode'        => 'LS1 1AA',
            'city'            => 'Leeds',
            'property_type'   => 'house',
            'floor_area'      => 120,
            'floors'          => 2,
            'service_type'    => 'deep_cleaning',
            'frequency'       => 'one_time',
            'urgency'         => 'flexible',
            'status'          => 'reviewing',
            'quote_min'       => 200.00,
            'quote_max'       => 240.00,
            'estimated_hours' => 6.0,
            'recommended_cleaners' => 2,
        ]);

        $this->getJson('/api/quotes/QT-2026-000001')
             ->assertOk()
             ->assertJsonStructure(['reference', 'status', 'quoteMin', 'quoteMax', 'estimatedHours', 'cleaners']);
    }

    public function test_it_returns_404_for_unknown_reference(): void {
        $this->getJson('/api/quotes/QT-9999-999999')->assertStatus(404);
    }

    public function test_admin_can_list_quotes(): void {
        QuoteRequest::factory()->count(3)->create();

        $this->getJson('/api/admin/quotes', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()->assertJsonStructure(['data', 'total', 'per_page']);
    }

    public function test_admin_can_approve_a_reviewing_quote(): void {
        $quote = QuoteRequest::factory()->create(['status' => 'reviewing']);

        $this->postJson("/api/admin/quotes/{$quote->id}/approve", [], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('quote_requests', ['id' => $quote->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_a_quote(): void {
        $quote = QuoteRequest::factory()->create(['status' => 'reviewing']);

        $this->postJson("/api/admin/quotes/{$quote->id}/reject", ['admin_notes' => 'Out of area'], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()->assertJsonPath('status', 'rejected');
    }

    public function test_admin_can_convert_approved_quote_to_booking(): void {
        $quote = QuoteRequest::factory()->create(['status' => 'approved']);

        $this->postJson("/api/admin/quotes/{$quote->id}/convert", [], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()->assertJsonPath('status', 'converted_to_booking');
    }

    public function test_admin_can_fetch_analytics(): void {
        QuoteRequest::factory()->count(5)->create(['status' => 'approved']);
        QuoteRequest::factory()->count(2)->create(['status' => 'rejected']);

        $this->getJson('/api/admin/quotes-analytics', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()->assertJsonStructure([
            'total', 'approved', 'rejected', 'approval_rate', 'conversion_rate',
        ]);
    }

    public function test_unauthenticated_admin_request_is_rejected(): void {
        $this->getJson('/api/admin/quotes')->assertStatus(401);
    }
}
