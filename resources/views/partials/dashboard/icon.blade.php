@php($svgClass = $class ?? 'icon-svg')
@switch($name)
    @case('home')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 10.8 12 4l8 6.8"></path>
            <path d="M6.5 9.6v9.2a1.7 1.7 0 0 0 1.7 1.7h7.6a1.7 1.7 0 0 0 1.7-1.7V9.6"></path>
            <path d="M10 20.5v-5.2h4v5.2"></path>
        </svg>
        @break
    @case('book')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5.5 5.5A2.5 2.5 0 0 1 8 3h10.5v16H8a2.5 2.5 0 0 0-2.5 2.5z"></path>
            <path d="M5.5 5.5v16"></path>
            <path d="M9 7h6.5"></path>
            <path d="M9 10h5"></path>
        </svg>
        @break
    @case('clock')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="13" r="7.5"></circle>
            <path d="M9 3.5h6"></path>
            <path d="M12 5.5V3.5"></path>
            <path d="M12 9.5v4l3 1.8"></path>
        </svg>
        @break
    @case('calendar')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6.5 4.5h11A2.5 2.5 0 0 1 20 7v10.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5V7a2.5 2.5 0 0 1 2.5-2.5Z"></path>
            <path d="M8 3v3"></path>
            <path d="M16 3v3"></path>
            <path d="M4 9h16"></path>
            <path d="M8 13h2"></path>
            <path d="M14 13h2"></path>
            <path d="M8 16h2"></path>
        </svg>
        @break
    @case('notes')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3.8h8.5L19 7.3V19a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5.8a2 2 0 0 1 2-2Z"></path>
            <path d="M15.5 3.8v3.5H19"></path>
            <path d="M8.5 11h7"></path>
            <path d="M8.5 14h7"></path>
            <path d="M8.5 17h4"></path>
        </svg>
        @break
    @case('target')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="8.5"></circle>
            <circle cx="12" cy="12" r="4.5"></circle>
            <circle cx="12" cy="12" r="1.3"></circle>
            <path d="m17.8 6.2 2.2-2.2"></path>
            <path d="M16 8 20 4"></path>
        </svg>
        @break
    @case('trophy')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M8 4.5h8v3a4 4 0 0 1-8 0z"></path>
            <path d="M8 6.5H5.5a2 2 0 0 0 2 3.5H8"></path>
            <path d="M16 6.5h2.5a2 2 0 0 1-2 3.5H16"></path>
            <path d="M12 11.5v4"></path>
            <path d="M9 20h6"></path>
            <path d="M10 15.5h4"></path>
        </svg>
        @break
    @case('users')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="9.5" cy="9" r="3"></circle>
            <path d="M4.5 19a5 5 0 0 1 10 0"></path>
            <circle cx="17" cy="10" r="2.3"></circle>
            <path d="M15.5 16.5a4.1 4.1 0 0 1 4 2.5"></path>
        </svg>
        @break
    @case('chart')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.5 19.5h15"></path>
            <path d="M7 17v-5"></path>
            <path d="M12 17V7"></path>
            <path d="M17 17v-8"></path>
            <path d="m7 10 5-4 5 2"></path>
        </svg>
        @break
    @case('settings')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="3.3"></circle>
            <path d="M19.3 13.4a7.7 7.7 0 0 0 0-2.8l2-1.5-2-3.4-2.4 1a7.8 7.8 0 0 0-2.4-1.4L14.2 3h-4.4l-.3 2.3A7.8 7.8 0 0 0 7.1 6.7l-2.4-1-2 3.4 2 1.5a7.7 7.7 0 0 0 0 2.8l-2 1.5 2 3.4 2.4-1a7.8 7.8 0 0 0 2.4 1.4l.3 2.3h4.4l.3-2.3a7.8 7.8 0 0 0 2.4-1.4l2.4 1 2-3.4z"></path>
        </svg>
        @break
    @case('bell')
        <svg class="{{ $svgClass }}" data-icon="bell" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 10.8a6 6 0 0 0-12 0c0 4.8-2 5.7-2 5.7h16s-2-.9-2-5.7Z"></path>
            <path d="M9.5 19a2.6 2.6 0 0 0 5 0"></path>
            <path d="M12 4.8V3"></path>
        </svg>
        @break
    @case('pencil')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.5 19.5 6 14.2 16.3 3.9a2.2 2.2 0 0 1 3.1 3.1L9.1 17.3z"></path>
            <path d="m14.8 5.4 3.1 3.1"></path>
            <path d="M4.5 19.5h5.2"></path>
        </svg>
        @break
    @case('upload')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 15.5V4.5"></path>
            <path d="m7.7 8.8 4.3-4.3 4.3 4.3"></path>
            <path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"></path>
        </svg>
        @break
    @case('trash')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.5 7h15"></path>
            <path d="M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7"></path>
            <path d="m7 7 .8 12a2 2 0 0 0 2 1.8h4.4a2 2 0 0 0 2-1.8L17 7"></path>
            <path d="M10.5 11v5.5"></path>
            <path d="M13.5 11v5.5"></path>
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
            <path d="M12 8v4l2.5 2"></path>
        </svg>
@endswitch
