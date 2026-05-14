{{--
Premium Form Open Component

Clean form wrapper with proper attributes.
--}}
@props([
    'action' => null,
    'method' => 'POST',
    'files' => false,
    'class' => null,
])

<form 
    {!! $class ? 'class="' . $class . '"' : '' !!} 
    @if ($action) action="{{ $action }}" @endif
    @if ($method) method="{{ $method }}" @endif
    @if ($files) enctype="multipart/form-data" @endif
    {{ $attributes }}
>
    @csrf
