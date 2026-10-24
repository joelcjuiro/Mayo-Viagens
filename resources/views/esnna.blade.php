@extends('layouts.app')
@section('title', \App\Support\Legal::t('esnna', 'meta_title'))
@section('content')
    @include('partials.legal-page', ['ns' => 'esnna'])
@endsection
