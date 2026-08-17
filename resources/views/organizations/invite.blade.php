@extends('layouts.app')

@section('title', 'Lời mời vào '.$org->name.' - Noteket')

@section('content')
    <div class="d-flex justify-content-center">
        <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
            <div class="card-body text-center">
                @if ($org->logo_url)
                    <img src="{{ $org->logo_url }}" alt="Logo {{ $org->name }}" class="rounded-circle mb-3" style="width: 72px; height: 72px; object-fit: cover;">
                @endif
                <h2 class="card-title">{{ $org->name }}</h2>
                @if ($org->description)
                    <p class="text-muted">{{ $org->description }}</p>
                @endif
                <p class="card-text">
                    Bạn ({{ $user->email }}) được mời tham gia tổ chức này.
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <form action="{{ route('member.accept', $member->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Tham gia tổ chức</button>
                    </form>
                    <form action="{{ route('member.decline', $member->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Từ chối</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content-mobile')
    <div class="card shadow-sm">
        <div class="card-body text-center">
            @if ($org->logo_url)
                <img src="{{ $org->logo_url }}" alt="Logo {{ $org->name }}" class="rounded-circle mb-3" style="width: 72px; height: 72px; object-fit: cover;">
            @endif
            <h2 class="card-title">{{ $org->name }}</h2>
            <p class="card-text">
                Bạn ({{ $user->email }}) được mời tham gia tổ chức này.
            </p>
            <div class="d-flex gap-2 justify-content-center">
                <form action="{{ route('member.accept', $member->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Tham gia tổ chức</button>
                </form>
                <form action="{{ route('member.decline', $member->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Từ chối</button>
                </form>
            </div>
        </div>
    </div>
@endsection
