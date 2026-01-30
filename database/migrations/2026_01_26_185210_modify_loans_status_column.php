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
        Schema::table('loans', function (Blueprint $table) {
            // Using raw statement to modify enum is often more reliable
            DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'defaulted', 'cancelled', 'approved', 'rejected', 'closed') DEFAULT 'active'");
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'defaulted', 'cancelled') DEFAULT 'active'");
        });
    }
};
