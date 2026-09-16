<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Builds ASTRA's six-table business domain (users is created separately). */
return new class extends Migration
{
    /** Creates categories, cars, images, reservations, and payments with foreign keys. */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('cars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('registration_number')->unique();
            $table->string('brand')->index();
            $table->string('model')->index();
            $table->unsignedSmallInteger('year');
            $table->string('color');
            $table->unsignedTinyInteger('seats');
            $table->unsignedTinyInteger('doors');
            $table->enum('fuel_type', ['gasoline', 'diesel', 'hybrid', 'electric']);
            $table->enum('transmission', ['manual', 'automatic']);
            $table->decimal('daily_price', 10, 2)->index();
            $table->unsignedInteger('mileage')->default(0);
            $table->text('description')->nullable();
            $table->enum('operational_status', ['available', 'maintenance', 'unavailable'])->default('available')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('car_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['car_id', 'sort_order']);
        });
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reservation_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->unsignedInteger('rental_days');
            $table->decimal('daily_price', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'])->default('pending')->index();
            $table->text('client_message')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->index(['car_id', 'status', 'start_date', 'end_date']);
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('payment_reference')->unique();
            $table->string('provider')->default('stripe');
            $table->string('provider_session_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('MAD');
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['reservation_id', 'user_id']);
        });
    }

    /** Drops business tables in reverse dependency order on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('car_images');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('categories');
    }
};
