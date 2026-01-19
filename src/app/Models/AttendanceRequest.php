<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class AttendanceRequest extends Model
{
    protected $table = 'attendance_requests';

    protected $fillable = [
        'user_id',
        'attendance_id',
        'target_date',
        'type',

        'before_clock_in_at',
        'before_clock_out_at',
        'before_breaks',
        'before_note',

        'after_clock_in_at',
        'after_clock_out_at',
        'after_breaks',
        'after_note',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'target_date'   => 'date',
        'before_breaks' => 'array',
        'after_breaks'  => 'array',
        'applied_at'    => 'datetime',
    ];

    // 関連
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(AttendanceList::class, 'attendance_id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $m->after_note = is_string($m->after_note) ? trim($m->after_note) : $m->after_note;
            $m->before_note = is_string($m->before_note) ? trim($m->before_note) : $m->before_note;
        });
    }
}
