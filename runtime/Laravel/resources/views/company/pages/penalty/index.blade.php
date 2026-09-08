@extends('company.layout.app')

@section('title', 'Penalty')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tabulator.css') }}?v={{ hash_file('md5', public_path('css/tabulator.css')) }}">  
<style>
        #ref_preview_table thead tr th {
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            border : 1px solid rgb(66, 65, 65);
            background-color: #E3E7EB;
        
    }

    #ref_preview_table tbody tr td {
        font-family: monospace;
        font-size: 13px;
        border : 1px solid rgb(66, 65, 65);
    }   
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <!-- Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center justify-content-between">

                <!-- Title -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card border-0 border-start border-4 border-primary">
                        <div class="card-body shadow p-2">
                            <h3 class="page-title font-monospace">
                                <i class="fa-solid fa-receipt me-2 text-primary"></i>
                                Penalty List
                            </h3>
                            <span class="ribbon ribbon-bookmark bg-blue">
                                <i class="fa-solid fa-eye me-1"></i> LIST DATA
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Back Action -->
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                    <div class="card border-0 border-end border-4 border-primary">
                        <div class="card-body shadow-sm p-2">
                            <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                <div class="waves-effect d-flex gap-2">
                                    {{-- @canany(['penalty.print', 'penalty.export'])
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary dropdown-toggle btn-sm btn-animate-icon btn-animate-icon-pulse waves-effect"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                @include('icons.upload', ['size' => 19])
                                                Export
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @php
                                                    $actions = exportActions('penalty', 'penalty');
                                                @endphp

                                                @foreach ($actions as $action)
                                                    @can($action['permission'])
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="#"
                                                                data-route="{{ $action['route'] }}"
                                                                @foreach(($action['attrs'] ?? []) as $attr => $value)
                                                                    {{ $attr }}="{{ $value }}"
                                                                @endforeach>
                                                                @include($action['icon'])
                                                                {{ $action['label'] }}
                                                            </a>
                                                        </li>
                                                    @endcan
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endcanany --}}
                                    {{-- Add Penalty Button --}}
                                    <div class="waves-effect">
                                        <a href="javascript:void(0)" class="btn btn-outline-primary btn-sm" id="addPenaltyBtn" data-bs-toggle="modal" data-bs-target="#addPenaltyModal">
                                            @include('icons.plus', ['size' => 20])
                                            Add New Penalty
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="card">               
                 <!-- 🔹 Filters Section -->
                <form>
                    <div class="p-3" id="filters">                            
                            <div class="row g-2 m-erp-so-row-1 section-brown">                              
                            
                                <!-- Item -->
                                <div class="col-md-4 col-lg-2">
                                    <label for="narration" class="fs-4">Items</label>
                                    <select id="item_id" name="item_id" class="form-select select2" style="width: 100%;" data-placeholder="Select Item" required>
                                        <option value="">Select Item</option>                                        
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}{{ $item->unit ? ' (' . $item->unit->name . ')' : '' }}</option>
                                            @endforeach
                                    </select>
                                </div>                                                                   

                                <!-- Filter Buttons -->
                                <div class="col-md-4 col-lg-auto d-flex align-items-end gap-2">
                                    <button id="filter_apply" class="btn btn-primary flex-fill">
                                        @include('icons.filter', ['size' => 20])
                                       Apply
                                    </button>
                                    <button id="filter_clear" class="btn btn-outline-secondary flex-fill">
                                        @include('icons.filter-clear', ['size' => 20])
                                        Clear
                                    </button>                                    
                                </div>
                            </div>                                                                               
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div id="penalty_table">
                    <!-- Table content will be loaded dynamically via JS -->
                </div>
            </div>
        </div>
    </div>
</div>
@include('company.partials._shortcuts-bar')
@endsection
@section('script')
    <script>
        const penaltyListUrl = "{{ route('penalty.index') }}";
        const penaltyCreateUrl = "{{ route('penalty.create') }}";
        const penaltyStoreUrl = "{{ route('penalty.store') }}";
        const penaltyViewUrl = "{{ route('penalty.show', ':id') }}";
        const penaltyEditUrl = "{{ route('penalty.edit', ':id') }}";
        const penaltyDeleteUrl = "{{ route('penalty.destroy', ':id') }}";
    </script>
    <script src="{{ asset('js/utils/icons.js') }}?v={{ hash_file('md5', public_path('js/utils/icons.js')) }}"></script>    
    <script src="{{ asset('js/modules/penalty/index.js') }}?v={{ hash_file('md5', public_path('js/modules/penalty/index.js')) }}"></script>

@endsection
