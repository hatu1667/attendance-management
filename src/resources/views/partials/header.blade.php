@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

$isSimple = Route::is(['login*', 'register*', 'admin.login*']);
$isAdmin = Auth::guard('admin')->check();

$active = function(array|string $patterns) {
foreach ((array)$patterns as $p) if (Route::is($p)) return ' is-active';
return '';
};
@endphp

@if ($isSimple)
<header class="site-header site-header--simple">
    <div class="site-header__inner">
        <div class="site-header__brand">
            <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="site-header__logo">
        </div>
    </div>
</header>
@else
<header class="site-header site-header--nav">
    <div class="site-header__inner">
        <div class="site-header__brand">
            <img src="{{ asset('images/logo.svg') }}" alt="COACHTECH" class="site-header__logo">
        </div>

        <nav class="site-nav" aria-label="メインメニュー">
            <ul class="site-nav__list">
                @if ($isAdmin)
                <li><a href="{{ route('admin.attendance.list') }}" class="site-nav__link{{ $active(['admin.attendance.*']) }}">勤怠一覧</a></li>
                <li><a href="{{ route('admin.staff.index') }}" class="site-nav__link{{ $active(['admin.staff.*']) }}">スタッフ一覧</a></li>

                {{-- ✅ 管理者は admin 側へ --}}
                <li><a href="{{ route('admin.requests.index', ['status' => 'pending']) }}" class="site-nav__link{{ $active(['admin.requests.*']) }}">申請一覧</a></li>

                <li>
                    <form method="post" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="site-nav__link site-nav__logout">ログアウト</button>
                    </form>
                </li>
                @else
                <li><a href="{{ route('attendance.index') }}" class="site-nav__link{{ $active(['attendance.index']) }}">勤怠</a></li>
                <li><a href="{{ route('attendance.list', ['ym' => now()->format('Y-m')]) }}" class="site-nav__link{{ $active(['attendance.list']) }}">勤怠一覧</a></li>

                {{-- ✅ 一般ユーザーは一般側へ --}}
                <li><a href="{{ route('requests.index', ['status' => 'pending']) }}" class="site-nav__link{{ $active(['requests.*']) }}">申請</a></li>

                <li>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="site-nav__link site-nav__logout">ログアウト</button>
                    </form>
                </li>
                @endif
            </ul>
        </nav>
    </div>
</header>
@endif