<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() || auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        // 余計な空白除去
        $this->merge([
            'after_clock_in_at'  => is_string($this->input('after_clock_in_at'))  ? trim($this->input('after_clock_in_at'))  : $this->input('after_clock_in_at'),
            'after_clock_out_at' => is_string($this->input('after_clock_out_at')) ? trim($this->input('after_clock_out_at')) : $this->input('after_clock_out_at'),
            'after_note'         => is_string($this->input('after_note'))         ? trim($this->input('after_note'))         : $this->input('after_note'),
        ]);
    }

    public function rules(): array
    {
        return [
            'attendance_id'      => ['required', 'integer'],

            // 時刻として入力されている前提
            'after_clock_in_at'  => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'after_clock_out_at' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],

            // 休憩は配列。空行はOK（後で判定）
            'after_breaks'               => ['nullable', 'array'],
            'after_breaks.*.start'       => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'after_breaks.*.end'         => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],

            // ✅ 備考は必須（あなたの要件4）
            'after_note'         => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'after_clock_in_at.regex'  => '出勤時間の形式が正しくありません',
            'after_clock_out_at.regex' => '退勤時間の形式が正しくありません',
            'after_breaks.*.start.regex' => '休憩時間の形式が正しくありません',
            'after_breaks.*.end.regex'   => '休憩時間の形式が正しくありません',
            'after_note.required'      => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {

            // ---- 時刻を「分」に変換する関数（比較用）----
            $toMin = function ($hhmm) {
                if (!is_string($hhmm) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hhmm)) {
                    return null;
                }
                [$h, $m] = array_map('intval', explode(':', $hhmm));
                return $h * 60 + $m;
            };

            $in  = $toMin($this->input('after_clock_in_at'));
            $out = $toMin($this->input('after_clock_out_at'));

            // =========================================================
            // 1) 出勤と退勤の前後関係
            // 出勤 > 退勤 OR 退勤 < 出勤 ならエラー
            // =========================================================
            if (!is_null($in) && !is_null($out) && $in > $out) {
                // ✅ Bladeで @error('after_clock_in_at') / @error('after_clock_out_at') で拾えるように同じキーに付与
                $msg = '出勤時間もしくは退勤時間が不適切な値です';
                $v->errors()->add('after_clock_in_at', $msg);
                $v->errors()->add('after_clock_out_at', $msg);
            }

            // 休憩配列を取得（空行も来る）
            $breaks = (array)($this->input('after_breaks') ?? []);

            foreach ($breaks as $i => $b) {
                $b = (array)$b;
                $bs = $toMin($b['start'] ?? null);
                $be = $toMin($b['end'] ?? null);

                // startもendも空ならスキップ（追加行など）
                if (is_null($bs) && is_null($be)) {
                    continue;
                }

                // =========================================================
                // 2) 休憩開始が「出勤より前」または「退勤より後」
                // =========================================================
                if (!is_null($bs)) {
                    if (!is_null($in) && $bs < $in) {
                        $v->errors()->add("after_breaks.$i.start", '休憩時間が不適切な値です');
                    }
                    if (!is_null($out) && $bs > $out) {
                        $v->errors()->add("after_breaks.$i.start", '休憩時間が不適切な値です');
                    }
                }

                // =========================================================
                // 3) 休憩終了が「退勤より後」
                // =========================================================
                if (!is_null($be) && !is_null($out) && $be > $out) {
                    $v->errors()->add("after_breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                }

                // おまけ：休憩 start > end の場合（仕様に無いけど普通は必要）
                if (!is_null($bs) && !is_null($be) && $bs > $be) {
                    $v->errors()->add("after_breaks.$i.start", '休憩時間が不適切な値です');
                    $v->errors()->add("after_breaks.$i.end", '休憩時間が不適切な値です');
                }
            }
        });
    }
}
