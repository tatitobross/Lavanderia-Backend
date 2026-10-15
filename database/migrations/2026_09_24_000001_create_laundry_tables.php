<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150); $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable()->unique(); $table->string('address')->nullable();
            $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('garment_types', function (Blueprint $table) {
            $table->id(); $table->string('name', 100)->unique();
            $table->string('description')->nullable(); $table->timestamps();
        });
        Schema::create('laundry_services', function (Blueprint $table) {
            $table->id(); $table->string('name', 100)->unique();
            $table->enum('pricing_mode', ['weight', 'unit']); $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('estimated_days')->default(2); $table->string('description')->nullable();
            $table->string('status')->default('active'); $table->timestamps();
        });
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id(); $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('garment_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('laundry_service_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('quantity', 8, 2); $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2); $table->dateTime('estimated_at');
            $table->dateTime('completed_at')->nullable(); $table->string('status')->default('received');
            $table->text('notes')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('laundry_orders'); Schema::dropIfExists('laundry_services'); Schema::dropIfExists('garment_types'); Schema::dropIfExists('customers'); }
};
