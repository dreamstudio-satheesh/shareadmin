@extends('layouts.app')
@section('title', 'Watchlist Manager')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
            <h4 class="mb-sm-0">Zerodha Watchlist</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="#">Trading</a></li>
                    <li class="breadcrumb-item active">Watchlist</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Add Symbol to Watchlist</h5>
            <form method="POST" action="{{ url('/watchlist/add') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Symbol</label>
                    <input type="text" class="form-control" name="symbol" placeholder="e.g. TCS, HDFC" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Exchange</label>
                    <select class="form-select" name="exchange">
                        <option value="NSE" selected>NSE</option>
                        <option value="BSE">BSE</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add to Redis</button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Current Redis Watchlist</h5>
            <ul class="list-group">
                @foreach($symbols as $symbol)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{ $symbol }}
                    <form method="POST" action="{{ url('/watchlist/remove') }}">
                        @csrf
                        <input type="hidden" name="symbol" value="{{ $symbol }}">
                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                </li>
                @endforeach
                @if(empty($symbols))
                <li class="list-group-item text-muted">No symbols in Redis</li>
                @endif
            </ul>
            <form method="POST" action="{{ url('/watchlist/clear') }}" class="mt-3">
                @csrf
                <button class="btn btn-outline-secondary btn-sm">Clear All</button>
            </form>
        </div>
    </div>
</div>
@endsection
