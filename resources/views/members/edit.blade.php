@extends('layouts.app')
@section('title', 'Edit member')
@section('page', 'members')
@section('content')
<a class="breadcrumb-link" href="{{ route('members.show', $member) }}"><x-icon name="chevron-left" />Back to member profile</a>
<x-page-heading title="A little update" :description="'Keep '.$member->first_name.'’s membership details up to date.'" />
@include('members.form')
@endsection