@extends('layouts.app')

@section('title', 'Chuyển điểm cho thành viên - Noteket')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card">
                <div class="card-header" style="background-color: #FACC15;">
                    <h3 style="margin: 0; font-size: 1.6rem;">Chuyển điểm cho thành viên</h3>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <p>Tổ chức: <strong>{{ $organization->name }}</strong></p>
                    <form action="{{ route('organization2user_transaction_create', $organization->id) }}" method="POST">
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
