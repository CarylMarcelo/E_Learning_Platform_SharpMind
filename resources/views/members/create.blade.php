@extends('layouts.app')
@section('title', 'Registration Form')
@section('page', 'members')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">@endpush
@section('content')
<a class="breadcrumb-link no-print" href="{{ route('members.index') }}"><x-icon name="chevron-left" />Back to members</a>
<x-page-heading title="Registration Form" description="A few details are all it takes to start a new chapter." />
@include('members.form')
@endsection
@push('scripts')<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>@endpush
