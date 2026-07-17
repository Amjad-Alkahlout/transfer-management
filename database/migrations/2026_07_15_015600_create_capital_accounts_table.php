<?php

use App\Enums\Branch;
use App\Enums\CurrencyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_accounts', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('branch');

            $table->string('currency', 3);

            $table->decimal('balance', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('branch');
            $table->index('currency');
            $table->index(['branch', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_accounts');
    }
};
