<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AttendanceRecord;

// 勤怠データの操作ルールを定義するクラス
class AttendanceRecordPolicy
{
    /**
     * 全ての権限チェックの前の処理
     *
     * @param User $user ログイン中のユーザーデータが入った箱
     * @param string $ability チェックしようとしている権限の名前
     * @return bool|null 管理者の場合はtrue、それ以外は通常ルールに進めるためnull
     */
    public function before(User $user, string $ability): ?bool
    {
        // 管理者の場合、以降の確認不要
        if ($user->admin_status === true) {
            return true;
        }

        // 管理者でない場合は通常ルール
        return null;
    }

    /**
     * 対象の勤怠データを更新する権限があるかを判定
     *
     * @param User $user ログイン中のユーザーデータが入った箱
     * @param AttendanceRecord $attendanceRecord 操作対象の勤怠データが入った箱
     * @return bool 自分のデータであればtrue
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // スタッフIDを確認し自分の勤怠データだけ操作可能
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 対象の勤怠データを削除する権限があるかを判定
     *
     * @param User $user ログイン中のユーザーデータが入った箱
     * @param AttendanceRecord $attendanceRecord 操作対象の勤怠データが入った箱
     * @return bool 自分のデータであればtrue
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // スタッフIDを確認し、自分の勤怠データだけ操作可能
        return $user->id === $attendanceRecord->user_id;
    }
}
