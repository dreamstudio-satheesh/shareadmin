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

    @include('partials.alerts')

    <div class="row">

        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-3">Live Watchlist Ticks</h5>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Symbol</th>
                                <th class="text-center">Price & Change</th>
                                <th class="text-center">Trend</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="watchlist">
                            @forelse ($symbols as $symbol)
                                @php
                                    $id = str_replace(':', '_', $symbol);
                                    $tick = $tickData->get($symbol);
                                @endphp
                                <tr id="tick-{{ $id }}">
                                    <td class="fw-medium">{{ $symbol }}</td>
                                    <td class="text-center">
                                        <span id="ltp-{{ $id }}" class="badge bg-info fw-medium px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $tick['ltp'] ?? '--' }}
                                        </span>
                                        <span id="change-{{ $id }}" class="ms-2 fw-semibold" style="font-size: 0.65rem;">--%</span>
                                        <i id="arrow-{{ $id }}" class="bx bx-minus text-muted fs-5 ms-2"></i>
                                        @if ($tick && isset($tick['last_update']))
                                            <div class="text-muted small mt-1">
                                                {{ $tick['market_open'] ? 'Live' : 'Last updated ' . $tick['last_update'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <canvas id="spark-{{ $id }}" height="35" width="120"></canvas>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ url('/watchlist/remove') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="symbol" value="{{ $symbol }}">
                                            <button class="btn btn-outline-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No symbols in Redis</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <form method="POST" action="{{ url('/watchlist/clear') }}" class="mt-3 text-end">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm px-4 py-1">Clear All</button>
                    </form>
                </div>
            </div>
        </div>

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

    </div>
@endsection

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
@endpush

@push('scripts')
    @vite('resources/js/app.js')

    <script type="module">
        window.addEventListener('DOMContentLoaded', () => {
            const previousPrices = {};
            const sparkData = {};
            const sparkCharts = {};

            window.Echo.channel('ticks')
                .listen('.TickUpdate', (e) => {
                    //  console.log('📡 Tick received:', e);
                    let tick;
                    try {
                        tick = e?.tick ?? (typeof e?.data === 'string' ? JSON.parse(e.data).tick : e.data?.tick);
                    } catch (err) {
                        console.error('[Watchlist] Tick parse error:', err, e);
                        return;
                    }

                    if (!tick || !tick.symbol || !tick.lp) return;

                    const id = tick.symbol.replace(':', '_');
                    const current = parseFloat(tick.lp);
                    const ltpEl = document.getElementById(`ltp-${id}`);
                    const arrowEl = document.getElementById(`arrow-${id}`);
                    const changeEl = document.getElementById(`change-${id}`);
                    const canvas = document.getElementById(`spark-${id}`);

                    if (!ltpEl || !arrowEl || !canvas) return;

                    ltpEl.textContent = current.toFixed(2);

                    const prev = previousPrices[id];
                    if (prev !== undefined) {
                        const diff = current - prev;
                        const pct = ((diff / prev) * 100).toFixed(2);

                        changeEl.textContent = `${pct}%`;

                        if (diff > 0) {
                            arrowEl.className = 'bx bx-up-arrow-alt text-success fs-5';
                            ltpEl.className = 'badge bg-success fw-semibold px-3 py-2';
                            changeEl.className = 'text-success small fw-medium';
                        } else if (diff < 0) {
                            arrowEl.className = 'bx bx-down-arrow-alt text-danger fs-5';
                            ltpEl.className = 'badge bg-danger fw-semibold px-3 py-2';
                            changeEl.className = 'text-danger small fw-medium';
                        } else {
                            arrowEl.className = 'bx bx-minus text-muted fs-5';
                            ltpEl.className = 'badge bg-info fw-semibold px-3 py-2';
                            changeEl.className = 'text-muted small fw-medium';
                        }
                    }

                    previousPrices[id] = current;

                    // Sparkline logic
                    if (!sparkData[id]) {
                        sparkData[id] = [];
                    }
                    if (sparkData[id].length > 20) {
                        sparkData[id].shift();
                    }
                    sparkData[id].push(current);

                    if (!sparkCharts[id]) {
                        sparkCharts[id] = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: sparkData[id].map((_, i) => i + 1),
                                datasets: [{
                                    data: sparkData[id],
                                    borderColor: '#3b76e1',
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    fill: false,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                animation: false,
                                responsive: false,
                                scales: {
                                    x: { display: false },
                                    y: { display: false }
                                },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { enabled: false }
                                }
                            }
                        });
                    } else {
                        sparkCharts[id].data.datasets[0].data = sparkData[id];
                        sparkCharts[id].data.labels = sparkData[id].map((_, i) => i + 1);
                        sparkCharts[id].update();
                    }
                });
        });
    </script>
@endpush
