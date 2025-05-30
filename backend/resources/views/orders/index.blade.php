@extends('layouts.app')
@section('title', 'Orders List')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Orders</h5>
                    <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                        <i class="ri-delete-bin-line"></i> Bulk Delete
                    </button>
                </div>
                <div class="card-body">
                    <form id="bulkDeleteForm">
                        @csrf
                        @method('DELETE')

                        <div class="table-responsive">
                            <table id="ordersTable" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>Account</th>
                                        <th>Symbol</th>
                                        <th>Type</th>
                                        <th>Trigger</th>
                                        <th>LTP</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
                                        <tr>
                                            <td><input type="checkbox" name="order_ids[]" value="{{ $order->id }}"></td>
                                            <td>{{ $order->account->name ?? 'N/A' }}</td>
                                            <td>{{ $order->symbol }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-secondary">{{ strtoupper($order->order_type ?? 'SINGLE') }}</span>
                                                <span
                                                    class="badge bg-soft-primary text-primary">{{ strtoupper($order->transaction_type ?? 'BUY') }}</span>
                                            </td>

                                            <td>
                                                {{ number_format($order->target_price, 2) }}
                                                <small
                                                    class="text-muted">{{ number_format($order->target_percent, 2) }}%</small>
                                            </td>
                                            <td id="ltp-{{ str_replace(':', '_', $order->symbol) }}">
                                                {{ number_format($order->ltp_at_upload, 2) }}
                                            </td>
                                            <td>{{ rtrim(rtrim($order->qty, '0'), '.') }}</td>
                                            <td>
                                                <span
                                                    class="badge text-uppercase
                                                @if ($order->status == 'pending') bg-warning-subtle text-warning
                                                @elseif ($order->status == 'executed') bg-success-subtle text-success
                                                @elseif ($order->status == 'failed') bg-danger-subtle text-danger
                                                @else bg-secondary-subtle text-muted @endif">
                                                    {{ $order->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button"
                                                    class="btn btn-icon btn-sm btn-light-danger single-delete"
                                                    data-id="{{ $order->id }}">
                                                    <i class="ri-delete-bin-2-line"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection



@push('styles')
    <!-- Datatables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" />
@endpush

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!-- DataTables Core & Extensions -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

    <script type="module">
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                responsive: true,
                ordering: true,
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            });
        });

        // Select all toggle
        document.getElementById('select-all')?.addEventListener('change', function() {
            document.querySelectorAll('input[name="order_ids[]"]').forEach(cb => cb.checked = this.checked);
        });

        // Bulk delete
        document.getElementById('bulkDeleteBtn')?.addEventListener('click', async function() {
            const form = document.getElementById('bulkDeleteForm');
            const formData = new FormData(form);
            const selected = formData.getAll('order_ids[]');

            if (selected.length === 0) return alert('Please select at least one order.');
            if (!confirm('Are you sure you want to delete selected orders?')) return;

            const response = await fetch("{{ route('orders.bulk-delete') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (response.ok) location.reload();
            else alert('Failed to delete orders.');
        });

        // Single row delete
        document.querySelectorAll('.single-delete').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Delete this order?')) return;
                const id = btn.dataset.id;

                const response = await fetch(`/orders/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) location.reload();
                else alert('Failed to delete order.');
            });
        });

        // Echo LTP Update
        window.Echo.channel('ticks')
            .listen('.TickUpdate', (e) => {
                const tick = e.tick;
                const id = 'ltp-' + tick.symbol.replace(':', '_');
                const cell = document.getElementById(id);
                if (cell && tick.market_open === "True") {
                    cell.textContent = parseFloat(tick.lp).toFixed(2);
                }
            });
    </script>
@endpush
