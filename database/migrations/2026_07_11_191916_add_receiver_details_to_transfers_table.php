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
        Schema::table('transfers', function (Blueprint $table) {

            $table->string('receiver_method', 20)
            ->after('requested_amount');

            $table->string('receiver_account_number')
                ->nullable()
                ->after('receiver_method');

            $table->string('receiver_wallet_phone')
                ->nullable()
                ->after('receiver_account_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {

            $table->dropColumn([
                'receiver_method',
                'receiver_account_number',
                'receiver_wallet_phone',
            ]);

        });
    }
};
