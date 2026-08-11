@extends('layouts.app')

@section('title', 'Noteket - Cài đặt')

@section('content')
    <h2 class="text-center mb-4">Cài đặt người dùng</h2>

    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Thông tin cá nhân</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('settings.profile') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Ảnh đại diện</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <div class="text-center mb-3">
                        @if ($user->avatar_image_url)
                            <img src="{{ asset('storage/' . $user->avatar_image_url) }}" class="rounded-circle" style="width: 90px; height: 90px; object-fit: cover;" alt="Avatar">
                        @else
                            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 90px; height: 90px; background: #FACC15; font-size: 2.5rem;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        @endif
                    </div>
                    <form action="{{ route('settings.avatar') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" class="form-control" name="avatar" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Cập nhật ảnh đại diện</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Đổi mật khẩu</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('settings.password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password_confirmation" name="password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="background-color: #ef4444; color: #fff;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Phiên đăng nhập</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Thông tin cá nhân</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('settings.profile') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="nameM" class="form-label">Tên</label>
                    <input type="text" class="form-control" id="nameM" name="name" value="{{ $user->name }}" required>
                </div>
                <div class="mb-3">
                    <label for="emailM" class="form-label">Email</label>
                    <input type="email" class="form-control" id="emailM" name="email" value="{{ $user->email }}" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Ảnh đại diện</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('settings.avatar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <input type="file" class="form-control" name="avatar" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Cập nhật ảnh đại diện</button>
            </form>
        </div>
    </div>

    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Đổi mật khẩu</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('settings.password') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="current_passwordM" class="form-label">Mật khẩu hiện tại</label>
                    <input type="password" class="form-control" id="current_passwordM" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_passwordM" class="form-label">Mật khẩu mới</label>
                    <input type="password" class="form-control" id="new_passwordM" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password_confirmationM" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" class="form-control" id="new_password_confirmationM" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
            </form>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-header" style="background-color: #ef4444; color: #fff;">
            <h4 style="margin: 0; font-size: 1.3rem;">Phiên đăng nhập</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100">Đăng xuất</button>
            </form>
        </div>
    </div>
@endsection
