<nav class="navbar bg-base-100 border-b border-base-300 sticky top-0 z-50">
    <div class="navbar-start">
        <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                <li><a href="{{ route('activities.index') }}">Activiteiten</a></li>
                <li><a href="{{ route('themes.index') }}">Kalender</a></li>
                <li><a href="{{ route('authors.index') }}">Bijdragers</a></li>
            </ul>
        </div>
        <a href="{{ route('home') }}" class="btn btn-ghost text-xl font-semibold">
            <div class="bg-primary p-2 rounded-lg hover:bg-primary/80 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary-content fill-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" style="color:white"></path>
                </svg>
            </div> <h3 class="ml-1">hartverwarmers</h3>
        </a>
    </div>

    <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal px-1 gap-2">
            <li><a href="{{ route('activities.index') }}" class="font-medium">Activiteiten</a></li>
            <li><a href="{{ route('themes.index') }}" class="font-medium">Kalender</a></li>
            <li><a href="{{ route('authors.index') }}" class="font-medium">Bijdragers</a></li>
        </ul>
    </div>

    <div class="navbar-end gap-2">
        @auth
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                        <span class="text-sm font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                    </div>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    <li><a href="{{ route('profile.show') }}">Mijn profiel</a></li>
                    <li><a href="{{ route('profile.bookmarks') }}">Mijn bookmarks</a></li>
                    @if(Route::has('logout'))
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left">Uitloggen</button>
                            </form>
                        </li>
                    @endif
                </ul>
            </div>
        @else
            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Inloggen</a>
            @endif
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Registreren</a>
            @endif
        @endauth
    </div>
</nav>
