@extends('layouts.app')
@section('title', 'Cron Job Logs')

@section('content')
    <!-- Breadcrumb -->
    <div class="page-title-box">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="page-title">Cron Job Logs</h4>
            </div>
            <div class="col-auto">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Cron Job Logs</li>
                </ol>

            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Cron Job Executions</h5>
                    <p class="card-text">Below are the logs of the most recent cron job executions.</p>
                </div>

                <div class="card-body">
                    @if ($logs->isEmpty())
                        <div class="alert alert-info" role="alert">
                            No cron job logs found.
                        </div>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Script</th>
                                    <th>Executed At</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logs as $log)
                                    <tr>
                                        <td>{{ $log->script }}</td>
                                        <td>{{ $log->created_at }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->status === 'ok' ? 'success' : 'danger' }}">
                                                {{ strtoupper($log->status) }}
                                            </span>
                                            <small class="text-muted d-block">{{ $log->message }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $logs->links('vendor.pagination.bootstrap-5') }}

                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
