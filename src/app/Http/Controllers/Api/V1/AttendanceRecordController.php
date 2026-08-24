<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// 勤怠管理画面の処理を行うクラス
class AttendanceRecordController extends Controller
{
    // laravel標準の操作権限の確認をする機能を利用するためのトレイト
    use AuthorizesRequests;

    /**
     * 勤怠一覧を取得する
     *
     * @param IndexAttendanceRecordRequest $request リクエスト情報
     * @return AnonymousResourceCollection 勤怠一覧のJSONレスポンス
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        // 1ページ20件
        $perPage = (int) $request->input('per_page', 20);

        // 勤怠データの取得
        $records = AttendanceRecord::with(['user', 'breaks'])
            // スタッフIDで絞り込み
            ->when($request->filled('user_id'), function ($query) use ($request) {
                $query->where('user_id', $request->input('user_id'));
            })
            // 日付けで絞り込み
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->where('date', $request->input('date'));
            })
            // 月で絞り込み
            ->when($request->filled('month'), function ($query) use ($request) {
                $query->where('date', 'like', $request->input('month') . '%');
            })
            // 日付けの新しい順に並び替え
            ->latest('date')
            // 指定件数ごとにページ分けして取得
            ->paginate($perPage);

        // 勤怠一覧データをJSONレスポンスとして返す
        return AttendanceRecordResource::collection($records);
    }

    /**
     * 勤怠データを登録する
     *
     * @param StoreAttendanceRecordRequest $request リクエスト情報
     * @return JsonResponse 登録した勤怠データのJSONレスポンス
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        // リクエスト内容をチェックし、勤怠データを登録
        $attendanceRecord = $request->user()->attendanceRecords()->create($request->validated());

        //登録した勤怠データに関連するユーザー情報と休憩データの読み込み
        $attendanceRecord->load(['user', 'breaks']);

        // 登録した勤怠データのJSONレスポンスと登録成功ステータスを返す
        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠詳細を取得する
     *
     * @param AttendanceRecord $attendanceRecord 勤怠データ
     * @return AttendanceRecordResource 勤怠詳細のJSONレスポンス
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        // 勤怠データを読み込む(ユーザー情報、休憩データ、申請データ)
        $attendanceRecord->load(['user', 'breaks', 'applications']);

        // 勤怠詳細のJSONレスポンスを返す
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠データを更新する
     *
     * @param UpdateAttendanceRecordRequest $request リクエスト情報
     * @param AttendanceRecord $attendanceRecord 更新対象の勤怠データ
     * @return AttendanceRecordResource 更新後の勤怠データのJSONレスポンス
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        // 更新権限を確認
        $this->authorize('update', $attendanceRecord);

        // バリデーション済みの勤怠データで更新
        $attendanceRecord->update($request->validated());

        // 関連データの読み込み
        $attendanceRecord->load(['user', 'breaks']);

        // 更新後の勤怠データのJSONレスポンスを返す
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠データを削除する
     *
     * @param AttendanceRecord $attendanceRecord 削除対象の勤怠データ
     * @return Response 削除成功レスポンス
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        // 削除権限を確認
        $this->authorize('delete', $attendanceRecord);

        // 勤怠データの削除
        $attendanceRecord->delete();

        // 削除成功のレスポンスを返す
        return response()->noContent();
    }
}
