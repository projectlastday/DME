<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#b45309">
        <title>@yield('title', config('app.name', 'DME'))</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        @stack('head')
    </head>
    <body class="app-page app-shell-body dme-layout-body">
        <div class="dme-app-shell">
            <div class="dme-app-shell__aurora dme-app-shell__aurora--top" aria-hidden="true"></div>
            <div class="dme-app-shell__aurora dme-app-shell__aurora--bottom" aria-hidden="true"></div>

            <div @class([
                'dme-app-shell__frame',
                'dme-app-shell__frame--centered' => trim($__env->yieldContent('page_layout')) === 'centered',
            ])>
                @auth
                    @unless (trim($__env->yieldContent('hide_navigation')) === 'true')
                        <x-shared.navigation />
                    @endunless
                @endauth

                @unless (trim($__env->yieldContent('hide_shell_header')) === 'true')
                    <header class="dme-app-shell__header">
                        <div @class([
                            'dme-app-shell__header-inner',
                            'dme-app-shell__header-inner--centered' => trim($__env->yieldContent('page_layout')) === 'centered',
                        ])>
                            <div>
                                @php($eyebrow = trim($__env->yieldContent('eyebrow', 'DME')))
                                @if ($eyebrow !== '')
                                    <p class="dme-app-shell__eyebrow">{{ $eyebrow }}</p>
                                @endif
                                <h1 class="dme-app-shell__title">
                                    @hasSection('page_title')
                                        @yield('page_title')
                                    @elseif (trim($__env->yieldContent('title')))
                                        @yield('title')
                                    @else
                                        {{ config('app.name', 'DME') }}
                                    @endif
                                </h1>
                                @hasSection('page_description')
                                    <p class="dme-app-shell__description">@yield('page_description')</p>
                                @endif
                            </div>

                            @hasSection('page_actions')
                                <div class="dme-app-shell__actions">
                                    @yield('page_actions')
                                </div>
                            @endif
                        </div>

                    </header>
                @endunless

                <main @class([
                    'dme-app-shell__content',
                    'dme-app-shell__content--centered' => trim($__env->yieldContent('page_layout')) === 'centered',
                    'dme-app-shell__content--bare' => trim($__env->yieldContent('surfaceless_content')) === 'true',
                ])>
                    @if (trim($__env->yieldContent('surfaceless_content')) === 'true')
                        <div @class([
                            'dme-content-card',
                            'dme-content-card--bare',
                            'dme-content-card--centered' => trim($__env->yieldContent('page_layout')) === 'centered',
                        ])>
                            <x-shared.flash-message />
                            <x-shared.form-errors />

                            @yield('content')
                        </div>
                    @else
                        <div @class([
                            'dme-content-card',
                            'dme-content-card--centered' => trim($__env->yieldContent('page_layout')) === 'centered',
                        ])>
                            <x-shared.flash-message />
                            <x-shared.form-errors />

                            @yield('content')
                        </div>
                    @endif
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
