@php
    $users = \Auth::user();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $languages = \App\Models\Utility::languages();

    $lang = isset($users->lang) ? $users->lang : 'en';
    if ($lang == null) {
        $lang = 'en';
    }
    // $LangName = \App\Models\Language::where('code',$lang)->first();
    // $LangName =\App\Models\Language::languageData($lang);
    $LangName = cache()->remember('full_language_data_' . $lang, now()->addHours(24), function () use ($lang) {
        return \App\Models\Language::languageData($lang);
    });

    $setting = \App\Models\Utility::settings();

    // Get unread notifications count based on user type (same logic as NotificationController)
    if (\Auth::user()->type == 'company') {
        $unreadNotificationsCount = \App\Models\Notification::where(function ($query) {
            $query
                ->whereIn('created_by', function ($subQuery) {
                    $subQuery
                        ->select('id')
                        ->from('users')
                        ->where('created_by', \Auth::user()->id);
                })
                ->orWhere('created_by', \Auth::user()->id);
        })
            ->where('is_read', false)
            ->count();
    } elseif (\Auth::user()->type == 'manager') {
        $unreadNotificationsCount = \App\Models\Notification::where(function ($query) {
            $query
                ->whereIn('created_by', function ($subQuery) {
                    $subQuery
                        ->select('id')
                        ->from('users')
                        ->where('manager_id', \Auth::user()->id);
                })
                ->orWhere('created_by', \Auth::user()->id);
        })
            ->where('is_read', false)
            ->count();
    } else {
        $unreadNotificationsCount = 0;
    }

    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::user()->id)
        ->where('seen', 0)
        ->count();
@endphp
@if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
    <header class="dash-header transprent-bg">
    @else
        <header class="dash-header">
@endif

