@extends('adminlte::page')

@section('title', 'Edit Library Item')

@section('content_header')
    <h1><i class="fas fa-edit mr-2"></i>Edit Content Block</h1>
@endsection

@section('content')
@include('library._form', ['item' => $library, 'categories' => $categories, 'route' => route('library.update', $library), 'method' => 'PUT'])
@endsection
