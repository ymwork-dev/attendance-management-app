<?php

namespace App\Http\Controllers;

use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Http\Controllers\Admin\AdminAttendanceController;
use Illuminate\Http\Request;

// 勤怠申請一覧画面を処理するクラス
class StampCorrectionRequestController extends Controller
{
    /**
     * 勤怠申請一覧画面を表示する（管理者の場合は管理者用申請一覧画面の表示処理）
     *
     * @param Request $request セッション情報を含むリクエスト
     * @return View 勤怠申請一覧画面のビュー
     */
    public function index(Request $request): View
    {
        // ログイン中のユーザー情報を取得
        $user = Auth::user();

        // 管理者ログインの場合、管理者用の申請一覧画面を表示
        if ($user && $user->admin_status && $request->session()->get('login_entrance') !== 'staff') {
            return app(AdminAttendanceController::class)->showRequestList();
        }
        // ログインユーザーIDを取得
        $userId = Auth::id();

        // ログインユーザーの修正申請データーと一緒に勤怠データを取得
        $allRequests = StampCorrectionRequest::with(['attendanceRecord'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')// 作成日時の昇順に並べる
            ->get();

        // 取得した修正申請データを申請ステータスをもとに、承認待ちに分類
        $pendingRequests = $allRequests->filter(
            function (StampCorrectionRequest $request): bool {
                return $request->status === 'pending';
            }
        );

        // 承認済みに分類
        $approvedRequests = $allRequests->filter(
            function (StampCorrectionRequest $request): bool {
                return $request->status === 'approved';
            }
        );

        // 却下済みに分類
        $rejectedRequests = $allRequests->filter(
            function (StampCorrectionRequest $request): bool {
                return $request->status === 'rejected';
            }
        );

        // 承認待ち・承認済み・却下済み申請をビューへ渡し、勤怠申請一覧画面を表示
        return view('stamp_correction_request.list', compact('pendingRequests', 'approvedRequests', 'rejectedRequests'));
    }

    /**
     * 申請詳細(読み取り専用の履歴確認)画面を表示する
     *
     * @param int $id 修正申請のID
     * @return View 申請詳細画面のビュー
     */
    public function show(int $id): View
    {
        // ログイン中のユーザー自身の申請データのみ取得(他人の申請は見られないようにする)
        $requestData = StampCorrectionRequest::where('user_id', Auth::id())
            ->with('attendanceRecord')
            ->findOrFail($id);

        // 申請詳細画面へ表示するデータを渡す
        return view('stamp_correction_request.show', compact('requestData'));
    }
}
