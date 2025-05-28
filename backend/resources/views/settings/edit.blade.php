@extends('layouts.app')

@section('title', 'Admin Settings')

<!-- Continue in resources/views/settings/edit.blade.php -->

@section('content')
    <!-- Breadcrumb -->
    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0">Admin Settings</h4>

        <div class="page-title-right">
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Admin Settings</li>
            </ol>
        </div>
    </div>

    <!-- Card Layout -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Configure Trading Rules</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Buy Logic -->
                        <div class="mb-3">
                            <label class="form-label">Buy Logic</label>
                            <select name="buy_logic" class="form-select" required>
                                <option value="fixed_percent" {{ $settings->buy_logic === 'fixed_percent' ? 'selected' : '' }}>Fixed Percent</option>
                                <option value="offset_ltp" {{ $settings->buy_logic === 'offset_ltp' ? 'selected' : '' }}>Offset LTP</option>
                            </select>
                        </div>

                        <!-- Buy Percent -->
                        <div class="mb-3">
                            <label class="form-label">Buy %</label>
                            <input type="number" step="0.01" name="buy_percent" value="{{ $settings->buy_percent }}" class="form-control" required>
                        </div>

                        <!-- Stoploss Percent -->
                        <div class="mb-3">
                            <label class="form-label">Stoploss %</label>
                            <input type="number" step="0.01" name="stoploss_percent" value="{{ $settings->stoploss_percent }}" class="form-control" required>
                        </div>

                        <!-- Auto Sell Time -->
                        <div class="mb-3">
                            <label class="form-label">Auto Sell Time</label>
                            <input type="time" name="auto_sell_time" value="{{ $settings->auto_sell_time }}" class="form-control">
                        </div>

                        <button class="btn btn-primary">Update Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
