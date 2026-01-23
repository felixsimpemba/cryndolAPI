<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('logo_url')->nullable();
            $table->string('tagline')->nullable();
            $table->string('primary_color')->default('#0F172A'); // Slate-900
            $table->string('secondary_color')->default('#10B981'); // Emerald-500
            $table->string('currency_code')->default('ZMW');
            $table->string('locale')->default('en-ZM');
            $table->string('timezone')->default('Africa/Lusaka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            //
        });
    }
};
