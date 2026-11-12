<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pms_plans', function (Blueprint $table) {
            $table->text('decision_note')->nullable()->after('final_comment');
        });
    }

    public function down(): void
    {
        Schema::table('pms_plans', function (Blueprint $table) {
            $table->dropColumn('decision_note');
        });
    }
};
