<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\AttendanceList;
use App\Models\AttendanceBreak;

class AttendanceDummySeeder extends Seeder
{
    public function run(): void
    {
        // ダミー対象ユーザー（user1, user2が居る前提。居なければ先頭ユーザー）
        $users = User::whereIn('email', ['user1@example.com', 'user2@example.com'])->get();
        if ($users->isEmpty()) {
            $users = User::query()->take(2)->get();
        }

        foreach ($users as $user) {
            // 直近10日分
            for ($i = 0; $i < 10; $i++) {
                $day = Carbon::today()->subDays($i);

                // 例：平日だけ作る（不要なら消してOK）
                // if ($day->isWeekend()) continue;

                // その日の出勤・退勤（適当にゆらぎを持たせる）
                $clockIn  = $day->copy()->setTime(9, rand(0, 20));          // 09:00〜09:20
                $clockOut = $day->copy()->setTime(18, rand(0, 20));         // 18:00〜18:20

                // attendance_lists（1ユーザー×1日で1行）
                $attendance = AttendanceList::updateOrCreate(
                    ['user_id' => $user->id, 'work_date' => $day->toDateString()],
                    [
                        'clock_in_at'  => $clockIn,
                        'clock_out_at' => $clockOut,
                        'note'         => null,
                    ]
                );

                // 既存休憩を消して入れ直し（updateOrCreateで日次再生成しても整合が崩れない）
                AttendanceBreak::where('attendance_list_id', $attendance->id)->delete();

                // 休憩を2回作る例（昼+夕方）
                $break1Start = $day->copy()->setTime(12, 0);
                $break1End   = $day->copy()->setTime(13, 0);

                $break2Start = $day->copy()->setTime(15, 0);
                $break2End   = $day->copy()->setTime(15, 15);

                AttendanceBreak::create([
                    'attendance_list_id' => $attendance->id,
                    'user_id'            => $user->id,
                    'start_at'           => $break1Start,
                    'end_at'             => $break1End,
                ]);

                AttendanceBreak::create([
                    'attendance_list_id' => $attendance->id,
                    'user_id'            => $user->id,
                    'start_at'           => $break2Start,
                    'end_at'             => $break2End,
                ]);
            }
        }
    }
}
