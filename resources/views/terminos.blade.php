@extends('layouts.app')
@section('title', \App\Support\Legal::t('terminos', 'meta_title'))
@section('content')
    @include('partials.legal-page', ['ns' => 'terminos'])
@endsection
