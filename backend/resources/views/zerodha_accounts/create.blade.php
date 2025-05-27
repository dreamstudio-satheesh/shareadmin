@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Add Zerodha Account</h4>

    @include('partials.alerts')

    <form action="{{ route('zerodha_accounts.store') }}" method="POST">
        @include('zerodha_accounts.form')
    </form>
</div>
@endsection
