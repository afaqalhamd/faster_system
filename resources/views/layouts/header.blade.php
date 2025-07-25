<!--start header -->
<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand gap-3">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>

            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center gap-1">
                    <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="alert-count">{{ auth()->user()->unreadNotifications->count() }}</span>
                            <i class='bx bx-bell'></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:;">
                                <div class="msg-header">
                                    <p class="msg-header-title">{{ __('app.notifications') }}</p>
                                    @if(auth()->user()->unreadNotifications->count() > 0)
                                        <p class="msg-header-clear ms-auto">
                                            <a href="{{ route('notifications.markAllAsRead') }}">{{ __('app.mark_all_as_read') }}</a>
                                        </p>
                                    @endif
                                </div>
                            </a>
                            <div class="header-notifications-list">
                                @foreach(auth()->user()->unreadNotifications as $notification)
                                    <a class="dropdown-item" href="{{ $notification->data['url'] }}">
                                        <div class="d-flex align-items-center">
                                            <div class="notify bg-light-primary text-primary">
                                                <i class="bx bx-cart-alt"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="msg-name">{{ $notification->data['title'] }}</h6>
                                                <p class="msg-info">{{ $notification->data['message'] }}</p>
                                                <p class="msg-time">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </li>

                    @can('purchase.order.create')
                    <li class="nav-item">
                        <a href="{{ route('purchase.order.list') }}" class="btn btn-sm btn-outline-primary radius-30 px-4" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ __('purchase.add') }}">
                            <i class="bx bx-plus-circle mr-1"></i>{{ __('purchase.purchase') }}
                        </a>
                    </li>
                    @endcan

                    @can('sale.order.create')
                    <li class="nav-item">
                        <a href="{{ route('sale.order.list') }}" class="btn btn-sm btn-outline-danger radius-30 px-4" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ __('sale.add') }}">
                            <i class="bx bx-plus-circle mr-1"></i>{{ __('sale.sale') }}
                        </a>
                    </li>
                    @endcan

                    @can('sale.invoice.create')
                    <li class="nav-item">
                        <a href="{{ route('pos.create') }}" class="btn btn-sm btn-success radius-30 px-4">
                            <i class="bx bx-plus-circle mr-1"></i>{{ __('sale.pos') }}
                        </a>
                    </li>
                    @endcan

                    <x-header-shortcut-menu />
                    <x-flag-toggle />

                    <li class="nav-item dark-mode d-none d-sm-flex">
                        <a class="nav-link dark-mode-icon theme-mode" data-base-url="{{ url('/') }}" href="javascript:;">
                            <i class='bx bx-moon'></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="user-box dropdown px-3">
                <a class="d-flex align-items-center nav-link dropdown-toggle gap-3 dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ url('/users/getimage/' . auth()->user()->avatar) }}" class="user-img" alt="user avatar">
                    <div class="user-info">
                        <p class="user-name mb-0">{{ auth()->user()->username }}</p>
                        <p class="designattion mb-0">{{ auth()->user()->role->name }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('user.profile') }}"><i class="bx bx-user fs-5"></i><span>{{ __('user.profile') }}</span></a>
                    </li>
                    <li>
                        <div class="dropdown-divider mb-0"></div>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                        @csrf
                            <button type="submit" class="dropdown-item d-flex align-items-center"><i class="bx bx-log-out-circle"></i>{{ __('auth.logout') }}</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>
<!--end header -->
