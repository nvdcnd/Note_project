@extends('layouts.app')

@section('title', 'Xác thực giao dịch - Noteket')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header" style="background-color: #FACC15;">
                    <h3 style="margin: 0; font-size: 1.6rem;">Xác thực giao dịch #{{ $transaction->id }}</h3>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <div class="mb-3">
                        <p>Tổ chức #{{ $transaction->organizationID }} chuyển <strong>{{ number_format($transaction->amount, 0, ',', '.') }} điểm</strong> đến người dùng #{{ $transaction->userID }}</p>
                        <p class="text-muted">Mã OTP đã được gửi tới email của bạn và hết hạn sau 10 phút.</p>
                        <p class="text-muted">Số lần thử còn lại: {{ max(0, \App\Http\Controllers\Organization2userTransactionController::MAX_ATTEMPTS - $transaction->attempts) }}</p>
                    </div>
                    <form action="{{ route('organization2user_transaction_verify', $transaction->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="passkey" class="form-label">Mã OTP</label>
                            <input type="text" class="form-control" id="passkey" name="passkey" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Xác nhận</button>
                    </form>
                    <form action="{{ route('organization2user_transaction_cancel', $transaction->id) }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">Hủy giao dịch</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
