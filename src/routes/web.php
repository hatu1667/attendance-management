<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RequestController;

// 管理者
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\ApproveController;

/* =========================
 | Public（一般ユーザー）
 * =======================*/

Route::get('/register',  [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.store');

Route::get('/login',  [LoginController::class, 'show'])->name('login.show');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');


/* =========================
 | Authenticated（一般）
 * =======================*/
Route::middleware('auth')->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');

    Route::get('/attendance/{date}', [AttendanceController::class, 'show'])
        ->where('date', '\d{4}-\d{2}-\d{2}')
        ->name('attendance.show');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])
        ->whereNumber('id')
        ->name('attendance.detail');

    Route::post('/attendance/clock-in',    [AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
    Route::post('/attendance/clock-out',   [AttendanceController::class, 'clockOut'])->name('attendance.clock_out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break_start');
    Route::post('/attendance/break-end',   [AttendanceController::class, 'breakEnd'])->name('attendance.break_end');

    // 申請作成（一般）
    Route::post('/stamp_correction_request', [RequestController::class, 'store'])->name('requests.store');
});

// 申請一覧
Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])
    ->name('requests.index')
    ->middleware('auth.any');


/* =========================
 | Admin（管理者）
 * =======================*/
Route::prefix('admin')->name('admin.')->group(function () {

    // 管理者ログイン（※ login.show と衝突させない）
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');

    // ここから先は管理者ログイン必須
    Route::middleware('auth:admin')->group(function () {

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // 勤怠一覧
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
            ->name('attendance.list');

        // 勤怠詳細
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])
            ->whereNumber('id')
            ->name('attendance.detail');

        //管理者が勤怠詳細から修正申請を作る（POST）
        Route::post('/stamp_correction_request/admin', [RequestController::class, 'storeByAdmin'])
            ->name('requests.store.admin');

        // 勤怠編集（必要なら）
        Route::get('/attendance/{id}/edit', [AdminAttendanceController::class, 'edit'])
            ->whereNumber('id')
            ->name('attendance.edit');

        // スタッフ一覧
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/{id}', [StaffController::class, 'show'])->whereNumber('id')->name('staff.show');

        // スタッフ別勤怠一覧
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])
            ->whereNumber('id')
            ->name('attendance.staff');

        // CSV出力（スタッフ別）
        Route::get('/attendance/staff/{id}/export', [AdminAttendanceController::class, 'exportStaffCsv'])
            ->whereNumber('id')
            ->name('attendance.staff.export');

        //管理者：申請一覧
        Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])
            ->name('requests.index');

        // 承認画面
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [ApproveController::class, 'show'])
            ->whereNumber('attendance_correct_request_id')
            ->name('requests.approve.show');

        // 承認実行
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [ApproveController::class, 'approve'])
            ->whereNumber('attendance_correct_request_id')
            ->name('requests.approve');
    });
});
