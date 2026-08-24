<?php

use Illuminate\Support\Facades\Route; // URLと実行する処理を結びつけるための読み込み
use App\Http\Controllers\Api\V1\AttendanceRecordController; // API用の勤怠データを操作するコントローラーの読み込み

// API(v1)用のルートを定義
Route::prefix('v1')->group(function () {
    // 勤怠データを取得するルート
    Route::get('attendance-records', [AttendanceRecordController::class, 'index']);
    // 勤怠詳細データを取得するルート
    Route::get('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);

    // API(v1)用の認証ルートを定義
    Route::middleware('auth:sanctum')->group(function () {
        // 勤怠データを登録するルート
        Route::post('attendance-records', [AttendanceRecordController::class, 'store']);
        // 勤怠データを更新するルート
        Route::put('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
        // 勤怠データを削除するルート
        Route::delete('attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy']);
    });
});
