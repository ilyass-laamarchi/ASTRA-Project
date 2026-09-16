<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates persistent storage for public contact form submissions. */
return new class extends Migration
{
    /** Creates the inquiry fields and indexed workflow status. */
    public function up(): void
    {
        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    /** Removes contact inquiry storage on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
