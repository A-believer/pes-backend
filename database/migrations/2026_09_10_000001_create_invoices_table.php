<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['invoice', 'receipt'])->default('invoice')->index();
            $table->string('document_number', 50)->unique();
            $table->foreignId('quote_request_id')->nullable()->constrained('quote_requests')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            
            // Client details
            $table->string('customer_name', 150);
            $table->string('customer_email', 150)->index();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_company', 150)->nullable();
            $table->text('billing_address');
            $table->text('service_address')->nullable();
            $table->string('service_type', 100);
            
            // Work & timeline details
            $table->enum('work_status', ['work_to_be_done', 'work_done'])->default('work_to_be_done');
            $table->date('work_scheduled_date')->nullable();
            $table->date('work_completed_date')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            
            // Status & Currency
            $table->enum('status', ['draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft')->index();
            $table->string('currency', 10)->default('GBP');
            
            // Financial Breakdown
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(20.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('balance_due', 10, 2)->default(0.00);
            
            // Metadata & Terms
            $table->json('bank_details_json')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            
            // Email Tracking
            $table->timestamp('email_sent_at')->nullable();
            $table->string('email_last_sent_to', 150)->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoices');
    }
};
