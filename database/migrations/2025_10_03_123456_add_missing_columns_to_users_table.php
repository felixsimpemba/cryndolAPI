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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('users', 'fullName')) {
                $table->string('fullName')->after('id');
            }
            if (!Schema::hasColumn('users', 'phoneNumber')) {
                $table->string('phoneNumber')->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'acceptTerms')) {
                $table->boolean('acceptTerms')->default(false)->after('password');
            }
            
            // Remove old 'name' column if it exists and we have fullName
            if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'fullName')) {
                $table->dropColumn('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse the changes
            if (Schema::hasColumn('users', 'fullName')) {
                $table->dropColumn('fullName');
            }
            if (Schema::hasColumn('users', 'phoneNumber')) {
                $table->dropColumn('phoneNumber');
            }
            if (Schema::hasColumn('users', 'acceptTerms')) {
                $table->dropColumn('acceptTerms');
            }
            
            // Add back the 'name' column
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->after('id');
            }
        });
    }
};
