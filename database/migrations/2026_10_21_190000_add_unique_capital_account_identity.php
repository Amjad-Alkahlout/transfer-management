<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capital_accounts', function (Blueprint $table): void {
            $table->unique(['branch', 'currency', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::table('capital_accounts', function (Blueprint $table): void {
            $table->dropUnique(['branch', 'currency', 'account_type']);
        });
    }
};
