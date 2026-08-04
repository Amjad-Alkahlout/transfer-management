<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('commission_rules');
    }

    public function down(): void
    {
        // القيمة الأصلية للجدول محذوفة نهائياً من الكود (الـ migration الأصلية انحذفت)،
        // فما فينا نرجعه تلقائياً لو عملنا rollback. لو احتجت الرجوع لاحقاً،
        // لازم ترجع تكتب بنية الجدول يدوياً هون.
    }
};
