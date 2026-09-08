@extends('company.layout.app')
@section('title', 'Freight – Edit')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: 1fr 2fr 1fr 1fr 1fr 1fr;
            gap: 15px;
        }

        .m-erp-so-row-2 {
            grid-template-columns: 1fr 1.5fr 2fr 2fr;
            gap: 15px;
        }

        .m-erp-so-row-3 {
            grid-template-columns: 1.5fr 1.5fr 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 1200px) {
            .m-erp-so-row-1 {
                grid-template-columns: 1fr 2fr 1fr;
            }
            .m-erp-so-row-2 {
                grid-template-columns: 1fr 1fr;
            }
            .m-erp-so-row-3 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 992px) {
            .m-erp-so-row-1,
            .m-erp-so-row-2,
            .m-erp-so-row-3 {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper">
        <!-- ✅ Page Header -->
 <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-sales rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-sales text-sales rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-plus"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-sales fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Freight
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-pen-to-square me-1"></i> EDIT ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.freight._form', ['formMode' => 'edit'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar', [
    'barModuleLabel'     => 'Freight',
    'barModuleShortcuts' => [
        ['key' => 'Alt+L', 'label' => 'Freight List',   'type' => 'info', 'permission' => 'freight.list', 'url' => route('freight.index')],
    ],
])
@endsection

@section('script')

@php
    $freightData = $freight->load(['items.item', 'consignor', 'consignee', 'fromDestination', 'toDestination', 'vehicle']);
@endphp
<script>
    const grnDataUrl = "{{ route('freight.getGrnData', ':id') }}";
    const lrNumberDataUrl = "{{ route('freight.getLrNumberData', ':id') }}";
    const nextBillNumberUrl = "{{ route('freight.getNextBillNumber', ':id') }}";
    const updateFreightUrl = "{{ route('freight.update', $freight->id) }}";
    const listUrl = "{{ route('freight.index') }}";
    const checkDuplicateLrFromFreightModule = "{{ route('freight.checkDuplicateLRNumber') }}";

    const FREIGHT_DATA = @json($freightData);
</script>

    <script src="{{ asset('js/modules/freight/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/freight/edit.js')) }}"></script>
    <script src="{{ asset('js/modules/freight/check-duplicate-lr.js') }}?v={{ hash_file('md5', public_path('js/modules/freight/check-duplicate-lr.js')) }}"></script>
@endsection
