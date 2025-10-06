@extends('base')

@section('title', koel_branding('name') . ' - Remote Controller')

@push('scripts')
    @vite(['resources/assets/js/remote/app.ts'])
@endpush
