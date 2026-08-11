@extends('layouts.app')

@section('title', 'Noteket - Số dư')

@section('content')
    <div class="card w-100 mb-3" style="display: flex; justify-content: center; border: 2px solid var(--nk-sticky);">
        <div class="card-body">
            <div class="d-flex align-items-center" style="margin-bottom: -10px;">
                <h1 style="font-size: 3rem;">{{ number_format($user->balance, 0, ',', '.') }}</h1>
                <p style="padding: 10px; margin: 0;">điểm</p>
            </div>
            <p>Số dư của bạn</p>
        </div>
    </div>

    <div class="card w-100 mb-3">
        <div class="card-body">
            <h4 style="font-size: 1.4rem;">Chuyển điểm</h4>
            <form action="{{ route('user2user_transaction_create') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Số tiền</label>
                    <input class="form-control" type="number" name="amount" min="1" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Người nhận (email)</label>
                    <input class="form-control" type="email" id="recipientEmail" name="recipient_email" required>
                    <small class="text-muted">Chúng tôi sẽ tìm tài khoản theo email.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu xác nhận</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Tạo giao dịch</button>
            </form>
        </div>
    </div>

    <table class="table bg-white rounded shadow-sm">
        <thead>
            <tr>
                <th>STT</th>
                <th>Từ</th>
                <th>Đến</th>
                <th>Loại</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allTransactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->from == $user->id ? 'Bạn' : '#' . $tx->from }}</td>
                    <td>{{ $tx->to == $user->id ? 'Bạn' : '#' . $tx->to }}</td>
                    <td>{{ $tx->type }}</td>
                    <td>{{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $badge = match ($tx->status) {
                                'finished' => 'success',
                                'pending' => 'warning',
                                'expired', 'failed', 'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge rounded-pill text-bg-{{ $badge }}">{{ $tx->status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Chưa có giao dịch nào</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('content-mobile')
    <div class="card w-100 mb-3" style="display: flex; justify-content: center; border: 2px solid var(--nk-sticky);">
        <div class="card-body">
            <div class="d-flex align-items-center" style="margin-bottom: -10px;">
                <h1>{{ number_format($user->balance, 0, ',', '.') }}</h1>
                <p style="padding: 10px; margin: 0;">điểm</p>
            </div>
            <p>Số dư của bạn</p>
        </div>
    </div>
    <table class="table bg-white rounded shadow-sm">
        <thead>
            <tr><th>STT</th><th>Từ</th><th>Đến</th><th>Loại</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
            @forelse ($allTransactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->from == $user->id ? 'Bạn' : '#' . $tx->from }}</td>
                    <td>{{ $tx->to == $user->id ? 'Bạn' : '#' . $tx->to }}</td>
                    <td>{{ $tx->type }}</td>
                    <td><span class="badge rounded-pill text-bg-{{ $tx->status === 'finished' ? 'success' : 'warning' }}">{{ $tx->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có giao dịch</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('mobile-fab')
    <button class="fab-add-btn btn btn-primary rounded-pill" style="position: fixed; bottom: 100px; right: 20px; padding: 10px 20px; font-size: 1.2rem; z-index: 2000;" data-bs-toggle="modal" data-bs-target="#transferModal">
        Chuyển tiền
    </button>
@endsection

@section('modals')
    <div class="modal fade" id="transferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chuyển tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body rounded">
                    <form action="{{ route('user2user_transaction_create') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label>Số tiền</label>
                            <input class="form-control" type="number" name="amount" min="1" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label>Email người nhận</label>
                            <input class="form-control" type="email" name="recipient_email" required>
                        </div>
                        <div class="mb-3">
                            <label>Mật khẩu</label>
                            <input class="form-control" type="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Chuyển tiền</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
