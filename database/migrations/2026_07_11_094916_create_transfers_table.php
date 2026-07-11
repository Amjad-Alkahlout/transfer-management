<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('reference_number')->unique();

            // Sender & Receiver
            $table->string('sender_name');
            $table->string('sender_phone')->nullable();

            $table->string('receiver_name');
            $table->string('receiver_phone')->nullable();

            // Customer Request
            $table->decimal('requested_amount', 15, 2);
            $table->string('requested_currency', 3);

            $table->string('fee_mode');

            // Pricing
            $table->decimal('exchange_rate', 18, 6)->nullable();

            $table->decimal('commission_amount', 15, 2)->nullable();
            $table->string('commission_currency', 3)->nullable();

            $table->decimal('amount_due', 15, 2)->nullable();
            $table->string('due_currency', 3)->nullable();

            // Workflow
            $table->string('status')
                ->default('pending_pricing');

            // Execution
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('bank_accounts')
                ->restrictOnDelete();

            // Responsibility
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('priced_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            // Timeline
            $table->timestamp('priced_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Cancellation
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('cancellation_reason')->nullable();

            // Other
            $table->text('notes')->nullable();

            $table->timestamps();

            // Useful indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
