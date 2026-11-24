<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#9560d9">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', 'Dashboard') · SharpMind</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php($pageAsset = trim($__env->yieldContent('page', 'dashboard')))
    @vite(['resources/css/'.$pageAsset.'.css', 'resources/js/'.$pageAsset.'.js'])
    @stack('styles')
</head>

<body class="workspace-body" data-page="{{ $pageAsset }}">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <div class="app-shell">
        <div class="sidebar-overlay" data-sidebar-overlay></div>
        <aside class="sidebar" id="sidebar" aria-label="Main navigation">
            <button type="button" class="icon-button sidebar-close" data-toggle-sidebar aria-label="Close navigation"><x-icon name="close" /></button>
            <x-brand :caption="true" />
            <p class="sidebar-section-label">YOUR WORKSPACE</p>
            <nav class="sidebar-nav">
                @foreach(['dashboard' => ['Dashboard', 'dashboard'], 'members' => ['Members', 'members'], 'transactions' => ['Transactions', 'transactions'], 'categories' => ['Categories', 'categories'], 'levels' => ['Levels', 'levels']] as $key => [$label, $icon])
                @php($isActive = $key === $pageAsset)
                <a class="nav-item {{ $isActive ? 'active' : '' }}" href="{{ route($key === 'dashboard' ? 'dashboard' : $key.'.index') }}" @if($isActive) aria-current="page" @endif><x-icon :name="$icon" /><span>{{ $label }}</span>@if($isActive)<span class="nav-arrow" aria-hidden="true">›</span>@endif</a>
                @endforeach
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-callout"><x-icon name="sparkles" />
                    <h3>A sharper tomorrow.</h3>
                    <p>A little learning today.<br>A world of possibility ahead.</p>
                </div>
                <div class="sidebar-account">
                    <div class="sidebar-profile">
                        <span class="avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <div class="sidebar-profile-copy">
                            <span class="sidebar-profile-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                            <span class="sidebar-profile-role">Administrator</span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="icon-button sidebar-logout" type="submit" aria-label="Log out" title="Log out"><x-icon name="logout" /></button>
                    </form>
                </div>
            </div>
        </aside>
        <div class="workspace">
            <main class="page-content" id="main-content">
            <button class="icon-button mobile-menu no-print" type="button" data-toggle-sidebar aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu" /></button>
                @if(session('success'))<div class="alert alert-success" role="status"><x-icon name="check-circle" /><span>{{ session('success') }}</span></div>@endif
                @if(session('error'))<div class="alert alert-error" role="alert"><x-icon name="info" /><span>{{ session('error') }}</span></div>@endif
                @if($errors->any() && !old('_editor'))<div class="alert alert-error" role="alert"><x-icon name="info" />
                    <div><strong>Please check the following:</strong>
                        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                </div>@endif
                @yield('content')
                <footer class="workspace-footer"><span>© {{ date('Y') }} <strong>SharpMind.</strong> All rights reserved.</span><span>Made for brighter minds <span aria-hidden="true">✧</span></span></footer>
            </main>
        </div>
    </div>
    <x-dialog id="confirm-dialog" title="Are you sure?" description="Please review this action before continuing.">
        <div class="dialog-body">
            <p class="text-muted text-small" data-confirm-message></p>
        </div>
        <div class="dialog-footer"><button type="button" class="button button-secondary" data-close-dialog>Keep record</button><button type="button" class="button button-danger" data-confirm-submit>Delete record</button></div>
    </x-dialog>
    @stack('scripts')
</body>

</html>
