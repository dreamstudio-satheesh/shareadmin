@extends('layouts.app')

@section('title', 'Upload Orders')
@section('content')
    <!-- Breadcrumb -->
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="page-title">Upload Orders</h4>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Upload Orders</li>
                </ol>
            </div>
            <div class="col-auto">
                <a href="{{ route('orders.download.sample') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ri-download-line"></i> Download Sample Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Upload Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Select Zerodha Account</label>
                            <select name="zerodha_account_id" class="form-select" required>
                                <option value="">-- Choose Account --</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Excel File (.xls/.xlsx)</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload Orders</button>
                    </form>

                </div>
            </div>
        </div>

    </div>
@endsection
