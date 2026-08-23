@extends('layouts.gmu')

@section('title', 'Edit library block')
@section('crumb', 'Proposal library')

@section('content')
<x-ui.page-head title="Edit library block" :lede="$library->title">
    <x-slot:actions>
        <a href="{{ route('library.show', $library) }}" class="btn">View block</a>
    </x-slot:actions>
</x-ui.page-head>

@include('library._form', ['item' => $library, 'categories' => $categories, 'route' => route('library.update', $library), 'method' => 'PUT'])
@endsection
