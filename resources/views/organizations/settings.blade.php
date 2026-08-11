@extends('layouts.app')

@section('title', 'Cài đặt - '.$organization->name)

@section('content')
    <h2 class="text-center mb-4">Cài đặt {{ $organization->name }}</h2>

    <div class="row justify-content-center">
        <div class="col-lg-6 col-12">
            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Thông tin tổ chức</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('edit.organization', $organization->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên tổ chức</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $organization->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control bigform" id="description" name="description" rows="3" required>{{ $organization->description }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Đổi chủ sở hữu</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('organization.change_host_real', $pendingHostRequests->first()?->id ?? 0) }}" method="POST" id="changeHostRealForm" style="display: none;"></form>
                    <form action="{{ route('organization.change_host', $organization->id) }}" method="POST">
                        @csrf
                        <p class="text-muted">Tạo yêu cầu chuyển quyền chủ sở hữu cho thành viên khác.</p>
                        <button type="submit" class="btn btn-outline-primary w-100">Tạo yêu cầu đổi chủ</button>
                    </form>

                    @foreach ($pendingHostRequests as $pending)
                        <div class="alert alert-warning mt-3 mb-2">
                            <strong>Yêu cầu đang chờ:</strong>
                            @if ($pending->newHost)
                                Chuyển cho {{ $pending->newHost->name }} ({{ $pending->newHost->email }})
                            @else
                                Chưa chọn người nhận
                            @endif
                            <form action="{{ route('organization.delete_host_request', $pending->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hủy yêu cầu</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header" style="background-color: #FACC15;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Chọn chủ sở hữu mới</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    @if ($pendingHostRequests->isEmpty())
                        <p class="text-muted">Trước tiên hãy tạo yêu cầu đổi chủ ở trên.</p>
                    @else
                        <form action="{{ route('organization.change_host_real', $pendingHostRequests->first()->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="new_host_email" class="form-label">Email chủ sở hữu mới</label>
                                <input type="email" class="form-control" id="new_host_email" name="new_host_email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Gửi lời mời</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="background-color: #ef4444; color: #fff;">
                    <h4 style="margin: 0; font-size: 1.3rem;">Vùng nguy hiểm</h4>
                </div>
                <div class="card-body" style="background-color: #FFE86E;">
                    <form action="{{ route('leave.organization', $organization->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning w-100 mb-2">Rời tổ chức</button>
                    </form>
                    <form action="{{ route('delete.organization', $organization->id) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn tổ chức này cùng tất cả dữ liệu?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">Xóa tổ chức</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Thông tin tổ chức</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('edit.organization', $organization->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="nameOrgM" class="form-label">Tên tổ chức</label>
                    <input type="text" class="form-control" id="nameOrgM" name="name" value="{{ $organization->name }}" required>
                </div>
                <div class="mb-3">
                    <label for="descriptionOrgM" class="form-label">Mô tả</label>
                    <textarea class="form-control bigform" id="descriptionOrgM" name="description" rows="3" required>{{ $organization->description }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Đổi chủ sở hữu</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('organization.change_host', $organization->id) }}" method="POST">
                @csrf
                <p class="text-muted">Tạo yêu cầu chuyển quyền chủ sở hữu cho thành viên khác.</p>
                <button type="submit" class="btn btn-outline-primary w-100">Tạo yêu cầu đổi chủ</button>
            </form>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-header" style="background-color: #ef4444; color: #fff;">
            <h4 style="margin: 0; font-size: 1.3rem;">Vùng nguy hiểm</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('leave.organization', $organization->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-warning w-100 mb-2">Rời tổ chức</button>
            </form>
            <form action="{{ route('delete.organization', $organization->id) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn tổ chức này cùng tất cả dữ liệu?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">Xóa tổ chức</button>
            </form>
        </div>
    </div>
@endsection
