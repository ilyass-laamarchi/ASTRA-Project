<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Framework support table used by Laravel Sanctum bearer tokens. */
return new class extends Migration
{
    /** Creates Sanctum's hashed bearer-token table. */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /** Removes Sanctum token storage on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
