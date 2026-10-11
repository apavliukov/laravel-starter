@php
    use App\Support\Areas\CurrentLayout;

    $layout = 'admin.dashboard.'.resolve(CurrentLayout::class)->name();
@endphp

<x-dynamic-component :component="$layout" />
