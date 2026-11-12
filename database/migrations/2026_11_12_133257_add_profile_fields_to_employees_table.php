<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('salary');
            $table->string('phone')->nullable()->after('photo');
            $table->string('city')->nullable()->after('phone');
            $table->string('address')->nullable()->after('city');
            $table->string('emergency_contact')->nullable()->after('address');
            $table->text('bio')->nullable()->after('emergency_contact');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'photo',
                'phone',
                'city',
                'address',
                'emergency_contact',
                'bio',
            ]);
        });
    }
};
