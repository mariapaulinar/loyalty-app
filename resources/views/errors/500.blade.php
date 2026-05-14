@php
$guard = request()->segment(2);
$layout = (in_array($guard, ['affiliate', 'staff', 'partner', 'admin'])) ? $guard : 'member';
try {
    $home = route($layout.'.index');
} catch (\Throwable $e) {
    $home = route('redir.locale');
}
@endphp
@extends($layout . '.layouts.default')

@section('page_title', trans('common.500_title') . config('default.page_title_delimiter') . config('default.app_name'))

@section('content')
@include('errors.partials.error-content', ['code' => '500', 'icon' => 'alert-triangle', 'home' => $home])
@stop