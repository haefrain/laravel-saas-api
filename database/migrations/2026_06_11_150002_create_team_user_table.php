<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Denormalized mirror of the spatie team-scoped role, dual-written
            // only by Actions from a server-derived role — never request input.
            $table->string('membership_role', 20)->default('member');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'membership_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
