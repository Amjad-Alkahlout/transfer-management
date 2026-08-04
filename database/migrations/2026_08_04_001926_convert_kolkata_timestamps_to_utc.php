<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert legacy datetime values that were stored in Asia/Kolkata
     * to UTC after migrating the application to UTC.
     *
     * This migration should run only once on existing production data.
     */
    protected array $columns = [
        'transfers' => [
            'created_at',
            'completed_at',
            'cancelled_at',
            'updated_at',
        ],

        'payments' => [
            'created_at',
            'received_at',
            'updated_at',
        ],

        'capital_accounts' => [
            'created_at',
            'updated_at',
        ],

        'capital_transactions' => [
            'created_at',
            'updated_at',
        ],

        'capital_transfers' => [
            'created_at',
            'updated_at',
        ],

        'commission_rules' => [
            'created_at',
            'updated_at',
        ],

        'exchange_rates' => [
            'created_at',
            'updated_at',
        ],

        'profit_account_transactions' => [
            'created_at',
            'updated_at',
        ],

        'users' => [
            'created_at',
            'updated_at',
        ],
    ];

    public function up(): void
    {
        $this->convertUsing('DATE_SUB');
    }

    public function down(): void
    {
        $this->convertUsing('DATE_ADD');
    }

    private function convertUsing(string $function): void
    {
        foreach ($this->columns as $table => $columns) {

            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {

                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::statement("
                    UPDATE `{$table}`
                    SET `{$column}` = {$function}(`{$column}`, INTERVAL 330 MINUTE)
                    WHERE `{$column}` IS NOT NULL
                ");
            }
        }
    }
};
