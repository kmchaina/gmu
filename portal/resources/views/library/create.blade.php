@extends('layouts.gmu')

@section('title', 'Add a library block')
@section('crumb', 'Proposal library')

@section('content')
<x-ui.page-head
    title="Add a library block"
    lede="Institutional text that every proposal needs — written once, approved, and reused." />

@include('library._form', ['item' => null, 'categories' => $categories, 'route' => route('library.store'), 'method' => 'POST'])
@endsection
