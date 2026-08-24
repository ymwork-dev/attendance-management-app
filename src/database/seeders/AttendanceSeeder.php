<?php

namespace Database\Seeders;

// laravel標準機能のSeeder機能(初期データー投入機能)の読み込み
use Illuminate\Database\Seeder;
// ユーザー情報のデーターベース操作機能(Userモデル)を使うための読み込み
use App\Models\User;
// データーベースの休憩データーを操作するBreakLogモデルを使うための読み込み
use App\Models\BreakLog;
// 勤怠管理のモデルを使うための読み込み
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Hash;
// 日時を取得・計算するための機能の読み込み
use Carbon\Carbon;

// Seeder機能を継承したオリジナルの勤怠管理初期データー投入機能を作成するためのクラス(設置)
class AttendanceSeeder extends Seeder
{
    /**
     * データベース初期データの投入処理を実行する
     *
     * @return void 戻り値なし
     */
    public function run(): void
    {
        // スタッフユーザー1、メールアドレス、パスワードの暗号化、メール認証日時は現在時刻(認証済み)
        $user1 = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'admin_status' => false, // 一般ユーザー
        ]);

        // スタッフユーザー2、メールアドレス、パスワードの暗号化、メール認証日時は現在時刻(認証済み)
        $user2 = User::create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'admin_status' => false, // 一般ユーザー
        ]);

        // 管理者ユーザー3、メールアドレス、パスワードの暗号化、メール認証日時は現在時刻
        $user3 = User::create([
            'name' => 'ユーザー3(管理者)',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'admin_status' => true, // 管理者ユーザー権限
        ]);

        // 今現在の日時を取得して変数(箱)へしまう
        $now = Carbon::now();

        // 当月の特殊パターンデータ作成
        $patterns = [
            ...array_fill(0, 10, ['09:00:00', '18:00:00']), // 通常 10日
            ...array_fill(0, 3,  ['09:00:00', '20:00:00']), // 残業 3日
            ...array_fill(0, 2,  ['09:30:00', '18:00:00']), // 遅刻 2日
            ...array_fill(0, 1,  ['09:00:00', '17:00:00']), // 早退 1日
            ...array_fill(0, 1,  ['08:00:00', '21:00:00']), // 長時間労働 1日
        ];

        // 各ユーザーの過去データ生成（5から1まで減少する5回分）
        $monthIndexes = [5, 4, 3, 2, 1];

        // ユーザー1のダミーデータ作成
        // 過去5ヶ月分の数字を1つずつ取り出し、過去の勤務データを繰り返し作成
        collect($monthIndexes)->each(function (int $i) use ($now, $user1) {
            // 現在から１か月ずつ戻り、その月の月初を基準点にする
            $monthDate = (clone $now)->subMonths($i)->startOfMonth();
            $createdDays = 0;

            // 平日の勤怠データが15日分できるまで、毎日打刻データを繰り返し登録
            while ($createdDays < 15) {
                // 土日ではない場合
                if (!$monthDate->isWeekend()) {
                    // ユーザー1の出退勤データ（9時から18時）を新しく登録
                    $this->createRecord($user1->id, $monthDate, '09:00:00', '18:00:00');
                    // 登録できた日数のカウントを1日増やす
                    $createdDays++;
                }
                // カレンダーの日付を1日進める
                $monthDate->addDay();
            }
        });

        // 1日からスタートするよう設定
        $currentMonthDate1 = (clone $now)->startOfMonth();
        // 出勤パターンを1つずつ取り出し、今月の平日のみに勤怠データを割り当てる
        collect($patterns)->each(function (array $pattern) use (&$currentMonthDate1, $user1) {
             // 週末である限り、日付を先に進めるループ処理を開始
            while ($currentMonthDate1->isWeekend()) {
                $currentMonthDate1->addDay();
            }
            // 平日の日付でデータベースに勤怠レコードを1件作成
            $this->createRecord($user1->id, $currentMonthDate1, $pattern[0], $pattern[1]);
            // 次へ進むために、日付をさらに1日分進める
            $currentMonthDate1->addDay();
        });

        // ユーザー2のダミーデータ作成
        collect($monthIndexes)->each(function (int $i) use ($now, $user2) {
            $monthDate = (clone $now)->subMonths($i)->startOfMonth();
            $createdDays = 0;

            while ($createdDays < 15) {
                if (!$monthDate->isWeekend()) {
                    $this->createRecord($user2->id, $monthDate, '09:00:00', '18:00:00');
                    $createdDays++;
                }
                $monthDate->addDay();
            }
        });

        $currentMonthDate2 = (clone $now)->startOfMonth();
        collect($patterns)->each(function (array $pattern) use (&$currentMonthDate2, $user2) {
            while ($currentMonthDate2->isWeekend()) {
                $currentMonthDate2->addDay();
            }
            $this->createRecord($user2->id, $currentMonthDate2, $pattern[0], $pattern[1]);
            $currentMonthDate2->addDay();
        });

        // ユーザー3（管理者）のダミーデータ作成
        collect($monthIndexes)->each(function (int $i) use ($now, $user3) {
            $monthDate = (clone $now)->subMonths($i)->startOfMonth();
            $createdDays = 0;

            while ($createdDays < 15) {
                if (!$monthDate->isWeekend()) {
                    $this->createRecord($user3->id, $monthDate, '09:00:00', '18:00:00');
                    $createdDays++;
                }
                $monthDate->addDay();
            }
        });

        $currentMonthDate3 = (clone $now)->startOfMonth();
        collect($patterns)->each(function (array $pattern) use (&$currentMonthDate3, $user3) {
            while ($currentMonthDate3->isWeekend()) {
                $currentMonthDate3->addDay();
            }
            // ★完全復元
            $this->createRecord($user3->id, $currentMonthDate3, $pattern[0], $pattern[1]);
            $currentMonthDate3->addDay();
        });
    }

    /**
     * ユーザーID、日付、出退勤時刻を個別に作成するための関数(設置)
     *
     * @param int $userId スタッフユーザーのID
     * @param Carbon $date 対象となる日付データ
     * @param string $startTime 出勤時刻
     * @param string $endTime 退勤時刻
     * @return void 戻り値なし
     */
    private function createRecord(int $userId, Carbon $date, string $startTime, string $endTime): void
    {
        // 年月日をデーター形式で変数(箱)にしまう
        $dateStr = $date->format('Y-m-d');

        // 勤怠データの従業員ID、勤務日時形式、出退勤時刻を新規作成・更新を保存
        $attendance = AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $dateStr,
            'clock_in' => $startTime,
            'clock_out' => $endTime,
        ]);

        // 休憩テーブルに打刻データを登録する 休憩入戻時刻、新規作成・更新データを保存する
        BreakLog::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}

