@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Edit Zerodha Account</h4>

    @include('partials.alerts')

    <form action="{{ route('zerodha_accounts.update', $zerodha_account) }}" method="POST">
        @method('PUT')
        @include('zerodha_accounts.form', ['account' => $zerodha_account])
    </form>
</div>
@endsection
