<section class="top">
    <div class="topbar">
        <div class="topbar-left">
            @isset($title)
                <h3 style="margin: 0;">{{ $title }}</h3>
            @endisset
        </div>
        <div class="topbar-container">
            @hasSection('topbar-actions')
                @yield('topbar-actions')
            @endif
        </div>
        <div class="topbar-right" style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('balance') }}" class="btn btn-primary rounded-pill" style="padding: 10px 20px; font-size: 1.2rem; font-weight: bold; text-decoration: none;">
                {{ number_format(auth()->user()->balance ?? 0, 0, ',', '.') }} <i class="fas fa-coins"></i>
            </a>
            <a href="{{ route('settings') }}" class="btn btn-primary rounded-circle" style="width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center; overflow: hidden;" title="Cài đặt">
                @if (auth()->user()->avatar_image_url)
                    <img src="{{ $user->avatar_image_url }}" style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar của {{ auth()->user()->name }}" class="rounded-circle">
                @else
                    <span style="font-size: 1.4rem; font-weight: bold; color: #000;">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                @endif
            </a>
        </div>
    </div>
</section>
