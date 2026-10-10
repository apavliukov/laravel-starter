@php
    use App\Enums\Policies\Role;

    $role = auth()->user()->app_role ?? Role::default();
    $layoutType = $role->layout();
    $layout = "admin.dashboard.$layoutType";
@endphp

<x-dynamic-component :component="$layout" />
