<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('capital_account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('amount', 15, 2);

            $table->string('direction', 10);

            $table->string('transaction_type');

            $table->decimal('balance_before', 15, 2);

            $table->decimal('balance_after', 15, 2);

            $table->nullableMorphs('reference');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('direction');
            $table->index('transaction_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_transactions');
    }
};
