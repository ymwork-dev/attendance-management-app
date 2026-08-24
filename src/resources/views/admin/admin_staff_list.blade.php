@extends('layouts.admin')

@section('css')
@vite(['resources/css/admin_staff_list.css'])
@endsection

@section('content')
<div class="requestListMain">
    <div class="requestListForm">
        <main class="request-management">
            <h2 class="page-title">スタッフ一覧</h2>

            <div class="table-wrapper">
                <table class="table-request">
                    <thead>
                        <tr>
                            <th scope="col">名前</th>
                            <th scope="col">メールアドレス</th>
                            <th scope="col">月次勤怠</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- スタッフデータを1件ずつ取り出し一覧表示 -->
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <a href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}" class="button-link">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
@endsection
