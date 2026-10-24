@extends('layouts.app')
@section('title', \App\Support\Legal::t('privacidad', 'meta_title'))
@section('content')
    @include('partials.legal-page', ['ns' => 'privacidad'])
@endsection
