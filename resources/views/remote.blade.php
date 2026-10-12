@extends('base')

@section('title', koel_branding('name') . ' - Remote Controller')
@section('manifest', route('manifest.remote'))

@push('scripts')
    @vite(['resources/assets/js/remote/app.ts'])
@endpush
