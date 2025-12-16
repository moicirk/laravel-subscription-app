@extends('layouts.app')

@section('title', 'Exception')

@section('content')
<div>
    {{ $exception->getCode() }}
</div>
@endsection
