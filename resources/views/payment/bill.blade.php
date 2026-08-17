@extends('layouts.app')

@section('title', 'Bill nap tien - Noteket')

@section('content')
<div class="" style="display:flex;justify-content:center;align-item:center;">
    <h1>+{{$order->point}}</h1>
    <p>Successfully pay {{$order->amount}} for {{$order->point}}</p>
</div>
@endsection

@section('content-mobile')
<div class="" style="display:flex;justify-content:center;align-item:center;">
    <h1>+{{$order->point}}</h1>
    <p>Successfully pay {{$order->amount}} for {{$order->point}}</p>
</div>
@endsection
