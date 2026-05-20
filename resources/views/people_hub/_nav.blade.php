<ul class="nav nav-pills mb-3" role="tablist" style="gap:4px;flex-wrap:wrap;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('people-hub.crew') ? 'active' : '' }}" href="{{ route('people-hub.crew') }}">
            <i class="ti ti-hierarchy-2 me-1"></i>{{ __('Crew') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('people-hub.squad') ? 'active' : '' }}" href="{{ route('people-hub.squad') }}">
            <i class="ti ti-users me-1"></i>{{ __('My Squad') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('people-hub.mentor') ? 'active' : '' }}" href="{{ route('people-hub.mentor') }}">
            <i class="ti ti-heart-handshake me-1"></i>{{ __('Mentor Buddy') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('people-hub.search') ? 'active' : '' }}" href="{{ route('people-hub.search') }}">
            <i class="ti ti-search me-1"></i>{{ __('Search Crew') }}
        </a>
    </li>
</ul>
