<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use App\Models\BreakLog;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\StampCorrectionRequest;

// 管理者の勤怠管理に関する処理を行うクラス
class AdminAttendanceController extends Controller
{
    /**
     * 管理者用勤怠一覧画面を表示する
     *
     * @param Request $request リクエスト情報
     * @return View 管理者用勤怠一覧画面のビュー
     */
    public function showDailyList(Request $request): View
    {
        // リクエストから日付を取得し、未指定なら当日の日付を設定
        $dateStr = $request->query('date', Carbon::now()->format('Y-m-d'));
        $date = Carbon::parse($dateStr);

        // 従業員ユーザー情報と一緒に休憩データも取得しデータベースへのアクセス回数を減らす
        $attendanceRecords = AttendanceRecord::with(['user', 'breaks'])
            ->where('date', $dateStr)
            ->get();

        // 管理者用勤怠一覧画面と日付データを渡す
        return view('admin.admin_attendance_list', [
            'date' => $date,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    /**
     * 管理者用勤怠詳細画面を表示する
     *
     * @param int $id 勤怠データID
     * @return View 管理者用勤怠詳細画面のビュー
     */
    public function showDetail(int $id): View
    {
        // 詳細画面の表示に必要な関連データを合わせて取得(勤怠データ・ユーザー情報・休憩データ・申請データ)
        $attendance = AttendanceRecord::with(['user', 'breaks', 'applications'])->findOrFail($id);

        // 承認待ち状態の申請データがあるか調べる
        $isPending = $attendance->applications ? $attendance->applications->contains('status', 'pending') : false;

        // 承認待ちの修正申請がある場合、申請内容を取得し勤怠データへ反映する
        if ($isPending) {
            $pendingData = $attendance->applications->where('status', 'pending')->first();

            // 出勤時刻・退勤時刻・備考の修正内容を勤怠データへ反映
            if ($pendingData) {
                $attendance->setAttribute('clock_in', $pendingData->requested_clock_in);
                $attendance->setAttribute('clock_out', $pendingData->requested_clock_out);
                $attendance->setAttribute('comment', $pendingData->comment);

                // 承認待ちの修正申請データに休憩時刻の修正値がある場合
                if (!empty($pendingData->requested_breaks)) {
                    $formattedBreaks = collect(array_values($pendingData->requested_breaks))->map(function ($breakData, $index) {
                    // 修正された休憩時刻を画面表示用の休憩データとして作成
                        return new BreakLog([
                                'id'        => $breakData['id'] ?? ($index + 1),
                                'break_in'  => isset($breakData['break_in']) ? \Carbon\Carbon::parse($breakData['break_in'])->format('H:i') : null,
                                'break_out' => isset($breakData['break_out']) ? \Carbon\Carbon::parse($breakData['break_out'])->format('H:i') : null,
                            ]);
                        });
                    // 修正後の休憩データを、勤怠データに紐づく休憩情報としてセットし画面に表示されるよう設定
                    $attendance->setRelation('breaks', $formattedBreaks);
                }
            }
        }

        // 管理者用勤怠詳細画面へ表示するデータを渡す
        return view('admin.admin_attendance_detail', [
            'attendance' => $attendance,
            'isPending' => $isPending,
        ]);
    }

    /**
     * 管理者による勤怠修正の更新
     *
     * @param AdminAttendanceUpdateRequest $request
     * @param int $id 勤怠データID
     * @return RedirectResponse スタッフ別勤怠一覧画面へリダイレクト
     */
    public function updateDetail(AdminAttendanceUpdateRequest $request, int $id): RedirectResponse
    {
        // 更新対象の勤怠データと休憩データを取得
        $attendance = AttendanceRecord::with('breaks')->findOrFail($id);

        // この勤怠データに紐づく修正申請の中に、承認待ちのものがあるか確認
        $isPending = $attendance->applications()->where('status', 'pending')->exists();

        // 承認待ちの場合、修正不可のメッセージを表示し処理の終了
        if ($isPending) {
            return redirect()->back()->with('alert_message', '承認待ちのため修正はできません。');
        }

        // リクエストされた勤怠情報で更新します
        $attendance->update([
            'clock_in'  => $request->input('clock_in'),
            'clock_out' => $request->input('clock_out'),
            'comment'   => $request->input('comment'),
        ]);

        // リクエストされた休憩データを取得
        $breaks = $request->input('breaks', []);

        // 休憩開始時刻または休憩終了時刻が入力されていた場合、休憩データとして追加
        $newBreakIn = $request->input('new_break_in');
        $newBreakOut = $request->input('new_break_out');
        if ($newBreakIn || $newBreakOut) {
            $breaks[] = [
                'id' => null,
                'break_in' => $newBreakIn,
                'break_out' => $newBreakOut,
            ];
        }

        // 既存の休憩データを削除し、入力内容で登録し直す
        if (!empty($breaks)) {
            $attendance->breaks()->delete();
            collect($breaks)->each(function ($breakData) use ($attendance) {
                if (!empty($breakData['break_in'])) {
                    $attendance->breaks()->create([
                        'break_in'  => $breakData['break_in'] ?? null,
                        'break_out' => $breakData['break_out'] ?? null,
                    ]);
                }
            });
        }

        // 修正完了メッセージをつけてスタッフ別勤怠一覧画面へ戻る
        return redirect()->route('admin.attendance.staff', ['id' => $attendance->user_id])
            ->with('success_message', '勤怠情報を修正しました。');
    }

    /**
     * スタッフ一覧画面を表示する
     *
     * @return View 管理者用スタッフ一覧画面のビュー
     */
    public function showStaffList(): View
    {
        // 管理者ではない従業員ユーザーの情報を取得
        $users = User::where('admin_status', false)->get();

        // 管理者用スタッフ一覧画面を返す
        return view('admin.admin_staff_list', compact('users'));
    }

    /**
     * 指定したスタッフの勤怠一覧画面を表示する
     *
     * @param Request $request リクエスト情報
     * @param int $id スタッフID
     * @return View スタッフ別勤怠一覧画面のビュー
     */
    public function showStaffAttendance(Request $request, int $id): View
    {
        // 指定スタッフのデータを取得
        $targetUser = User::findOrFail($id);

        // 指定年月を取得(未指定の場合は当月）
        $currentMonthStr = $request->query('month', Carbon::now()->format('Y-m'));
        // 取得した年月の1日を基準日として作成
        $currentMonth = Carbon::parse($currentMonthStr . '-01');

        // 月切り替え用の前月・翌月を取得
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 勤怠データと一緒に休憩データを取得
        // 休憩時間・勤務時間は AttendanceRecord モデルの total_break_time / total_time アクセサで計算する
        $attendances = AttendanceRecord::with('breaks')
            // 指定したスタッフIDのデータを検索
            ->where('user_id', $targetUser->id)
            // 更に指定年月データを検索
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()

            // 日付をキーにしてデータを扱いやすくする
            ->keyBy('date');

            // 指定月の日付一覧を作成
            $daysInMonth = [];
            // 1日から月末までの日付を順番に追加
            $daysCount = $currentMonth->daysInMonth;
            for ($i = 1; $i <= $daysCount; $i++) {
                $daysInMonth[] = $currentMonth->copy()->day($i);
            }

        // 一覧表示に必要なデータをビューへ渡す
        return view('admin.admin_staff_attendance', [
            'targetUser'   => $targetUser,
            'daysInMonth'  => $daysInMonth,
            'attendances'  => $attendances,
            'currentMonth' => $currentMonth->format('Y年m月'),
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
        ]);
    }

    /**
     * 指定したスタッフの月間勤怠データをCSVファイルとして出力・ダウンロードする
     *
     * @param Request $request リクエスト情報
     * @param int $id スタッフID
     * @return StreamedResponse csvファイルのダウンロードレスポンス
     */
    public function downloadCsv(Request $request, int $id): StreamedResponse
    {
        // csv出力対象スタッフデータを取得
        $targetUser = User::findOrFail($id);
        // 出力対象の年月を取得(未指定の場合は当月)
        $currentMonthStr = $request->query('month', Carbon::now()->format('Y-m'));
        // 指定月の月初の日付を作成
        $currentMonth = Carbon::parse($currentMonthStr . '-01');

        // 指定したスタッフ・対象年月の勤怠データを休憩データとあわせて取得
        $records = AttendanceRecord::with('breaks')
            ->where('user_id', $targetUser->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get();

        // スタッフ名と指定年月を含むｃｓｖファイル名を設定
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $targetUser->name . '_' . $currentMonthStr . '_attendance.csv"',
        ];

        // Csvに出力処理を定義
        $callback = function () use ($records) {
            // 出力先を準備
            $stream = fopen('php://output', 'w');
            // csvヘッダーの作成
            fputcsv($stream, [
                mb_convert_encoding('日付', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('出勤', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('退勤', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('休憩時間', 'SJIS-win', 'UTF-8'),
                mb_convert_encoding('労働時間', 'SJIS-win', 'UTF-8')
            ]);

            // 勤怠データごとに休憩時間の計算
            collect($records)->each(function (AttendanceRecord $record) use ($stream) {
                $totalBreakSeconds = $record->breaks->sum(function ($b): int {
                    if (!$b->break_in || !$b->break_out) return 0;
                    // 休憩時間を秒単位で計算
                    return Carbon::parse($b->break_in)->diffInSeconds(Carbon::parse($b->break_out));
                });
                // csv出力用に休憩時間を時分形式へ変換
                $breakTime = sprintf('%02d:%02d', floor($totalBreakSeconds / 3600), floor(($totalBreakSeconds % 3600) / 60));

                // 勤務時間を時分形式へ変換
                $workTime = '';
                if ($record->clock_in && $record->clock_out) {
                    $totalWorkSeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out)) - $totalBreakSeconds;
                    // 労働時間がマイナスにならないよう補正00:00
                    if ($totalWorkSeconds < 0) $totalWorkSeconds = 0;
                    $workTime = sprintf('%02d:%02d', floor($totalWorkSeconds / 3600), floor(($totalWorkSeconds % 3600) / 60));
                }

                // 日付、出勤退勤時刻、休憩時間、勤務時間をcsvへ出力
                fputcsv($stream, [
                    $record->date,
                    $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
                    $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                    $breakTime,
                    $workTime
                ]);
            });
            // csvの出力終了
            fclose($stream);
        };

        // csvファイルをダウンロードレスポンスとして返す
        return response()->stream($callback, 200, $headers);
    }

    /**
     * 修正申請の一覧画面を表示する
     *
     * @return View 修正申請一覧画面のビュー
     */
    public function showRequestList(): View
    {
        // 修正申請データと一緒にスタッフデータと勤怠データを取得
        $allRequests = StampCorrectionRequest::with(['user', 'attendanceRecord'])->get();

        //修正申請データを承認待ち・承認済み・却下済みに振り分ける
        $pendingRequests = $allRequests->filter(fn(StampCorrectionRequest $req): bool => $req->status === 'pending')->values();
        $approvedRequests = $allRequests->filter(fn(StampCorrectionRequest $req): bool => $req->status === 'approved')->values();
        $rejectedRequests = $allRequests->filter(fn(StampCorrectionRequest $req): bool => $req->status === 'rejected')->values();

        // 管理者用申請一覧へ表示
        return view('admin.admin_request_list', compact('pendingRequests', 'approvedRequests', 'rejectedRequests'));
    }

    /**
     * 修正申請の承認画面を表示する
     *
     * @param int $id 修正申請のID
     * @return View 修正申請の承認画面のビュー
     */
    public function showApproveView(int $id): View
    {
        // 修正申請データと一緒にスタッフデータと勤怠データを取得
        $requestData = StampCorrectionRequest::with(['user', 'attendanceRecord'])->findOrFail($id);
        // 承認待ちかを判定
        $isPending = $requestData->status === 'pending';

        // 管理者用修正申請の承認画面を表示
        return view('admin.admin_request_approve', compact('requestData', 'isPending'));
    }

    /**
     * 修正申請を承認する
     *
     * @param Request $request リクエスト情報
     * @param int $id 修正申請ID
     * @return RedirectResponse 承認完了後の修正申請一覧画面へリダイレクト
     */
    public function approveRequest(Request $request, int $id): RedirectResponse
    {
        // 修正申請データを1件だけ取得
        $requestData = StampCorrectionRequest::findOrFail($id);
        // 更新対象の勤怠データを取得
        $attendance = AttendanceRecord::findOrFail($requestData->attendance_record_id);

        // 承認ボタンを押したら修正申請の出退勤時刻・備考を勤怠データへ反映
        if ($request->input('action') === 'approve') {
            $attendance->update([
                'clock_in'  => $requestData->requested_clock_in,
                'clock_out' => $requestData->requested_clock_out,
                'comment'   => $requestData->comment,
            ]);

            // 修正データに休憩時間の修正がある場合、既存データを削除
            if (!empty($requestData->requested_breaks)) {
                $attendance->breaks()->delete();
                // 新しい休憩データを1件ずつ作成
                collect($requestData->requested_breaks)->each(function (array $breakData) use ($attendance) {
                    // 空データをスキップ
                    if (empty($breakData['break_in']) || empty($breakData['break_out'])) {
                        return;
                    }
                    // 修正後の休憩データを新しく登録
                    $attendance->breaks()->create([
                        'break_in'  => $breakData['break_in'],
                        'break_out' => $breakData['break_out'],
                    ]);
                });
            }

            // ステータスを承認済みに更新
            $requestData->update(['status' => 'approved']);
            // 承認完了メッセージをつけて修正申請一覧画面へ戻る
            return redirect()->route('attendance_correction_request.index')->with('success_message', '申請を承認しました。');
        }

        // 却下ボタンを押したら、勤怠データは変更せず申請のステータスだけ却下済みにする
        if ($request->input('action') === 'reject') {
            $requestData->update(['status' => 'rejected']);
            // 却下完了メッセージをつけて修正申請一覧画面へ戻る
            return redirect()->route('attendance_correction_request.index')->with('success_message', '申請を却下しました。');
        }

        // 承認・却下以外の値が送られてきた場合は、何もせず元の画面に戻す
        return redirect()->back()->with('alert_message', '不正な操作です。');
    }
}


