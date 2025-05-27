@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">All Accounts</h4>

    <a href="{{ route('zerodha_accounts.create') }}" class="btn btn-primary mb-3 float-end">+ Add Account</a>

    @include('partials.alerts')

    <div class="row">
        @foreach($accounts as $account)
        <div class="col-md-6 col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ $account->name }}</h5>
                    <p><strong>API Key:</strong> {{ $account->api_key }}</p>

                    <form method="POST" action="{{ route('zerodha_accounts.update_token', $account) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Access Token</label>
                            <input type="text" name="access_token" value="{{ $account->access_token }}" class="form-control" />
                        </div>
                        <button class="btn btn-success btn-sm mb-2">Update Token</button>
                    </form>

                    <span class="badge bg-danger">Disconnected</span>
                    <form action="{{ route('zerodha_accounts.check_now') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Check now</button>
                    </form>

                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <a href="{{ route('zerodha.login', $account->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plug"></i> Connect Zerodha
                        </a>
                        <a href="{{ route('zerodha_accounts.edit', $account) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form method="POST" action="{{ route('zerodha_accounts.destroy', $account) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this account?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        @if($accounts->isEmpty())
        <div class="col-12 text-center text-muted">
            <p>No Zerodha accounts added yet.</p>
        </div>
        @endif
    </div>
</div>
@endsection
