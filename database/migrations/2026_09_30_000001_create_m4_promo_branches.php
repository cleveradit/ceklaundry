<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_branches', function (Blueprint $table) {
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete()->restrictOnUpdate();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete()->restrictOnUpdate();
            $table->primary(['promo_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_branches');
    }
};
