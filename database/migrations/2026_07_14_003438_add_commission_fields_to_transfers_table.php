<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->decimal('commission_amount', 12, 2)
                ->after('customer_payable_currency');

            $table->string('commission_currency', 3)
                ->after('commission_amount');

        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->dropColumn([
                'commission_amount',
                'commission_currency',
            ]);

        });
    }
};
