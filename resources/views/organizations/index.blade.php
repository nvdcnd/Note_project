@extends('layouts.app')

@section('title', 'Noteket - Tổ chức')

@section('topbar-actions')
    <a href="{{ route('organizations.create') }}" class="btn btn-primary rounded-pill" style="padding: 10px 20px; font-size: 1.1rem;">
        <i class="fas fa-plus"></i> Tạo tổ chức
    </a>
@endsection

@section('content')
    @if ($hostedOrganizations->isEmpty() && $memberOrganizations->isEmpty() && $pendingOrganizations->isEmpty())
        <div class="text-center py-5">
            <div style="font-size: 4rem;">🏢</div>
            <h3>Chưa tham gia tổ chức nào</h3>
            <a href="{{ route('organizations.create') }}" class="btn btn-primary rounded-pill mt-3">Tạo tổ chức đầu tiên</a>
        </div>
    @endif

    <div class="row g-4">
        @foreach ($hostedOrganizations as $organization)
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <a href="{{ route('organization', $organization->id) }}" style="text-decoration: none; color: #000;">
                            <h5 class="card-title" style="font-size: 1.6rem;">{{ $organization->name }}</h5>
                        </a>
                        <p class="card-text">{{ Str::limit($organization->description, 100) }}</p>
                        <p class="card-text"><small class="text-body-secondary">Chủ sở hữu: {{ $organization->host?->name ?? 'Bạn' }} (bạn)</small></p>
                        <a href="{{ route('organization.dashboard', $organization->id) }}" class="btn btn-primary">Bảng điều hành</a>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach ($memberOrganizations as $organization)
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <a href="{{ route('organization', $organization->id) }}" style="text-decoration: none; color: #000;">
                            <h5 class="card-title" style="font-size: 1.6rem;">{{ $organization->name }}</h5>
                        </a>
                        <p class="card-text">{{ Str::limit($organization->description, 100) }}</p>
                        <p class="card-text"><small class="text-body-secondary">Chủ sở hữu: {{ $organization->host?->name ?? 'Không rõ' }}</small></p>
                        <a href="{{ route('organization', $organization->id) }}" class="btn btn-primary">Mở tổ chức</a>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach ($pendingOrganizations as $pending)
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100" style="border: 2px dashed var(--nk-yellow);">
                    <div class="card-body">
                        <h5 class="card-title" style="font-size: 1.4rem;">{{ $pending->organization?->name ?? 'Tổ chức' }}</h5>
                        <p class="card-text"><small class="text-body-secondary">Lời mời đang chờ xử lý</small></p>
                        <div class="d-flex gap-2">
                            <form action="{{ route('member.accept', $pending->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">Chấp nhận</button>
                            </form>
                            <form action="{{ route('member.decline', $pending->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">Từ chối</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('content-mobile')
    @foreach ($hostedOrganizations->merge($memberOrganizations) as $organization)
        <div class="card mb-3 w-100">
            <div class="card-body">
                <a href="{{ route('organization', $organization->id) }}" style="text-decoration: none; color: #000;">
                    <h5 class="card-title">{{ $organization->name }}</h5>
                </a>
                <p class="card-text"><small class="text-body-secondary">Chủ sở hữu: {{ $organization->host?->name ?? 'Không rõ' }}</small></p>
                <a href="{{ route('organization.dashboard', $organization->id) }}" class="btn btn-primary">Bảng điều hành</a>
            </div>
        </div>
    @endforeach
    @foreach ($pendingOrganizations as $pending)
        <div class="card mb-3 w-100" style="border: 2px dashed var(--nk-yellow);">
            <div class="card-body">
                <h5 class="card-title">{{ $pending->organization?->name ?? 'Tổ chức' }}</h5>
                <div class="d-flex gap-2">
                    <form action="{{ route('member.accept', $pending->id) }}" method="POST">@csrf<button type="submit" class="btn btn-primary">Chấp nhận</button></form>
                    <form action="{{ route('member.decline', $pending->id) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger">Từ chối</button></form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
