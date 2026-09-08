@extends('company-selector.layout.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">
<style>
    .balance-chips { display: flex; flex-wrap: wrap; gap: 4px; }
    .chip-item {
        display: inline-flex; align-items: center; gap: 5px;
        background: #eef2ff; border-radius: 20px;
        padding: 4px 10px; font-size: 0.75rem; color: #3730a3; font-weight: 500;
        white-space: nowrap; border: 1px solid #c7d2fe;
    }
    .chip-item .chip-company { color: #6b7280; font-size: 0.70rem; font-weight: 600; margin-right: 2px;}
    .chip-item .chip-amt { color: #1e1b4b; font-weight: 700; }
    .amt-negative { color: #dc2626 !important; }
    .amt-positive { color: #16a34a !important; }
    
    .total-balance {
        font-weight: 700;
        font-size: 0.95rem;
    }
</style>
@endsection

@section('content')
<div class="page-body pt-4">
    <div class="container-xl">

        {{-- Hero Banner --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="position-relative"
                style="height:60px;background:linear-gradient(120deg,#1a56db 0%,#4dabf7 60%,#74c0fc 100%);border-radius:12px 12px 0 0;">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 border border-4 border-white shadow position-absolute text-white"
                    style="background:linear-gradient(135deg,#1a56db,#4dabf7);width:76px;height:76px;bottom:-38px;left:24px;">
                    <i class="fa-solid fa-receipt" style="font-size:26px;"></i>
                </span>
            </div>
            <div class="px-4 pb-3" style="padding-top:48px !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="mb-0 fw-bold">Dairy Outstanding</h3>
                        <div class="text-muted small mt-1">View cross-company outstanding balances for party masters</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('company-selection.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            @include('icons.prev', ['size' => 15])
                            Dashboard
                        </a>
                         @if(has_permission('dairy_outstanding.print'))
                        <button id="btn_print_all" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="fa-solid fa-print"></i> Print 
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="w-1 text-center">#</th>
                                <th>Party Name</th>
                                <th>Company Wise Balances</th>
                                <th class="text-end">Total Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($finalArray as $index => $party)
                            <tr>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $party['party_name'] }}</td>
                                <td>
                                    @if(count($party['companies']) > 0)
                                        <div class="balance-chips">
                                            @foreach($party['companies'] as $c)
                                                @php
                                                    $val = (float)($c['balance'] ?? 0);
                                                    $formatted = number_format(abs($val), 2);
                                                    $suffix = $val >= 0 ? 'Dr' : 'Cr';
                                                    $colorClass = $val >= 0 ? 'amt-positive' : 'amt-negative';
                                                @endphp
                                                <span class="chip-item">
                                                    <span class="chip-company">{{ $c['company_name'] }}</span>
                                                    <span class="chip-amt {{ $colorClass }}">{{ $formatted }} {{ $suffix }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">No balances</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @php
                                        $totalVal = (float)($party['total_balance'] ?? 0);
                                        $totalFormatted = number_format(abs($totalVal), 2);
                                        $totalSuffix = $totalVal >= 0 ? 'Dr' : 'Cr';
                                        $totalColorClass = $totalVal >= 0 ? 'amt-positive' : 'amt-negative';
                                    @endphp
                                    <span class="total-balance {{ $totalColorClass }}">{{ $totalFormatted }} <small>{{ $totalSuffix }}</small></span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No outstanding records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>
<script>
const printUrl = "{{ route('dairy-outstanding.print') }}";

// Print All
document.getElementById('btn_print_all')?.addEventListener('click', function() {
    printReport(printUrl);
});

function printReport(route, data = {}) {
  showLoader("Please wait... Generating print preview...");

  $.ajax({
    url: route,
    method: "GET",
    data: data,
    success: function (response) {
        console.log('response', response);
        
      hideLoader();

      // Normalize response handling whether it's wrapped in a data object or not
      const isSuccess = response.success || (response.data && response.data.success);
      const html = response.html || (response.data && response.data.html) ||
        (response.data && response.data.data && response.data.data.html);

      if (isSuccess && html) {
        // 🪟 Step 5: Open a new window for print preview
        const w = window.open("", "_blank");

        if (!w) {
          // Handle popup-blocker issue
          showToast("error", "Popup blocked! Please allow popups for this site.");
          return;
        }

        // 📝 Step 6: Write the HTML content into the new window
        w.document.write(html);
        w.document.close();

        // 🖨️ Step 7: Trigger browser print dialog (Small delay to allow CSS loading)
         w.onload = function () {
        w.focus();
        w.print();
        // w.close(); // optional
      };
      } else {
        // ⚠️ Step 8: Handle invalid or failed response
        const message = response.message || (response.data && response.data.message) || "Unable to generate print preview.";
        showToast("warning", message);
      }
    },
    error: function (xhr) {
      // ❌ Step 9: Handle network/server errors gracefully
      hideLoader();

      let errorMessage = "Something went wrong while generating the report.";

      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.status === 0) {
        errorMessage = "No response received from server. Check your connection.";
      } else if (xhr.status >= 500) {
        errorMessage = "Server error occurred while generating the report.";
      }

      console.error("Print Report Error:", xhr);
      showToast("error", errorMessage);
    }
  });
}

</script>
@endsection
