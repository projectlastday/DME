@php
    $user = auth()->user();
    $currentRole = $user?->role;
    $teacherRole = \App\Models\User::ROLE_TEACHER;
    $studentRole = \App\Models\User::ROLE_STUDENT;
    $adminRole = \App\Models\User::ROLE_SUPER_ADMIN;
    $routeExists = static fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);

    $homeRoute = match ($currentRole) {
        $adminRole => $routeExists('admin.dashboard') ? 'admin.dashboard' : null,
        $teacherRole => $routeExists('teacher.students.index') ? 'teacher.students.index' : null,
        $studentRole => $routeExists('student.dashboard') ? 'student.dashboard' : null,
        default => null,
    };

    $homeLabel = in_array($currentRole, [$teacherRole, $studentRole], true) ? 'Utama' : 'Dashboard';

    $adminNavLinks = $currentRole === $adminRole
        ? [
            ['label' => 'Dashboard', 'route' => $routeExists('admin.dashboard') ? route('admin.dashboard') : null],
            ['label' => 'Guru', 'route' => $routeExists('admin.teachers.index') ? route('admin.teachers.index') : null],
            ['label' => 'Murid', 'route' => $routeExists('admin.students.index') ? route('admin.students.index') : null],
            ['label' => 'Transaksi', 'route' => $routeExists('admin.transactions.index') ? route('admin.transactions.index') : null],
        ]
        : [];
@endphp

<nav class="dme-nav" aria-label="Navigasi global">
    <div class="dme-nav__group">
        @if ($currentRole === $adminRole)
            @foreach ($adminNavLinks as $link)
                @if ($link['route'])
                    <a href="{{ $link['route'] }}" class="dme-nav__icon-link" aria-label="{{ $link['label'] }}">
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endif
            @endforeach

        @elseif ($homeRoute)
            <a href="{{ route($homeRoute) }}" class="dme-nav__icon-link" aria-label="Halaman utama">
                @if (! in_array($currentRole, [$teacherRole, $studentRole], true))
                    <svg class="dme-nav__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.25 12 3l9 7.25V20a1 1 0 0 1-1 1h-5.5v-6.25h-5V21H4a1 1 0 0 1-1-1v-9.75Z" />
                    </svg>
                @endif
                <span>{{ $homeLabel }}</span>
            </a>
        @endif
    </div>

    <div class="dme-nav__group">
        @auth
            <details class="dme-nav__menu">
                <summary class="dme-nav__icon-link" aria-label="Akun">
                    <svg class="dme-nav__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 1 0-16 0m12-11a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />
                    </svg>
                    <span>{{ $user->name }}</span>
                </summary>

                <div class="dme-nav__menu-panel">
                    @if ($routeExists('profile.edit'))
                        <a href="{{ route('profile.edit') }}" class="dme-nav__menu-link">Profil</a>
                    @endif

                    @if ($routeExists('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dme-nav__menu-action">Logout</button>
                        </form>
                    @endif
                </div>
            </details>
        @else
            @if ($routeExists('login'))
                <a href="{{ route('login') }}" class="dme-nav__icon-link" aria-label="Masuk">
                    <svg class="dme-nav__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3m-4-5 5-5m0 0-5-5m5 5H3" />
                    </svg>
                    <span>Masuk</span>
                </a>
            @endif
        @endauth
    </div>
</nav>
