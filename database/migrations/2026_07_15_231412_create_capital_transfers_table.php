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
        Schema::create('capital_transfers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('from_account_id')
                ->constrained('capital_accounts')
                ->restrictOnDelete();

            $table->foreignId('to_account_id')
                ->constrained('capital_accounts')
                ->restrictOnDelete();

            $table->decimal('source_amount', 15, 2);

            $table->decimal('destination_amount', 15, 2);

            $table->decimal('transfer_cost', 15, 2)->default(0);

            $table->decimal('exchange_rate', 15, 6);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_transfers');
    }
};
