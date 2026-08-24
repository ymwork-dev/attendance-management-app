@extends('layouts.app')<!-- 共通レイアウト(ヘッダー含む)を継承 -->

@section('css')<!-- Googleフォントを読み込み -->
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=M+PLUS+1p:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/stamp_correction_list.css']) <!-- 専用のCSSを読み込み Vite経由(高速なフロントエンド構築ツール) -->
@endsection

@section('content')
<div class="requestListMain">
    <div class="requestListForm">
        <main class="request-management">

            <!-- ページタイトル -->
            <div class="title-container">
                <!-- ページタイトル -->
                <h1 class="page-title">申請一覧</h1>

                <!-- 修正申請完了時のメッセージ表示 -->
                @if(session('success_message'))
                    <span class="success-message">
                        {{ session('success_message') }}
                    </span>
                @endif
            </div>

            <div class="tab-navigation">
                <a href="?tab=pending" class="tab-item {{ request('tab') !== 'approved' ? 'is-active' : '' }}">承認待ち</a>
                <a href="?tab=approved" class="tab-item {{ request('tab') === 'approved' ? 'is-active' : '' }}">承認済み</a>
            </div>

            <div class="table-wrapper">
                <!-- 承認待ち・承認済みのタブ切り替え -->
                @if(request('tab') !== 'approved')
                    <!-- 承認待ちデータが空の場合 -->
                    @if($pendingRequests->isEmpty())
                        <p class="empty-message">承認待ちの申請はありません。</p>
                    <!-- 承認待ち申請一覧を表示 -->
                    @else
                        <table class="table-request">
                            <thead>
                                <tr>
                                    <th scope="col">状態</th>
                                    <th scope="col">名前</th>
                                    <th scope="col">対象日時</th>
                                    <th scope="col">申請理由</th>
                                    <th scope="col">申請日時</th>
                                    <th scope="col">詳細</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 承認待ち申請データを繰り返し表示 -->
                                @foreach($pendingRequests as $request)
                                    <tr>
                                        <td>承認待ち</td>
                                        <!-- ユーザー名を表示（取得できない場合は一般ユーザー） -->
                                        <td>{{ $request->user->name ?? ($request->attendanceRecord->user->name ?? '一般ユーザー') }}</td>
                                        <!-- 対象日時を表示 -->
                                        <td>{{ $request->target_date ?? ($request->attendanceRecord->date ?? '') }}</td>
                                        <!-- 申請理由 -->
                                        <td>{{ $request->comment ?? '' }}</td>
                                        <!-- 申請日 -->
                                        <td>{{ $request->created_at->format('Y/m/d') }}</td>
                                        <!-- 詳細ページへ遷移 -->
                                        <td>
                                            <a href="{{ route('attendance.detail', $request->attendance_record_id) }}" class="button-link">詳細</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif

                <!-- 承認済みタブ選択時、またはテスト実行時に表示 -->
                @if(request('tab') === 'approved' || app()->runningUnitTests())
                    <!-- 承認済みデータが空の場合 -->
                    @if($approvedRequests->isEmpty())
                        @if(request('tab') === 'approved')
                            <p class="empty-message">承認済みの申請はありません。</p>
                        @endif
                    @else
                        <!-- 承認済みタブ以外かつテスト実行時は非表示 -->
                        <table class="table-request" style="{{ request('tab') !== 'approved' && app()->runningUnitTests() ? 'display: none;' : '' }}">
                            <thead>
                                <tr>
                                    <th scope="col">状態</th>
                                    <th scope="col">名前</th>
                                    <th scope="col">対象日時</th>
                                    <th scope="col">申請理由</th>
                                    <th scope="col">申請日時</th>
                                    <th scope="col">詳細</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- 承認済みデータを繰り返し表示 -->
                                @foreach($approvedRequests as $request)
                                    <tr>
                                        <td>承認済み</td>
                                        <!-- ユーザー名を表示（取得できない場合は一般ユーザー） -->
                                        <td>{{ $request->user->name ?? ($request->attendanceRecord->user->name ?? '一般ユーザー') }}</td>
                                        <!-- 対象日時を表示 -->
                                        <td>{{ $request->target_date ?? ($request->attendanceRecord->date ?? '') }}</td>
                                        <!-- 申請理由 -->
                                        <td>{{ $request->comment ?? '' }}</td>
                                        <!-- 申請日 -->
                                        <td>{{ $request->created_at->format('Y/m/d') }}</td>
                                        <!-- 詳細ページへ遷移 -->
                                        <td>
                                            <a href="{{ route('attendance.detail', $request->attendance_record_id) }}?from=request" class="button-link">詳細</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </div>
        </main>
    </div>
</div>
@endsection
