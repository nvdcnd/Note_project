@extends('layouts.app')

@section('title', 'Noteket - Trang chủ')

@section('topbar-actions')
    <div class="dropdown">
        <a class="btn drop dropdown-toggle rounded-pill" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:400px;">
            {{ $noteFilters[$noteFilter] ?? 'Tất cả ghi chú' }}
        </a>
        <ul class="dropdown-menu">
            @foreach ($noteFilters as $key => $label)
                <li>
                    <a class="dropdown-item {{ $key === $noteFilter ? 'active' : '' }}" href="{{ route('home', ['filter' => $key]) }}">{{ $label }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endsection

@section('content')

    <div class="note-layout">
        {{-- Create note card — đặt cạnh note deck, cùng kích thước 420px --}}
        <div class="card note-card note-card-create" data-card-mode="CREATE" data-note-id="">
            <div class="card-header note-header" style="background-color: var(--nk-yellow); display: flex; justify-content: center; align-items: center;">
                <p>Tạo ghi chú</p>
            </div>
            <div class="card-body rounded" style="background-color: var(--nk-sticky); padding: 20px;">
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

        {{-- Note deck — cạnh card tạo note, xếp chồng kiểu bộ bài --}}
        @if ($notes->isNotEmpty())
            <div class="note-deck">
                    @foreach ($notes as $note)
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
                            <div class="card-body rounded" style="background-color: var(--nk-sticky); padding: 20px;">
                                <a href="{{ route('note', $note->id) }}" style="text-decoration: none; color: inherit;">
                                    <h3 class="card-title">{{ $note->title }}</h3>
                                </a>
                                <span class="d-none note-full-description">{{ $note->description }}</span>
                                <p class="card-text">{{ \Illuminate\Support\Str::limit($note->description, 200) }}</p>
                                <p class="card-text text-secondary">Bởi: {{ $note->creater?->name ?? 'Bạn' }}</p>
                                <div style="display: flex;">
                                    @if (in_array($note->id, $doneNoteIds))
                                        <span class="badge rounded-pill text-bg-success">✓ Hoàn thành</span>
                                    @endif
                                    @if ($note->organizationID)
                                        <span class="badge rounded-pill text-bg-primary">Ghi trong Org.{{ $note->organizationID }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="note-overlay" aria-hidden="true">
                                <div class="overlay-box">Đánh dấu hoàn thành</div>
                            </div>
                        </div>
                    @endforeach
            </div>
            @else
            <div class="text-center py-5">
                <div style="font-size: 4rem;">📌</div>
                <h3>Chưa có ghi chú nào</h3>
            </div>
        @endif
    </div>

    @if ($notes->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $notes->links() }}
        </div>
    @endif
@endsection

@section('content-mobile')
    {{-- Note deck trên mobile: xếp chồng kiểu bộ bài, form tạo ẩn chỉ hiện khi bấm FAB --}}
    @if ($notes->isNotEmpty())
        <div class="note-deck mobile">
            @foreach ($notes as $note)
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
                    <div class="card-body rounded" style="background-color: var(--nk-sticky); padding: 20px;">
                        <a href="{{ route('note', $note->id) }}" style="text-decoration: none; color: inherit;">
                            <h3 class="card-title">{{ $note->title }}</h3>
                        </a>
                        <span class="d-none note-full-description">{{ $note->description }}</span>
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
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <div style="font-size: 4rem;">📌</div>
            <h3>Chưa có ghi chú nào</h3>
        </div>
    @endif

    @if ($notes->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $notes->links() }}
        </div>
    @endif
@endsection

@section('mobile-fab')
    <button class="fab-add-btn btn btn-primary rounded-circle" id="fabBtn" onclick="toggleMobileCardSwap()" title="Tạo Note Mới" style="position: fixed; bottom: 100px; right: 20px; width: 60px; height: 60px; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; z-index: 2000;">
        <i class="fas fa-plus"></i>
    </button>
@endsection
