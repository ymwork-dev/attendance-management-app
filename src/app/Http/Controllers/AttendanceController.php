<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceRecord;
use Illuminate\Contracts\View\View;
use App\Models\BreakLog;
use Illuminate\Http\RedirectResponse;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\UpdateAttendanceRequest;

// 勤怠管理画面に関する処理を行うクラス
class AttendanceController extends Controller
{
    /**
     * 勤怠管理画面を表示する
     *
     * @return View 勤怠管理画面のビュー
     */
    public function index(): View
    {
        // 現在ログインしているユーザー情報を取得
        $user = Auth::user();
        // 本日の日付を取得
        $today = Carbon::today();

        // ログインユーザーの当日の勤怠データを1件取得
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();// 先頭の1件を取得

        // 取得した勤怠データに紐づく最新の修正申請を1件取得
        $correctionRequest = $attendance
            ? StampCorrectionRequest::where('attendance_record_id', $attendance->id)->latest()->first()
            : null;

        // 画面表示用の初期状態の設定(休憩中でない、退勤していない)
        $is_breaking = false;
        $is_clocked_out = false;

        // 勤務状態の判定
        if ($attendance) {
            // 退勤時刻が登録されていれば退勤済と判定し、
            if ($attendance->clock_out) {
                $is_clocked_out = true;
            } else {
                // 退勤していない場合は、休憩終了時刻に未登録の休憩があれば休憩中と判定
                $is_breaking = $attendance->breaks()
                    ->whereNull('break_out')
                    ->exists();
            }
        }

        // 勤怠管理画面に勤怠データ、修正申請データ、休憩中かどうか、退勤済かどうか、本日の日付けをビューに渡す
        return view('attendance', [
            'attendance'     => $attendance,
            'correctionRequest' => $correctionRequest,
            'is_breaking'    => $is_breaking,
            'is_clocked_out' => $is_clocked_out,
            'today'          => $today->format('Y年n月j日'),
        ]);
    }

    /**
     * 出勤打刻を行う
     *
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function clockIn(): RedirectResponse
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 本日の勤怠データが登録済みか確認
        $exists = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->exists();

        // 未登録の場合のみ出勤データを作成
        if (!$exists) {
            AttendanceRecord::create([
                'user_id'  => $user->id,
                'date'     => $today->toDateString(),
                'clock_in' => Carbon::now()->toTimeString(),
            ]);
        }
        // 勤怠登録画面へリダイレクト
        return redirect()->back();
    }

    /**
     * 退勤打刻を行う
     *
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function clockOut(): RedirectResponse
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 本日の勤怠データを1件取得
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // 未退勤の場合、退勤時刻を登録
        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => Carbon::now()->toTimeString(),
            ]);

            return redirect()->back()->with('message', 'お疲れ様でした。');
        }

        // 退勤済の場合も元の画面へリダイレクト
        return redirect()->back();
    }

    /**
     * 休憩開始・休憩終了の打刻を行う
     *
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function break():RedirectResponse
    {
        $user = Auth::user();
        $today = Carbon::today();
        // 現在時刻を取得
        $now = Carbon::now();

        // 本日の勤怠データを1件取得
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // 出勤前または退勤後は休憩打刻処理を行わない
        if (!$attendance || $attendance->clock_out) {
            return redirect()->back();
        }

        // 休憩中データを1件取得
        $activeBreak = $attendance->breaks()
            ->whereNull('break_out')
            ->first();


        // 休憩中の場合は休憩終了時刻を登録
        if ($activeBreak) {
            $activeBreak->update([
                'break_out'  => $now->toTimeString(),
                'updated_at' => $now,
            ]);
        }
        // 休憩中でない場合は休憩開始を登録
        else {
            $attendance->breaks()->create([
                'break_in'   => $now->toTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 勤怠登録画面へリダイレクト
        return redirect()->back();
    }

    /**
     * 従業員の勤怠一覧を表示する
     *
     * @param Request $request リクエスト情報
     * @return View 勤怠一覧画面
     */
    public function showList(Request $request): View
    {
        $user = Auth::user();

        // 表示対象の年月を取得
        $currentMonthStr = $request->query('month', Carbon::now()->format('Y-m'));
        // 取得した年月の1日を取得
        $currentMonth = Carbon::parse($currentMonthStr . '-01');

        // ユーザーが指定した年月の前月情報を取得
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        // ユーザーが指定した年月の翌月情報を取得
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 勤怠登録データーを取得し1件ずつ追加(休憩データも一緒に取得N+1問題防止)
        $attendances = AttendanceRecord::with('breaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->map(function (AttendanceRecord $record): AttendanceRecord {

            // 承認されるまでは元の確定データのみを使って計算する
                $record->clock_in = $record->clock_in;
                $record->clock_out = $record->clock_out;

                // 休憩時間を秒で集計
                $totalBreakSeconds = $record->breaks->sum(function (BreakLog $break): int {
                    if (!$break->break_in || !$break->break_out) return 0;
                    return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
                });

                // 休憩時間を表示形式へ変換
                $breakHours = floor($totalBreakSeconds / 3600);
                $breakMinutes = floor(($totalBreakSeconds % 3600) / 60);
                $record->display_break_time = $totalBreakSeconds > 0 ? sprintf('%02d:%02d', $breakHours, $breakMinutes) : '00:00';

                // 勤務時間を計算(退勤時間-出勤時間-休憩時間）
                $record->display_work_time = '';
                if ($record->clock_in && $record->clock_out) {
                    $start = Carbon::parse($record->clock_in);
                    $end = Carbon::parse($record->clock_out);
                    $totalWorkSeconds = $start->diffInSeconds($end) - $totalBreakSeconds;

                    if ($totalWorkSeconds < 0) { $totalWorkSeconds = 0; }

                    // 勤務時間を表示形式へ変換
                    $workHours = floor($totalWorkSeconds / 3600);
                    $workMinutes = floor(($totalWorkSeconds % 3600) / 60);
                    $record->display_work_time = sprintf('%02d:%02d', $workHours, $workMinutes);
                }

                return $record;
            })
            // 日付けをキーにして扱いやすく変換
            ->keyBy('date');

