@extends('layouts.app')

@section('title', 'Bảng điều hành - '.$organization->name)

@section('topbar-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('organization.members', $organization->id) }}" class="btn btn-primary rounded-pill" style="padding: 10px 16px;">Thành viên</a>
        <a href="{{ route('organization.balance', $organization->id) }}" class="btn btn-primary rounded-pill" style="padding: 10px 16px;">Ví tổ chức</a>
    </div>
@endsection

@section('content')
    <h2 class="text-center mb-4">Bảng điều hành - {{ $organization->name }}</h2>

    <div class="row g-4">
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ $allNotes }}</h5>
                    <p class="card-text">Tổng ghi chú</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ $undoneNotes }}</h5>
                    <p class="card-text">Chưa hoàn thành</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ $doneNotes }}</h5>
                    <p class="card-text">Đã hoàn thành</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ $currentMembers }}</h5>
                    <p class="card-text">Thành viên hiện tại</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ $pendingMembers }}</h5>
                    <p class="card-text">Thành viên chờ duyệt</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title" style="font-weight: bold; font-size: 2rem;">{{ number_format($organization->balance, 0, ',', '.') }}</h5>
                    <p class="card-text">Số dư (điểm)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header" style="background-color: var(--nk-yellow);">
            <h4 style="margin: 0; font-size: 1.3rem;">Thành viên hiện tại</h4>
        </div>
        <div class="card-body" style="background-color: var(--nk-sticky);">
            @forelse ($currentMemberList as $member)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: rgba(255,255,255,0.6);">
                    <span><strong>{{ $member->user?->name }}</strong> <small class="text-muted">{{ $member->user?->email }}</small></span>
                    @if ($member->userID !== $organization->hostID)
                        <form action="{{ route('member.remove', [$organization->id, $member->userID]) }}" method="POST" onsubmit="return confirm('Xóa thành viên này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    @else
                        <span class="badge rounded-pill text-bg-warning">Host</span>
                    @endif
                </div>
            @empty
                <p class="text-muted text-center mb-0">Chưa có thành viên.</p>
            @endforelse
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header" style="background-color: var(--nk-yellow);">
            <h4 style="margin: 0; font-size: 1.3rem;">Thành viên chờ duyệt</h4>
        </div>
        <div class="card-body" style="background-color: var(--nk-sticky);">
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
