@extends('layouts.app')

@section('title', 'Chuyển điểm - Noteket')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header" style="background-color: var(--nk-yellow);">
                    <h3 style="margin: 0; font-size: 1.6rem;">Chuyển điểm cho người dùng</h3>
                </div>
                <div class="card-body" style="background-color: var(--nk-sticky);">
                    <form action="{{ route('user2user_transaction_create') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email người nhận</label>
                            <input type="email" class="form-control" name="recipient_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số tiền</label>
                            <input type="number" class="form-control" name="amount" min="1" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu xác nhận</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tạo giao dịch</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
