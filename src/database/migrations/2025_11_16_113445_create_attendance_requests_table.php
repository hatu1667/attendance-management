<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->constrained('attendance_lists')->cascadeOnDelete();

            $table->date('target_date');
            $table->string('type')->default('modify');

            $table->string('status')->default('pending');
            $table->dateTime('applied_at')->nullable();
            $table->string('before_clock_in_at', 5)->nullable();
            $table->string('before_clock_out_at', 5)->nullable();
            $table->string('after_clock_in_at', 5)->nullable();
            $table->string('after_clock_out_at', 5)->nullable();

            $table->json('before_breaks')->nullable();
            $table->json('after_breaks')->nullable();

            $table->string('before_note', 255)->nullable();
            $table->string('after_note', 255)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'attendance_id']);
            $table->index(['status', 'applied_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