        // 当月の日付を昇順に並べる
        $daysInMonth = [];
        $daysCount = $currentMonth->daysInMonth;
        for ($i = 1; $i <= $daysCount; $i++) {
            $daysInMonth[] = $currentMonth->copy()->day($i);
        }

        // 勤怠一覧画面表示のビューを渡す(当月の日付一覧・勤怠データ・年月データ・前月データ・翌月データ)
        return view('attendance_list', [
            'daysInMonth'  => $daysInMonth,
            'attendances'  => $attendances,
            'currentMonth' => $currentMonth->format('Y年m月'),
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
        ]);
    }

    /**
     * 勤怠詳細画面を表示する
     *
     * @param int $id 勤怠データID
     * @return View 勤怠詳細画面のビュー
     */
    public function show(int $id): View
    {
        // ログインユーザーの勤怠データを取得
        $record = AttendanceRecord::where('user_id', auth()->id())
            ->findOrFail($id);// 指定したIDデータを1件取得、無ければ404エラー

        // 取得した勤怠データ1件に関連するデータを読み込む
        $record->load(['breaks', 'applications']);

        // 承認待ち申請があるか判定
        $isPending = $record->applications ? $record->applications->contains('status', 'pending') : false;

        // 勤怠データに紐づく修正申請データを取得
        $correctionRequest = $record->applications ? $record->applications->sortByDesc('created_at')->first() : null;

        // 承認待ち申請内容を画面表示用データへ反映
        if ($isPending) {
            $pendingData = $record->applications->where('status', 'pending')->first();
            $correctionRequest = $pendingData;

            // 申請内容で勤怠データを上書き
            if ($pendingData) {
                $record->clock_in = Carbon::parse($pendingData->requested_clock_in)->format('H:i');
                $record->clock_out = $pendingData->requested_clock_out ? Carbon::parse($pendingData->requested_clock_out)->format('H:i') : null;
                $record->comment = $pendingData->comment;

                // 申請された休憩データを画面表示用に整形
                if (!empty($pendingData->requested_breaks)) {
                    $formattedBreaks = collect(array_values($pendingData->requested_breaks))->map(function ($break, $index) {

                        return new BreakLog([
                            'id' => $break['id'] ?? ($index + 1),
                            'break_in' => isset($break['break_in']) ? Carbon::parse($break['break_in'])->format('H:i') : null,
                            'break_out' => isset($break['break_out']) ? Carbon::parse($break['break_out'])->format('H:i') : null,
                        ]);
                    });
                    // 画面表示用の休憩データを設定
                    $record->setRelation('breaks', $formattedBreaks);
                }
            }
        }

        // 最新申請の備考を表示
        if ($correctionRequest && $correctionRequest->comment) {
            $record->comment = $correctionRequest->comment;
        }

        return view('attendance_detail', compact('record', 'isPending', 'correctionRequest'));
    }

    /**
     * 勤怠修正申請を登録する
     *
     * @param UpdateAttendanceRequest $request 修正内容
     * @param int $id 勤怠データID
     * @return RedirectResponse 申請一覧画面へリダイレクト
     */
    public function update(UpdateAttendanceRequest $request, int $id): RedirectResponse
    {

        // 対象の勤怠データを取得
        $record = AttendanceRecord::findOrFail($id);

        // 入力された休憩データを取得
        $breaks = $request->input('breaks', []);

        // 新しい休憩時間が入力されている場合は追加
        if ($request->filled('new_break_in') || $request->filled('new_break_out')) {

            // 新しい休憩データを追加
            $breaks[] = [
                'break_in'  => $request->input('new_break_in'),
                'break_out' => $request->input('new_break_out'),
            ];
        }

        // 修正申請を承認待ちで登録
        StampCorrectionRequest::create([
            'user_id'              => auth()->id(),
            'attendance_record_id' => $record->id,
            'requested_clock_in'   => $request->input('clock_in'),
            'requested_clock_out'  => $request->input('clock_out'),
            'requested_breaks'     => $breaks,
            'status'               => 'pending',
            'comment' => $request->input('comment'),
        ]);

        // 申請一覧画面へリダイレクトし、成功メッセージを表示
        return redirect('/stamp_correction_request/list')->with('success_message', '修正を申請しました');
    }

    /**
     * マイ勤怠レポート画面を表示するための月間集計処理を行う
     *
     * @param Request $request リクエスト情報
     * @return View マイ勤怠レポート画面のビュー
     */
    public function report(Request $request): View
    {
        $user = Auth::user();
        $now = Carbon::now();
        // 集計対象期間を6か月間で設定
        $sixMonthsAgo = Carbon::now()->startOfMonth()->subMonths(5);
        $endDate = Carbon::now()->endOfMonth();

        // 集計対象の勤怠データを取得
        $records = AttendanceRecord::with('breaks')
            ->where('user_id', $user->id)
            ->where('date', '>=', $sixMonthsAgo->format('Y-m-d'))
            ->where('date', '<=', $endDate->format('Y-m-d'))
            ->get();

        // 月ごとの集計データを準備
        $monthlyData = collect();

        // 過去6か月分を集計
        collect()->times(6, function (int $number) use ($records, $monthlyData) {
            $i = 6 - $number;

            //月初開始
            $monthStr = Carbon::now()->startOfMonth()->subMonths($i)->format('Y-m');

            // 対象月の勤怠データを取得
            $monthRecords = $records->filter(fn($record) => Carbon::parse($record->date)->format('Y-m') === $monthStr);

            // 月の勤務時間を秒で集計
            $totalWorkSeconds = $monthRecords->sum(function ($record) {
                if (!$record->clock_in || !$record->clock_out) {
                    return 0;
                }
                // 秒単位の勤務時間
                $staySeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out));
                // 休憩開始時刻または休憩終了時刻が無い場合、0を返す
                $breakSeconds = $record->breaks->sum(function ($break) {
                    if (!$break->break_in || !$break->break_out) {
                        return 0;
                    }
                    // 休憩開始時刻と休憩終了時刻をcarbon形式に変換し、差を秒単位で計算
                    return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
                });
                // 休憩時間を差し引いた労働時間を0未満にならないようにする
                return max(0, $staySeconds - $breakSeconds);
            });

            // 月の残業時間を秒単位で集計、出退勤時刻が無い場合0
            $totalOvertimeSeconds = $monthRecords->sum(function ($record) {
                if (!$record->clock_in || !$record->clock_out) {
                    return 0;
                }
                // 出退勤時刻を秒単位で計算
                $staySeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out));
                // 休憩開始時刻または休憩終了時刻が無い場合0
                $breakSeconds = $record->breaks->sum(function ($break) {
                    if (!$break->break_in || !$break->break_out) {
                        return 0;
                    }
                    // 休憩開始時刻と休憩終了時刻をcarbon形式に変換し、差を秒単位で計算
                    return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
                });
                // 休憩時間を差し引いた労働時間を0未満にならないようにする
                $workSeconds= max(0, $staySeconds - $breakSeconds);
                // 8時間を超えた分を残業時間とする
                return max(0, $workSeconds - 28800);
            });

            // 月ごとの集計結果を保存
            $monthlyData->put($monthStr, [
                'work_hours' => (int)floor($totalWorkSeconds / 3600),
                'work_minutes' => (int)floor(($totalWorkSeconds % 3600) / 60),
                'overtime_hours' => (int)floor($totalOvertimeSeconds / 3600),
                'overtime_minutes' => (int)floor(($totalOvertimeSeconds % 3600) / 60),
                'raw_work_seconds' => $totalWorkSeconds,
            ]);
        });

        // 6か月分の総勤務時間を集計
        $grandTotalWorkSeconds = $monthlyData->sum('raw_work_seconds');

        // 出勤時刻または退勤時刻が無い場合、0を返す
        $grandTotalOvertimeSeconds = $records->sum(function ($record) {
            if (!$record->clock_in || !$record->clock_out) {
                return 0;
            }
            // 出勤時刻と退勤時刻を秒単位で計算
            $staySeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out));
            // 休憩時間を秒単位で取得
            $breakSeconds = $record->breaks->sum(function ($break) {
                // 休憩開始時刻または休憩終了時刻が無い場合0
                if (!$break->break_in || !$break->break_out) {
                    return 0;
                }
                // 休憩開始時刻と休憩終了時刻をcarbon形式に変換し、差を秒単位で計算
                return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
            });
            // 休憩時間を差し引いた労働時間を0未満にならないようにする
            $workSeconds = max(0, $staySeconds - $breakSeconds);
            // 8時間を超えた分を残業時間として秒単位で返す
            return max(0, $workSeconds - 28800);
        });

        // 総勤務日数を取得
        $totalDays = $records->count();
        // 平均勤務時間を秒単位で計算、勤務日が無い場合0
        $averageWorkSeconds = $totalDays > 0 ? (int)round($grandTotalWorkSeconds / $totalDays) : 0;

        // 総勤務時間・総残業時間・平均勤務時間を時分形式へ変換
        $summary = [
            'total_work' => ['h' => (int)floor($grandTotalWorkSeconds / 3600), 'm' => (int)floor(($grandTotalWorkSeconds % 3600) / 60)],
            'total_overtime' => ['h' => (int)floor($grandTotalOvertimeSeconds / 3600), 'm' => (int)floor(($grandTotalOvertimeSeconds % 3600) / 60)],
            'average_work' => ['h' => (int)floor($averageWorkSeconds / 3600), 'm' => (int)floor(($averageWorkSeconds % 3600) / 60)],
        ];

        // 現在年月を取得
        $currentMonthStr = $now->format('Y-m');
        // 当月の勤怠データを抽出
        $currentMonthRecords = $records->filter(fn($record) => Carbon::parse($record->date)->format('Y-m') === $currentMonthStr);

        $anomaly = [
            // 出勤時間が9時より遅い場合は遅刻で集計
            'lateness' => $currentMonthRecords->filter(fn($record) => Carbon::parse($record->clock_in)->format('H:i:s') > '09:00:00')->count(),
            // 退勤時間が18時より早い場合は早退で集計
            'early_leave' => $currentMonthRecords->filter(fn($record) => $record->clock_out && Carbon::parse($record->clock_out)->format('H:i:s') < '18:00:00')->count(),
            // 長時間労働の勤怠を集計
            'long_working' => $currentMonthRecords->filter(function ($record) {
                // 出勤時刻または退勤時刻が無い場合、除外する
                if (!$record->clock_in || !$record->clock_out) {
                    return false;
                }
                // 出勤から退勤までの経過時間を秒単位で計算
                $staySeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out));
                // 休憩時間を秒単位で計算
                $breakSeconds = $record->breaks->sum(function ($break) {
                    // 休憩開始または休憩終了時刻が無い場合0
                    if (!$break->break_in || !$break->break_out) {
                        return 0;
                    }
                    // 休憩開始と休憩終了時刻をcarbonに変換して、差を秒単位で返す
                    return Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
                });
                // 勤務時間が10時間を超える勤怠を抽出
                return ($staySeconds - $breakSeconds) > 36000;
            })->count(),
        ];

        // 集計結果をマイ勤怠レポート画面へ渡す
        return view('attendance_report', compact('summary', 'monthlyData', 'anomaly'));
    }
}
