<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->decimal('customer_payable_amount', 12, 2)
                ->after('requested_amount');

            $table->string('customer_payable_currency', 3)
                ->after('customer_payable_amount');

        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->dropColumn([
                'customer_payable_amount',
                'customer_payable_currency',
            ]);

        });
    }
};
