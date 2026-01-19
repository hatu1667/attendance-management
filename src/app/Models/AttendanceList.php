<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceList extends Model
{
    protected $table = 'attendance_lists';

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'break_start',
        'break_end',
        'break2_start',
        'break2_end',
        'note',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
        'break2_start' => 'datetime',
        'break2_end'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_list_id');
    }

    public function openBreak(): ?AttendanceBreak
    {
        return $this->breaks()->whereNull('end_at')->latest('start_at')->first();
    }

    public function breakPairsFormatted(): array
    {
        $pairs = [];

        foreach ($this->breaks()->orderBy('start_at')->get() as $b) {
            $pairs[] = [
                'start' => optional($b->start_at)->format('H:i'),
                'end'   => optional($b->end_at)->format('H:i'),
            ];
        }

        if ($this->break_start || $this->break_end) {
            $pairs[] = [
                'start' => optional($this->break_start)->format('H:i'),
                'end'   => optional($this->break_end)->format('H:i'),
            ];
        }

        if ($this->break2_start || $this->break2_end) {
            $pairs[] = [
                'start' => optional($this->break2_start)->format('H:i'),
                'end'   => optional($this->break2_end)->format('H:i'),
            ];
        }

        return $pairs;
    }

    public function breakMinutes(): int
    {
        $mins = 0;

        foreach ($this->breaks as $b) {
            if ($b->start_at && $b->end_at) {
                $mins += max(0, $b->end_at->diffInMinutes($b->start_at));
            }
        }

        if ($this->break_start && $this->break_end) {
            $mins += max(0, $this->break_end->diffInMinutes($this->break_start));
        }

        if ($this->break2_start && $this->break2_end) {
            $mins += max(0, $this->break2_end->diffInMinutes($this->break2_start));
        }

        return $mins;
    }

    public function totalWorkedMinutes(): ?int
    {
        if (!$this->clock_in_at || !$this->clock_out_at) return null;

        $total = $this->clock_out_at->diffInMinutes($this->clock_in_at);
        $total -= $this->breakMinutes();

        return max(0, $total);
    }
}
