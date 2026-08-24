<?php

// ルートを定義するための読み込み
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
// 勤怠修正申請データを操作するコントローラーの読み込み
use App\Http\Controllers\StampCorrectionRequestController;
// 管理者ログイン用コントローラーの読み込み
use App\Http\Controllers\Admin\AdminLoginController;
// 管理者用勤怠一覧コントローラーの読み込み
use App\Http\Controllers\Admin\AdminAttendanceController;

// ログイン済みの場合は勤怠登録画面へ、未ログインの場合はログイン画面へ自動転送
Route::get('/', function () {
    if (Auth::check()) {
        // もしログインしているユーザーが管理者なら管理者一覧へ自動振り分け
        if (Auth::user()->admin_status) {
            return redirect()->route('admin.attendance.list');
        }
        return redirect()->route('attendance.index');
    }
    return redirect('/login');
});

// Mailpitの画面へ簡単に移動するためのルート
Route::get('/email/go-to-mailpit', function () {
    return redirect('http://localhost:8025');
});

// メール認証機能確認用の強制パスルート
Route::post('/email/bypass', function () {
    //ログインしているユーザー情報を取得
    $user = Auth::user();

    // メール認証機能確認用ユーザーはメール認証しなくても認証済みにする
    if ($user && !$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    // 勤怠登録画面へ遷移する
    return redirect()->route('attendance.index');
})->middleware('auth')->name('email.bypass');

// ログイン認証（auth）とメール認証（verified）が必須のルートをここにまとめます
Route::middleware(['auth', 'verified'])->group(function () {

    // 勤怠登録画面(打刻画面)を表示するルート
    // ログイン認証とメール認証が済みのユーザーがアクセス可能
    // 打刻画面呼び出し用ルート
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤ボタンを押したときの保存処理
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');

    // 退勤ボタンを押したときの保存処理
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // 休憩入・休憩戻ボタンを押したときの保存処理
    Route::post('/attendance/break', [AttendanceController::class, 'break'])->name('attendance.break');

    // 勤怠一覧画面を表示するルート
    Route::get('/attendance/list', [AttendanceController::class, 'showList'])->name('attendance.list');

    // 勤怠一覧詳細画面を表示するルート
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');

    // 修正ボタンが押されたときの更新処理を実行するルート
    Route::patch('/attendance/detail/update/{id}', [AttendanceController::class, 'update'])->name('attendance.update');

    // 申請一覧画面を表示するルート
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('attendance_correction_request.index');

    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');

});

// 管理者向けルートグループ
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');

    // 認証が必須な管理者用ルート
    Route::middleware(['auth'])->group(function () {
        // 管理者ログアウト
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        // 修正申請承認画面
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApproveView'])->name('request.approve');

        // 修正申請の承認処理実行
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approveRequest'])->name('request.approve');

        // 勤怠一覧画面(管理者)
        Route::get('/attendance/list', [AdminAttendanceController::class, 'showDailyList'])->name('attendance.list');

        // 勤怠詳細画面(管理者)
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->name('attendance.detail');

        // 管理者が修正ボタンが押したときの更新処理を実行するルート
        Route::patch('/attendance/{id}', [AdminAttendanceController::class, 'updateDetail'])->name('attendance.update');

        // 管理者がスタッフ一覧ボタンを押した時のルート
        Route::get('/staff/list', [AdminAttendanceController::class, 'showStaffList'])->name('staff.list');

        // 管理者がスタッフ一覧から指定したスタッフの詳細ボタンを押した時のルート
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffAttendance'])->name('attendance.staff');

        // 管理者が指定したスタッフの一覧画面からcsvボタンを押した時のルート
        Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'downloadCsv'])->name('attendance.staff.csv');
    });
});