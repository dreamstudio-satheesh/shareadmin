<!-- resources/views/partials/sidebar.blade.php -->
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="17">
            </span>
        </a>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>Menu</span></li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <i class="mdi mdi-home-outline"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                 <li class="nav-item">
                    <a class="nav-link" href="{{ route('zerodha_accounts.index') }}">
                        <i class="mdi mdi-account-outline"></i>
                        <span>Accounts</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('instruments.index') }}">
                        <i class="mdi mdi-database-import"></i>
                        <span>Instruments</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ url('watchlist') }}">
                        <i class="mdi mdi-eye-outline"></i>
                        <span>Watchlist</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('orders.upload') }}">
                        <i class="mdi mdi-file-upload-outline"></i>
                        <span>Upload Orders</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('settings.edit') }}">
                        <i class="mdi mdi-cog-outline"></i>
                        <span>Settings</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>

    <div class="sidebar-background"></div>
</div>

<!-- End Sidebar -->