@extends('errors.template')

@section('title', 'Confirm your new email')

@section('details')
    <p>Change your email address to {{ $newEmail }}?</p>

    <form method="post" action="{{ $action }}">
        @csrf
        <button type="submit" style="font: inherit; font-size: 18px; padding: 10px 20px; cursor: pointer;">
            Confirm
        </button>
    </form>
@endsection
