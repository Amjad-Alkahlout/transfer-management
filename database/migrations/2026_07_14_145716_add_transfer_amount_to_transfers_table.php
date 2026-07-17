<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->decimal('transfer_amount', 12, 2)
                ->after('requested_amount');

            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->dropColumn('transfer_amount');

        });
    }
};
