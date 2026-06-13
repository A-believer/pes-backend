<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('quote_reference')->unique(); // QT-2026-000001

            // Customer
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone');

            // Location
            $table->string('address');
            $table->string('postcode');
            $table->string('city');

            // Property
            $table->string('property_type');
            $table->decimal('floor_area', 10, 2);
            $table->integer('floors')->default(1);

            // Service
            $table->string('service_type');
            $table->string('frequency');
            $table->date('preferred_date')->nullable();
            $table->string('urgency');

            // JSON payloads
            $table->json('property_details_json')->nullable(); // bedrooms, bathrooms, etc.
            $table->json('condition_json')->nullable();         // overallCondition, dirtLevel, specialConditions
            $table->json('addons_json')->nullable();            // array of add-on keys
            $table->json('photos_json')->nullable();            // array of image URLs

            // AI analysis (stored for audit)
            $table->json('ai_analysis_json')->nullable();

            // Pricing results
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('difficulty_multiplier', 4, 2)->nullable();
            $table->integer('confidence_score')->nullable();
            $table->decimal('quote_min', 10, 2)->nullable();
            $table->decimal('quote_max', 10, 2)->nullable();
            $table->integer('recommended_cleaners')->nullable();

            // Status & admin
            $table->string('status')->default('pending');
            // pending | reviewing | approved | rejected | converted_to_booking
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('quote_requests');
    }
};
