@extends('layouts.app')

@section('title', 'Ví tổ chức - '.$organization->name)

@section('content')
    <div class="card w-100 mb-3" style="display: flex; justify-content: center; border: 2px solid var(--nk-sticky);">
        <div class="card-body">
            <div class="d-flex align-items-center" style="margin-bottom: -10px;">
                <h1 style="font-size: 3rem;">{{ number_format($organization->balance, 0, ',', '.') }}</h1>
                <p style="padding: 10px; margin: 0;">điểm</p>
            </div>
            <p>Số dư của tổ chức {{ $organization->name }}</p>
        </div>
    </div>

    @if ($isHost)
        <div class="card w-100 mb-3">
            <div class="card-body">
                <h4 style="font-size: 1.4rem;">Chuyển tiền cho thành viên</h4>
                <form action="{{ route('organization2user_transaction_create', $organization->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email người nhận</label>
                        <input class="form-control" type="email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tiền</label>
                        <input class="form-control" type="number" name="amount" min="1" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu xác nhận</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tạo giao dịch</button>
                </form>
            </div>
        </div>
    @endif

    <table class="table bg-white rounded shadow-sm">
        <thead>
            <tr><th>STT</th><th>Loại</th><th>Đối tác</th><th>Số tiền</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
            @php $i = 0; @endphp
            @foreach ($user2org as $tx)
                <tr>
                    <td>{{ ++$i }}</td>
                    <td>Người dùng → Tổ chức</td>
                    <td>#{{ $tx->from }}</td>
                    <td>+{{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td><span class="badge rounded-pill text-bg-{{ $tx->status === 'finished' ? 'success' : 'warning' }}">{{ $tx->status }}</span></td>
                </tr>
            @endforeach
            @foreach ($org2user as $tx)
                <tr>
                    <td>{{ ++$i }}</td>
                    <td>Tổ chức → Người dùng</td>
                    <td>#{{ $tx->userID }}</td>
                    <td>-{{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td><span class="badge rounded-pill text-bg-{{ $tx->status === 'finished' ? 'success' : 'warning' }}">{{ $tx->status }}</span></td>
                </tr>
            @endforeach
            @if ($i === 0)
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có giao dịch nào</td></tr>
            @endif
        </tbody>
    </table>
@endsection

@section('content-mobile')
    <div class="card w-100 mb-3" style="border: 2px solid var(--nk-sticky);">
        <div class="card-body">
            <h1>{{ number_format($organization->balance, 0, ',', '.') }} điểm</h1>
            <p>Số dư của tổ chức</p>
        </div>
    </div>
    <table class="table bg-white rounded shadow-sm">
        <thead><tr><th>Loại</th><th>Đối tác</th><th>Trạng thái</th></tr></thead>
        <tbody>
            @foreach ($user2org as $tx)
                <tr><td>U → O</td><td>#{{ $tx->from }}</td><td>{{ $tx->status }}</td></tr>
            @endforeach
            @foreach ($org2user as $tx)
                <tr><td>O → U</td><td>#{{ $tx->userID }}</td><td>{{ $tx->status }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endsection
