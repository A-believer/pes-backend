<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('landing_page_slug')->nullable()->after('type');
            $table->string('utm_source')->nullable()->after('message');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('landing_page_slug')->nullable()->after('city');
            $table->string('utm_source')->nullable()->after('admin_notes');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
        });
    }

    public function down(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['landing_page_slug', 'utm_source', 'utm_medium', 'utm_campaign']);
        });

        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['landing_page_slug', 'utm_source', 'utm_medium', 'utm_campaign']);
        });
    }
};
