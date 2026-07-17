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


            $table->string('receiver_name');

            // Customer Request
            $table->decimal('requested_amount', 15, 2);
            $table->string('requested_currency', 3);

            $table->string('fee_mode');

            // Workflow
            $table->string('status')
                ->default('pending');


            // Responsibility
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();


            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            // Timeline
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Cancellation
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('cancellation_reason')->nullable();
            $table->string('transfer_proof_path')->nullable();

            // Other
            $table->text('notes')->nullable();
            $table->string('calculation_mode');

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
