@props(['user', 'class' => 'friend-avatar'])

@php
    $displayName = $user?->displayName() ?: 'Usuario';
    $photoUrl = $user?->profilePhotoUrl();
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($displayName, 0, 1));
@endphp

@if ($photoUrl)
    <img src="{{ $photoUrl }}" alt="Foto de {{ $displayName }}" class="{{ $class }} friend-avatar-image">
@else
    <span class="{{ $class }}">{{ $initial }}</span>
@endif
