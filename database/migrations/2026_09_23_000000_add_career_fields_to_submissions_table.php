<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('cv_path')->nullable()->after('message');
            $table->string('cv_original_name')->nullable()->after('cv_path');
            $table->string('availability')->nullable()->after('cv_original_name');
            $table->string('experience_level')->nullable()->after('availability');
            $table->boolean('has_right_to_work')->nullable()->default(true)->after('experience_level');
            $table->boolean('has_driving_licence')->nullable()->default(false)->after('has_right_to_work');
        });
    }

    public function down(): void {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'cv_path',
                'cv_original_name',
                'availability',
                'experience_level',
                'has_right_to_work',
                'has_driving_licence',
            ]);
        });
    }
};
