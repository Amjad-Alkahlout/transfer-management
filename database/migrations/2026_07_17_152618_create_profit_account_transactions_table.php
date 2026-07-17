<?php

use App\Enums\CurrencyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_account_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('currency', 3);

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_account_transactions');
    }
};
