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
        // Use raw SQL to modify enum since Doctrine DBAL can struggle with enums
        DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('pending', 'submitted', 'approved', 'rejected', 'active', 'paid', 'completed', 'defaulted', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original list if needed
        DB::statement("ALTER TABLE loans MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'defaulted', 'cancelled') NOT NULL DEFAULT 'active'");
    }
};
