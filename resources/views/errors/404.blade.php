@extends('errors.template')

@section('title', 'Not Found')
@section('details', $exception->getMessage())
