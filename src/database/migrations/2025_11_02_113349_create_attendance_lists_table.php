<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_lists', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('work_date');

            // 打刻
            $table->dateTime('clock_in_at')->nullable();   // 出勤
            $table->dateTime('clock_out_at')->nullable();  // 退勤

            // 休憩1
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();

            // 休憩2
            $table->dateTime('break2_start')->nullable();
            $table->dateTime('break2_end')->nullable();

            // 備考
            $table->string('note')->nullable();

            $table->timestamps();

            // 同一ユーザー・同一日の重複を禁止
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_lists');
    }
};
