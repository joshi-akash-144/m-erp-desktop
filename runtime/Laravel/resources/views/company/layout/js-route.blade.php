@php
    $fiscalYearStart = financial_year_start();
    $fiscalYearEnd = financial_year_end();
@endphp

{{-- Global Route --}}
<script>
    const FINANCIAL_YEAR_START = {!! json_encode($fiscalYearStart) !!};
    const FINANCIAL_YEAR_END = {!! json_encode($fiscalYearEnd) !!};
    const COMPANY_ID = {{ company_id() }};

    // const accountDetailByIdUrl 

    const masterRoutes = {
        items: "{{ route('lookup.items') }}",
        parties: "{{ route('lookup.accounts.party') }}",
        brokers: "{{ route('lookup.brokers') }}",
        conditions: "{{ route('lookup.conditions') }}",
        destinations: "{{ route('lookup.destinations') }}",
        purchaseTypes: "{{ route('lookup.purchase_types') }}",
        saleTypes: "{{ route('lookup.sale_types') }}",
        accounts: "{{ route('lookup.accounts') }}",
        billSundries: "{{ route('lookup.billSundries') }}",
        voucherTypes: "{{ route('lookup.voucherTypes') }}",
        banks: "{{ route('lookup.banks') }}",
        accountBalance: "{{ route('lookup.account.balance') }}",
        preLoadLedgers: "{{ route('lookup.preload.ledger.accounts') }}",
        transportParties: "{{ route('lookup.transport_parties') }}",
        expenseAccounts:  "{{ route('lookup.expense_accounts') }}",
        allDestinations:  "{{ route('lookup.all_destinations') }}",
        allItems:         "{{ route('lookup.all_items') }}",
        vehicles:         "{{ route('lookup.vehicles') }}",
        allVehicles:      "{{ route('lookup.all_vehicles') }}",
        allContractor:    "{{ route('lookup.all_contractors') }}",

        accountDetails: (id) => `{{ route('lookup.accounts.party.details', ':id') }}`.replace(':id', id),
        itemDetails: (id) => `{{ route('lookup.item.details', ':id') }}`.replace(':id', id),
        purchaseTypeDetails: (id) => `{{ route('lookup.purchase_type.details', ':id') }}`.replace(':id', id),
        saleTypeDetails: (id) => `{{ route('lookup.sale_type.details', ':id') }}`.replace(':id', id),
 
    }

</script>