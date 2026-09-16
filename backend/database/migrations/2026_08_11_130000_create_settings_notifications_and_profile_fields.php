<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds profile media, preferences, agency settings, and private notifications. */
return new class extends Migration
{
    /** Extends users and creates settings and notification tables. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('phone');
            $table->json('notification_preferences')->nullable()->after('avatar_path');
        });

        Schema::create('application_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('astra_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    /** Removes the added tables and user profile columns on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('astra_notifications');
        Schema::dropIfExists('application_settings');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'notification_preferences']);
        });
    }
};
