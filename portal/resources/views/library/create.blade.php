@extends('adminlte::page')

@section('title', 'Add Library Item')

@section('content_header')
    <h1><i class="fas fa-plus-circle mr-2"></i>Add Content Block</h1>
@endsection

@section('content')
@include('library._form', ['item' => null, 'categories' => $categories, 'route' => route('library.store'), 'method' => 'POST'])
@endsection
