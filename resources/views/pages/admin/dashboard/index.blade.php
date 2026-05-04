@php
    $role = auth()->user()->app_role;
    $layoutType = $role->layout();
    $layout = "admin.dashboard.$layoutType";
@endphp

<x-dynamic-component :component="$layout" />
