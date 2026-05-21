<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'contact' or 'review'
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('service')->nullable();
            $table->string('postcode')->nullable();
            $table->integer('rating')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('submissions');
    }
};