<div class="header-wrapper" style="overflow:visible;">
    <div class="me-auto dash-mob-drp">
        <ul class="list-unstyled">
            <li class="dash-h-item mob-hamburger">
                <a href="#!" class="dash-head-link" id="mobile-collapse"
                    onclick="(function(e){e.preventDefault();e.stopPropagation();var s=document.getElementById('sidebar');if(!s)return;var isOpen=!s.classList.contains('active');s.classList.toggle('active',isOpen);document.body.classList.toggle('sidebar-open',!isOpen);var h=document.querySelector('.hamburger');if(h)h.classList.toggle('is-active',!isOpen);})(event)">
                    <div class="hamburger hamburger--arrowturn">
                        <div class="hamburger-box">
                            <div class="hamburger-inner"></div>
                        </div>
                    </div>
                </a>
            </li>

            <li class="dropdown dash-h-item drp-company">
                <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="theme-avtar">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=blue&color=fff"
                            class="img-fluid rounded-circle">
                    </span>
                    <span class="hide-mob ms-2">{{ __('Hi, ') }}{{ \Auth::user()->name }}!</span>
                    <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                </a>
                <div class="dropdown-menu dash-h-dropdown">

                    <a href="{{ route('profile') }}" class="dropdown-item">
                        <i class="ti ti-user text-dark"></i><span>{{ __('Profile') }}</span>
                    </a>

                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                        class="dropdown-item">
                        <i class="ti ti-power text-dark"></i><span>{{ __('Logout') }}</span>
                    </a>
                    <form id="frm-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                        {{ csrf_field() }}
                    </form>
                </div>
            </li>

            <!-- Search Form -->
            <li class="dash-h-item ms-2" style="position: relative;">
                <form class="d-flex mt-2" action="{{ route('search') }}" method="GET" id="search-form">
                    <div style="position: relative; width: 100%;">
                        <input class="form-control me-2" type="search" name="query" id="search-input" 
                            placeholder="{{ __('Search') }}" aria-label="Search" autocomplete="off">
                        <div id="autocomplete-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                    </div>
                    <button class="btn btn-outline-success btn-primary" type="submit">{{ __('Search') }}</button>
                </form>
            </li>

            <!-- Notifications next to Search -->
            <li class="dropdown dash-h-item drp-notification ms-2" style="position:relative">
                <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                    data-bs-display="static" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                    style="position:relative">
                    <span
                        style="display:inline-flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:50%; background:#f3f4f6;">
                        <i class="ti ti-bell"
                            @if ($unreadNotificationsCount > 0) style="color:#dc2626; font-size:22px;" @else style="font-size:22px; color:#374151;" @endif></i>
                    </span>
                    @if ($unreadNotificationsCount > 0)
                        <span class="bg-danger dash-h-badge"
                            style="min-width:20px; height:20px; line-height:18px; font-size:12px; border-radius:10px; text-align:center; position:absolute; top:-2px; right:-4px; padding:0 6px;">{{ $unreadNotificationsCount }}</span>
                        {{-- <span style="position:absolute; top:6px; right:6px; width:8px; height:8px; background:#dc2626; border-radius:50%;"></span> --}}
                    @endif
                </a>

                <div class="dropdown-menu dash-h-dropdown dropdown-menu-end"
                    style="z-index:99999 !important; position:absolute;">
                    <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>{{ __('Notifications') }}</span>
                        @if ($unreadNotificationsCount > 0)
                            <span class="badge bg-danger">{{ $unreadNotificationsCount }} {{ __('unread') }}</span>
                        @endif
                    </h6>

                    @php
                        // Get notifications based on user type (same logic as NotificationController)
                        if (\Auth::user()->type == 'company') {
                            $notifications = \App\Models\Notification::where(function ($query) {
                                $query
                                    ->whereIn('created_by', function ($subQuery) {
                                        $subQuery
                                            ->select('id')
                                            ->from('users')
                                            ->where('created_by', \Auth::user()->id);
                                    })
                                    ->orWhere('created_by', \Auth::user()->id);
                            })
                                ->latest()
                                ->take(5)
                                ->get();
                        } elseif (\Auth::user()->type == 'manager') {
                            $notifications = \App\Models\Notification::where(function ($query) {
                                $query
                                    ->whereIn('created_by', function ($subQuery) {
                                        $subQuery
                                            ->select('id')
                                            ->from('users')
                                            ->where('manager_id', \Auth::user()->id);
                                    })
                                    ->orWhere('created_by', \Auth::user()->id);
                            })
                                ->latest()
                                ->take(5)
                                ->get();
                        } else {
                            $notifications = collect();
                        }
                    @endphp

                    @forelse($notifications as $note)
                        <a href="{{ route('notifications.show', $note->id) }}"
                            class="dropdown-item {{ $note->is_read ? '' : 'fw-bold' }}"
                            @if (!$note->is_read) style="background:#fff5f5;border-left:3px solid #dc2626;" @endif>
                            <div class="d-flex align-items-start">
                                <span class="me-2"
                                    style="width:8px;height:8px;border-radius:50%;background:{{ $note->is_read ? '#9ca3af' : '#dc2626' }};margin-top:6px"></span>
                                <div>
                                    <div>{{ $note->data['message'] ?? 'Notification' }}</div>
                                    <small class="text-muted">{{ $note->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </a>
                    @empty
                        <span class="dropdown-item text-muted">{{ __('No new notifications') }}</span>
                    @endforelse

                    <div class="dropdown-divider"></div>
                    <a href="{{ route('notifications.index_web') }}" class="dropdown-item text-center text-primary">
                        {{ __('View All') }}
                    </a>
                </div>
            </li>
        </ul>
    </div>
    <div class="ms-auto">
        <ul class="list-unstyled">
            @impersonating($guard = null)
                <li class="dropdown dash-h-item drp-company">
                    <a class="btn btn-danger btn-sm me-3" href="{{ route('exit.company') }}"><i class="ti ti-ban"></i>
                        {{ __('Exit Company Login') }}
                    </a>
                </li>
            @endImpersonating

            {{-- @if (\Auth::user()->type != 'client' && \Auth::user()->type != 'super admin')
                    <li class="dropdown dash-h-item drp-notification">
                        <a class="dash-head-link arrow-none me-0" href="{{ url('chats') }}" aria-haspopup="false"
                           aria-expanded="false">
                            <i class="ti ti-brand-hipchat"></i>
                            <span class="bg-danger dash-h-badge message-toggle-msg  message-counter custom_messanger_counter beep"> {{ $unseenCounter }}<span
                                    class="sr-only"></span>
                            </span>
                        </a>
                    </li>
                @endif --}}

            {{-- Language selector hidden for now --}}
            {{-- <li class="dropdown dash-h-item drp-language">
                <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="ti ti-world nocolor"></i>
                    <span class="drp-text hide-mob">{{ ucfirst($LangName->full_name) }}</span>
                    <i class="ti ti-chevron-down drp-arrow nocolor"></i>
                </a>
                <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                    @foreach ($languages as $code => $language)
                        <a href="{{ route('change.language', $code) }}"
                            class="dropdown-item {{ $lang == $code ? 'text-primary' : '' }}">
                            <span>{{ ucFirst($language) }}</span>
                        </a>
                    @endforeach

                    <h></h>
                    @if (\Auth::user()->type == 'super admin')
                        <a data-url="{{ route('create.language') }}" class="dropdown-item text-primary"
                            data-ajax-popup="true" data-title="{{ __('Create New Language') }}">
                            {{ __('Create Language') }}
                        </a>
                        <a class="dropdown-item text-primary"
                            href="{{ route('manage.language', [isset($lang) ? $lang : 'english']) }}">{{ __('Manage Language') }}</a>
                    @endif
                </div>
            </li> --}}

        </ul>
    </div>
</div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const autocompleteResults = document.getElementById('autocomplete-results');
    let autocompleteTimeout;
    let selectedIndex = -1;

    if (!searchInput || !autocompleteResults) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(autocompleteTimeout);
        
        if (query.length < 1) {
            autocompleteResults.style.display = 'none';
            return;
        }

        autocompleteTimeout = setTimeout(function() {
            fetch('{{ route("search.autocomplete") }}?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        displayAutocompleteResults(data);
                    } else {
                        autocompleteResults.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                    autocompleteResults.style.display = 'none';
                });
        }, 300);
    });

    function displayAutocompleteResults(items) {
        autocompleteResults.innerHTML = '';
        selectedIndex = -1;

        items.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.style.cssText = 'padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;';
            div.textContent = item.label;
            div.setAttribute('data-url', item.url);
            
            div.addEventListener('mouseenter', function() {
                selectedIndex = index;
                updateSelection();
            });
            
            div.addEventListener('click', function() {
                window.location.href = item.url;
            });
            
            autocompleteResults.appendChild(div);
        });

        autocompleteResults.style.display = 'block';
    }

    function updateSelection() {
        const items = autocompleteResults.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.style.backgroundColor = '#f0f0f0';
            } else {
                item.style.backgroundColor = 'white';
            }
        });
    }

    searchInput.addEventListener('keydown', function(e) {
        const items = autocompleteResults.querySelectorAll('.autocomplete-item');
        
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection();
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            const selectedItem = items[selectedIndex];
            if (selectedItem) {
                const url = selectedItem.getAttribute('data-url');
                if (url) {
                    window.location.href = url;
                }
            }
        } else if (e.key === 'Escape') {
            autocompleteResults.style.display = 'none';
            selectedIndex = -1;
        }
    });

    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
            autocompleteResults.style.display = 'none';
        }
    });
});
</script>
