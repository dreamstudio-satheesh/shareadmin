@extends('layouts.app')
@section('title', ' Orders List')

@section('content')
<div class="row" id="pendingOrders">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center border-0">
                <h5 class="card-title mb-0 flex-grow-1">Pending Orders</h5>
            </div>

            <div class="card-body border border-dashed border-end-0 border-start-0">
                <div class="row g-2">
                    <div class="col-xl-4 col-md-6">
                        <div class="search-box">
                            <input type="text" class="form-control search" placeholder="Search symbol...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-calendar-2-line"></i></span>
                            <input type="text" class="form-control flatpickr-input" data-provider="flatpickr" placeholder="Select date" readonly>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <select class="form-select">
                            <option value="all">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="executed">Executed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <select class="form-select">
                            <option value="all">Select Type</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>
                    <div class="col-xl-1 col-md-4">
                        <button class="btn btn-success w-100">Filter</button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table align-middle table-nowrap">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>Date</th>
                                <th>Instrument</th>
                                <th>Type</th>
                                <th>Trigger</th>
                                <th>LTP</th>
                                <th>Qty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
    <tr>
        <td>{{ $order->created_at->format('Y-m-d') }}</td>
        <td>{{ $order->symbol }} <small class="text-muted">NSE</small></td>
        <td>
            <span class="badge bg-secondary">SINGLE</span>
            <span class="badge bg-soft-primary text-primary">BUY</span>
        </td>
        <td>
            {{ number_format($order->target_price, 2) }}
            <span class="text-muted ms-1">
                {{ number_format($order->target_percent, 2) }}%
            </span>
        </td>
        <td>{{ number_format($order->ltp_at_upload, 2) }}</td>
        <td>{{ rtrim(rtrim($order->qty, '0'), '.') }}</td>
        <td>
            <span class="badge text-uppercase
                @if ($order->status == 'pending') bg-warning-subtle text-warning
                @elseif ($order->status == 'executed') bg-success-subtle text-success
                @elseif ($order->status == 'failed') bg-danger-subtle text-danger
                @else bg-secondary-subtle text-muted
                @endif">
                {{ $order->status }}
            </span>
        </td>
    </tr>
@endforeach

                            @if ($orders->isEmpty())
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No pending orders</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end mt-3">
                        {{ $orders->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
