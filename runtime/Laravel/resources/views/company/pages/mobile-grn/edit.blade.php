@extends('company.layout.app')
@section('title', 'Mobile GRN – Edit')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/module/index.css') }}?v={{ hash_file('md5', public_path('css/module/index.css')) }}"></link>
    <style>
        .m-erp-so-row-1 {
            grid-template-columns: repeat(6, 1fr);
        }

        .m-erp-so-row-2 {
            grid-template-columns: repeat(6, 1fr);
        }

        .m-erp-so-row-3 {
            grid-template-columns: repeat(4, 1fr);
        }

        .m-erp-so-row-4 {
            grid-template-columns: repeat(7, 1fr);
        }

        .m-erp-so-row-5 {
            grid-template-columns: repeat(3, 1fr);
        }

        /* Ensure Select2 fits container */
        .select2-container {
            width: 100% !important;
        }

        /* 🖥️ For X-Large Screens (1400px - 1600px) */
        @media (max-width: 1600px) {
            .m-erp-so-row-4 { grid-template-columns: repeat(4, 1fr); }
            .m-erp-so-row-1, .m-erp-so-row-2 { grid-template-columns: repeat(4, 1fr); }
        }

        /* 💻 For Large Screens (Desktop/Laptop) */
        @media (max-width: 1200px) {
            .m-erp-so-row-1, .m-erp-so-row-2 { grid-template-columns: repeat(3, 1fr); }
            .m-erp-so-row-4 { grid-template-columns: repeat(3, 1fr); }
            .m-erp-so-row-3 { grid-template-columns: repeat(2, 1fr); }
            .m-erp-so-row-5 { grid-template-columns: repeat(2, 1fr); }
        }

        /* 📱 For Medium Screens (Tablets) */
        @media (max-width: 992px) {
            .m-erp-so-row-1, .m-erp-so-row-2, .m-erp-so-row-3, .m-erp-so-row-4, .m-erp-so-row-5 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        /* .page */
        }

        /* 📲 For Small Screens (Phones) */ 
        @media (max-width: 576px) {
            .m-erp-so-row-1, .m-erp-so-row-2, .m-erp-so-row-3, .m-erp-so-row-4, .m-erp-so-row-5 {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-wrapper" style="max-width: 1700px">
        <!-- ✅ Page Header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="card border-0 shadow-sm rounded-0" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
                <div class="card-body p-1 d-flex flex-wrap align-items-center justify-content-between gap-3 border-start border-4 border-primary rounded-0">
                    
                    <!-- 🔹 Title Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-lt text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                            <i class="fs-3 fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h2 class="page-title text-primary fw-bolder mb-1" style="letter-spacing: 0.5px; font-size: 1.25rem;">
                                Mobile GRN
                            </h2>
                        </div>
                        <div class="ms-md-3">
                            <span class="badge badge-pill bg-orange text-white fw-bold text-uppercase shadow-sm px-3 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-plus me-1"></i> EDIT ENTRY
                            </span>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn waves-effect rounded-0 fw-medium shadow-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        {{-- <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center justify-content-between">

                    <!-- 🔹 Title & Subtitle -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 border-start border-4 border-primary">
                            <div class="card-body shadow p-2">
                                <h3 class="page-title font-monospace">
                                    <i class="fa-solid fa-cart-shopping me-2 text-primary"></i>
                                    Mobile GRN
                                </h3>
                                <span class="ribbon ribbon-bookmark bg-orange">
                                    <i class="fa-solid fa-plus me-1"></i> EDIT ENTRY
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔹 Action Buttons -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-print-none">
                        <div class="card border-0 border-end border-4 border-primary">
                            <div class="card-body shadow-sm p-2">
                                <div class="d-flex flex-wrap justify-content-md-end justify-content-start gap-2">
                                    <a href="{{ route('back.to.previous') }}" class="btn btn-sm btn-outline-dark back-btn">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>--}}


        <!-- ✅ Page Body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="card p-2 shadow-sm">
                    @include('company.pages.mobile-grn._form', ['formMode' => 'edit'])
                </div>
            </div>
        </div>
    </div>
@include('company.partials._shortcuts-bar');
@endsection

@section('script')
<script>
    const getGrnUrl = "{{ route('grns.show', ':id') }}";
    const updateMobileGrnUrl = "{{ route('mobile-grns.update', ':id') }}";
    const storeMobileGrnUrl = "{{ route('mobile-grns.store') }}";
    const checkDuplicateReferenceFromGrn = "{{ route('grns.check_duplicate_reference') }}";
</script>
    <script src="{{ asset('js/modules/grn/check-duplicate-ref.js') }}?v={{ hash_file('md5', public_path('js/modules/grn/check-duplicate-ref.js')) }}"></script>
    <script src="{{ asset('js/modules/mobile-grn/create.js') }}?v={{ hash_file('md5', public_path('js/modules/mobile-grn/create.js')) }}"></script>
    <script src="{{ asset('js/modules/mobile-grn/edit.js') }}?v={{ hash_file('md5', public_path('js/modules/mobile-grn/edit.js')) }}"></script>
@endsection