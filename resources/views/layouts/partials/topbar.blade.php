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
            @endhasSection
        </div>
        <div class="topbar-right" style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('balance') }}" class="btn btn-primary rounded-pill" style="padding: 10px 20px; font-size: 1.2rem; font-weight: bold; text-decoration: none;">
                {{ number_format(auth()->user()->balance ?? 0, 0, ',', '.') }} <i class="fas fa-coins"></i>
            </a>
            <a href="{{ route('settings') }}" class="btn btn-primary rounded-circle" style="width: 50px; height: 50px; padding: 0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                @if (auth()->user()->avatar_image_url)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_image_url) }}" style="width: 40px; height: 40px; object-fit: cover;" alt="Profile" class="rounded-circle">
                @else
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQMZjYeTW5dVKQpVxWLdjUqQTgjkSBVfVzONaCzFqnpig&s" style="width: 40px; height: 40px;" alt="Profile" class="rounded-circle">
                @endif
            </a>
        </div>
    </div>
</section>
