@extends('layouts.app')

@section('title', 'Order Logs')

@section('content')
    <!-- Breadcrumb -->
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="page-title">Orders Logs</h4>
               
            </div>
            <div class="col-auto">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Orders Logs</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->action }}</td>
                            <td>
                                <span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td>{{ $log->message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $logs->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>
@endsection
