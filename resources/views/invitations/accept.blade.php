@extends('layouts.auth')

@section('title', 'Noteket - Nhận lời mời')

@section('content')
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg" style="display: flex; flex-direction: column; justify-content: center; align-items: center; margin-bottom: 20px; text-align: center;">
                <h1 class="card-title">Noteket</h1>
                <p class="card-text">
                    Bạn được mời vào {{ $targetLabel }}@if ($invitation->inviter) bởi {{ $invitation->inviter->name }}@endif.
                </p>
                <p class="card-text">Tạo mật khẩu để nhận lời mời.</p>
            </div>
            <div class="col-lg-1 d-none d-lg-block">
                <div style="border-radius: 20px; height: 90%; width: 3px; background-color: #000; margin: 0 auto;"></div>
            </div>
            <div class="col-lg">
                <form action="{{ route('invitation.accept', $token) }}" method="POST" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        {{-- Chỉ để hiển thị: email luôn lấy từ lời mời ở phía server. --}}
                        <input type="email" class="form-control" id="email" value="{{ $invitation->email }}" disabled readonly>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Tên hiển thị</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tạo tài khoản và nhận lời mời</button>
                    <p style="margin: 10px 0 0;">Đã có tài khoản, <a href="{{ route('login') }}" style="text-decoration: underline;">Đăng nhập</a></p>
                </form>
            </div>
        </div>
    </div>
@endsection
