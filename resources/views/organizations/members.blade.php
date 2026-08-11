@extends('layouts.app')

@section('title', 'Thành viên - '.$organization->name)

@section('content')
    <h2 class="text-center mb-4">Danh sách thành viên - {{ $organization->name }}</h2>

    <div class="card mb-4">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Thêm thành viên mới</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('share.organization', $organization->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="user_list" class="form-label">Email thành viên (nhập nhiều email, cách nhau bằng dấu phẩy)</label>
                    <input type="text" class="form-control" id="user_list" name="user_list_text" placeholder="member1@example.com, member2@example.com" required>
                </div>
                <button type="submit" class="btn btn-primary">Gửi lời mời</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Thành viên hiện tại ({{ $currentMemberList->count() }})</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            @forelse ($currentMemberList as $member)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: rgba(255,255,255,0.6);">
                    <span><strong>{{ $member->user?->name }}</strong> <small class="text-muted">{{ $member->user?->email }}</small></span>
                    <div class="d-flex align-items-center gap-2">
                        @if ($member->userID === $organization->hostID)
                            <span class="badge rounded-pill text-bg-warning">Host</span>
                        @else
                            <form action="{{ route('member.remove', [$organization->id, $member->userID]) }}" method="POST" onsubmit="return confirm('Xóa thành viên này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted text-center mb-0">Chưa có thành viên.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="background-color: #FACC15;">
            <h4 style="margin: 0; font-size: 1.3rem;">Chờ duyệt ({{ $pendingMemberList->count() }})</h4>
        </div>
        <div class="card-body" style="background-color: #FFE86E;">
            @forelse ($pendingMemberList as $member)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: rgba(255,255,255,0.6);">
                    <span><strong>{{ $member->user?->name ?? '#' . $member->userID }}</strong> <small class="text-muted">{{ $member->user?->email }}</small></span>
                </div>
            @empty
                <p class="text-muted text-center mb-0">Không có yêu cầu chờ duyệt.</p>
            @endforelse
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card w-100 mb-3">
        <div class="card-header" style="background-color: #FACC15;"><h4 style="margin: 0;">Thêm thành viên</h4></div>
        <div class="card-body" style="background-color: #FFE86E;">
            <form action="{{ route('share.organization', $organization->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="user_list_m" class="form-label">Email thành viên</label>
                    <input type="text" class="form-control" id="user_list_m" name="user_list_text" placeholder="member@example.com" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Gửi lời mời</button>
            </form>
        </div>
    </div>
    @foreach ($currentMemberList as $member)
        <div class="card w-100 mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span>{{ $member->user?->name }}</span>
                @if ($member->userID !== $organization->hostID)
                    <form action="{{ route('member.remove', [$organization->id, $member->userID]) }}" method="POST">@csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                    </form>
                @else
                    <span class="badge text-bg-warning">Host</span>
                @endif
            </div>
        </div>
    @endforeach
@endsection
