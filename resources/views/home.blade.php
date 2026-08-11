@extends('layouts.app')

@section('title', 'Noteket - Trang chủ')

@section('content')
    @if ($notes->isEmpty())
        <div class="text-center py-5">
            <div style="font-size: 4rem;">📌</div>
            <h3>Chưa có ghi chú nào</h3>
            <p class="text-muted">Tạo ghi chú đầu tiên của bạn để bắt đầu!</p>
        </div>
    @endif

    <div class="row justify-content-center g-4">
        {{-- Create note card (widescreen) --}}
        <div class="col-lg-5 col-12">
            <div class="card note-card" data-card-mode="VIEW" data-note-id="">
                <div class="card-header note-header" style="background-color: #FACC15; padding: 7px; display: flex; justify-content: center; align-items: center;">
                    <p>Tạo ghi chú</p>
                </div>
                <div class="card-body rounded" style="background-color: #FFE86E; padding: 20px;">
                    <form action="{{ route('create.note') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="noteTitle" class="form-label">Tiêu đề ghi chú</label>
                            <input type="text" class="form-control" id="noteTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="noteContent" class="form-label">Nội dung ghi chú</label>
                            <textarea class="form-control bigform" id="noteContent" name="description" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Tạo ghi chú</button>
                    </form>
                </div>
                <div class="note-overlay" aria-hidden="true">
                    <div class="overlay-box">Tạo ghi chú</div>
                </div>
            </div>
        </div>

        {{-- Existing note cards --}}
        @foreach ($notes as $note)
            <div class="col-lg-5 col-12">
                <div class="card note-card" data-card-mode="VIEW" data-note-id="{{ $note->id }}">
                    <div class="card-header note-header" style="position: relative;">
                        <p>Tạo lúc: {{ $note->created_at?->format('Y-m-d H:i') }}</p>
                        <button class="pin-btn" aria-label="More" type="button">📌</button>
                        <div class="note-menu" role="menu">
                            <button type="button" data-action="mark-done">{{ in_array($note->id, $doneNoteIds) ? 'Hoàn tác' : 'Đánh dấu hoàn thành' }}</button>
                            <button type="button" data-action="edit">Sửa</button>
                            <button type="button" data-action="share">Chia sẻ</button>
                            <button type="button" data-action="reply">Trả lời</button>
                            <button type="button" data-action="delete">Xóa</button>
                        </div>
                    </div>
                    <div class="card-body rounded" style="background-color: #FFE86E; padding: 20px;">
                        <a href="{{ route('note', $note->id) }}" style="text-decoration: none; color: inherit;">
                            <h3 class="card-title">{{ $note->title }}</h3>
                        </a>
                        <p class="card-text">{{ \Illuminate\Support\Str::limit($note->description, 200) }}</p>
                        <p class="card-text text-secondary">Bởi: {{ $note->creater?->name ?? 'Bạn' }}</p>
                        @if (in_array($note->id, $doneNoteIds))
                            <span class="badge rounded-pill text-bg-success">✓ Hoàn thành</span>
                        @endif
                    </div>
                    <div class="note-overlay" aria-hidden="true">
                        <div class="overlay-box">Đánh dấu hoàn thành</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('content-mobile')
    @forelse ($notes as $note)
        <div class="card note-card" data-card-mode="VIEW" data-note-id="{{ $note->id }}">
            <div class="card-header note-header" style="position: relative;">
                <p>Tạo lúc: {{ $note->created_at?->format('Y-m-d H:i') }}</p>
                <button class="pin-btn" aria-label="More" type="button">📌</button>
                <div class="note-menu" role="menu">
                    <button type="button" data-action="mark-done">{{ in_array($note->id, $doneNoteIds) ? 'Hoàn tác' : 'Đánh dấu hoàn thành' }}</button>
                    <button type="button" data-action="edit">Sửa</button>
                    <button type="button" data-action="share">Chia sẻ</button>
                    <button type="button" data-action="reply">Trả lời</button>
                    <button type="button" data-action="delete">Xóa</button>
                </div>
            </div>
            <div class="card-body rounded" style="background-color: #FFE86E; padding: 20px;">
                <a href="{{ route('note', $note->id) }}" style="text-decoration: none; color: inherit;">
                    <h3 class="card-title">{{ $note->title }}</h3>
                </a>
                <p class="card-text">{{ \Illuminate\Support\Str::limit($note->description, 200) }}</p>
                <p class="card-text text-secondary">Bởi: {{ $note->creater?->name ?? 'Bạn' }}</p>
                @if (in_array($note->id, $doneNoteIds))
                    <span class="badge rounded-pill text-bg-success">✓ Hoàn thành</span>
                @endif
            </div>
            <div class="note-overlay" aria-hidden="true">
                <div class="overlay-box">Đánh dấu hoàn thành</div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <div style="font-size: 4rem;">📌</div>
            <h3>Chưa có ghi chú nào</h3>
        </div>
    @endforelse
@endsection

@section('mobile-fab')
    <button class="fab-add-btn btn btn-primary rounded-circle" id="fabBtn" onclick="toggleMobileCardSwap()" title="Tạo Note Mới" style="position: fixed; bottom: 100px; right: 20px; width: 60px; height: 60px; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; z-index: 2000;">
        <i class="fas fa-plus"></i>
    </button>
@endsection
