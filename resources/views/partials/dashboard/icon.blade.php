@php($svgClass = $class ?? 'icon-svg')
@switch($name)
    @case('home')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 11.5 12 4l9 7.5"></path>
            <path d="M5 10.5V20h14v-9.5"></path>
            <path d="M9.5 20v-5h5v5"></path>
        </svg>
        @break
    @case('book')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 4.5h10.5a2 2 0 0 1 2 2V20H8a2 2 0 0 1-2-2z"></path>
            <path d="M6 4.5a2 2 0 0 0-2 2V18a2 2 0 0 0 2 2"></path>
            <path d="M9 8h7"></path>
            <path d="M9 11h7"></path>
        </svg>
        @break
    @case('clock')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"></circle>
            <path d="M12 8v4l3 2"></path>
        </svg>
        @break
    @case('folder')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3.5 7.5h5l2 2H20.5v8.5a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2z"></path>
            <path d="M3.5 7.5v-1a2 2 0 0 1 2-2h4.5l2 2"></path>
        </svg>
        @break
    @case('notes')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 4.5h10l2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-12z"></path>
            <path d="M8.5 9h7"></path>
            <path d="M8.5 12h7"></path>
            <path d="M8.5 15h4.5"></path>
        </svg>
        @break
    @case('target')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"></circle>
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 3v3"></path>
            <path d="M21 12h-3"></path>
            <path d="M12 21v-3"></path>
            <path d="M3 12h3"></path>
        </svg>
        @break
    @case('trophy')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 4.5h8v2a4 4 0 0 1-8 0z"></path>
            <path d="M8 6.5H5.5A2 2 0 0 0 7.5 10h.7"></path>
            <path d="M16 6.5h2.5A2 2 0 0 1 16.5 10h-.7"></path>
            <path d="M10 14.5h4"></path>
            <path d="M12 10.5v4"></path>
            <path d="M9 19.5h6"></path>
        </svg>
        @break
    @case('users')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M16.5 18.5a4 4 0 0 0-9 0"></path>
            <circle cx="12" cy="9" r="3"></circle>
            <path d="M18.5 19.5a3 3 0 0 0-3-3"></path>
            <path d="M16 6a2.5 2.5 0 1 1 0 5"></path>
        </svg>
        @break
    @case('chart')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 19.5h14"></path>
            <path d="M7 17V10"></path>
            <path d="M12 17V6.5"></path>
            <path d="M17 17v-4.5"></path>
        </svg>
        @break
    @case('settings')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="3.5"></circle>
            <path d="M19 12a7 7 0 0 0-.1-1.1l2-1.5-2-3.5-2.3.7a7.8 7.8 0 0 0-1.9-1.1l-.4-2.4h-4l-.4 2.4a7.8 7.8 0 0 0-1.9 1.1l-2.3-.7-2 3.5 2 1.5A7 7 0 0 0 5 12a7 7 0 0 0 .1 1.1l-2 1.5 2 3.5 2.3-.7a7.8 7.8 0 0 0 1.9 1.1l.4 2.4h4l.4-2.4a7.8 7.8 0 0 0 1.9-1.1l2.3.7 2-3.5-2-1.5c.1-.4.1-.7.1-1.1Z"></path>
        </svg>
        @break
    @case('laravel')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 7.5 12 4l5 3.5-5 3.5z"></path>
            <path d="M7 7.5v6L12 17l5-3.5v-6"></path>
            <path d="M12 11v6"></path>
        </svg>
        @break
    @case('java')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 16.5h7"></path>
            <path d="M9.5 19.5h5"></path>
            <path d="M11 4.5c1.5 2 3 2.5 3 4 0 1.5-1.1 2.2-1.1 3.4"></path>
            <path d="M12 11.8c0 1.7-1.2 2.4-1.2 3.4 0 .9.7 1.3 1.2 1.3"></path>
        </svg>
        @break
    @case('database')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <ellipse cx="12" cy="6" rx="6.5" ry="2.5"></ellipse>
            <path d="M5.5 6v6c0 1.4 2.9 2.5 6.5 2.5s6.5-1.1 6.5-2.5V6"></path>
            <path d="M5.5 12v6c0 1.4 2.9 2.5 6.5 2.5s6.5-1.1 6.5-2.5v-6"></path>
        </svg>
        @break
    @case('code')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="m9 7-4 5 4 5"></path>
            <path d="m15 7 4 5-4 5"></path>
            <path d="M13 5.5 11 18.5"></path>
        </svg>
        @break
    @case('language')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"></circle>
            <path d="M3.8 12h16.4"></path>
            <path d="M12 3.5c2.5 2.3 4 5 4 8.5s-1.5 6.2-4 8.5c-2.5-2.3-4-5-4-8.5s1.5-6.2 4-8.5Z"></path>
        </svg>
        @break
    @case('fire')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3.5c2 3.4-.5 4.2 1.8 6.7 1.7 1.8 4.2 2.3 4.2 5.6a6 6 0 0 1-12 0c0-2.8 1.8-4.5 3.2-6.4.9-1.2 1.4-3.1 2.8-5.9Z"></path>
            <path d="M10.5 15.5c0-1.4.8-2.2 1.5-3 1 1 2 1.7 2 3.4a2 2 0 0 1-4 0c0-.2 0-.3.5-.4Z"></path>
        </svg>
        @break
    @case('medal')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="13" r="5.5"></circle>
            <path d="M9.5 4.5 7.5 9"></path>
            <path d="M14.5 4.5 16.5 9"></path>
            <path d="m10.5 13.5 1.2 1.2 2.8-3"></path>
        </svg>
        @break
    @case('sun')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 3.5v2.5"></path>
            <path d="M12 18v2.5"></path>
            <path d="M3.5 12H6"></path>
            <path d="M18 12h2.5"></path>
            <path d="m5.3 5.3 1.8 1.8"></path>
            <path d="m16.9 16.9 1.8 1.8"></path>
            <path d="m18.7 5.3-1.8 1.8"></path>
            <path d="m6.1 16.9-1.8 1.8"></path>
        </svg>
        @break
    @default
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"></circle>
        </svg>
@endswitch
