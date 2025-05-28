@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Instruments List</h4>
                
                <div class="page-title-right">
                    <form method="POST" action="{{ route('instruments.import') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary mb-3">Import Instruments</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
           
            <div class="card p-4">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Exchange</th>
                        <th>Symbol</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Segment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($instruments as $instrument)
                        <tr>
                            <td>{{ $instrument->instrument_token }}</td>
                            <td>{{ $instrument->exchange }}</td>
                            <td>{{ $instrument->tradingsymbol }}</td>
                            <td>{{ $instrument->name }}</td>
                            <td>{{ $instrument->instrument_type }}</td>
                            <td>{{ $instrument->segment }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $instruments->links() }}

            </div>
        </div>
    </div>
@endsection
