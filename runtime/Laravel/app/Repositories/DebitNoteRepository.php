<?php

namespace App\Repositories;

use App\Models\DebitNote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DebitNoteRepository extends BaseRepository
{
    public function __construct(DebitNote $debitNote)
    {
        parent::__construct($debitNote);
    }

    public function list(int $companyId, int $financialYearId, array $filters): array
    {
        $query = $this->model
            ->with(['account:id,name,city', 'purchaseType:id,name', 'details.item:id,name'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if (!empty($filters['start_date'])) {
            $query->whereDate('debit_note_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('debit_note_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }
        if (!empty($filters['bill_from'])) {
            $query->where('debit_note_serial', '>=', $filters['bill_from']);
        }
        if (!empty($filters['bill_to'])) {
            $query->where('debit_note_serial', '<=', $filters['bill_to']);
        }

        $rowCount  = $query->count();
        $paginator = $query
            
            ->select([
                'id', 'debit_note_serial', 'debit_note_number', 'debit_note_date',
                'account_id', 'purchase_type_id', 'gst_type',
                'total_quantity', 'taxable_amount', 'tax_amount', 'net_amount', 'grand_total',
                'voucher_id', 'remarks', 'status',
                'purchase_invoice_id', 'purchase_invoice_serial',
            ])
            ->orderByRaw('CAST(debit_note_serial AS UNSIGNED) DESC')
            ->paginate($filters['size'] ?? 50, ['*'], 'page', $filters['page'] ?? 1);

        return [$paginator, $rowCount];
    }

    public function countFiltered(int $companyId, int $financialYearId, array $filters): int
    {
        $query = $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if (!empty($filters['start_date'])) {
            $query->whereDate('debit_note_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('debit_note_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        return $query->count();
    }

    public function countAll(int $companyId, int $financialYearId): int
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->count();
    }

    public function getDebitNoteSerials(int $companyId, int $financialYearId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderBy('debit_note_serial')
            ->get(['id', 'debit_note_serial']);
    }

    public function getViewData(int $id, int $companyId, int $financialYearId): ?DebitNote
    {
        return $this->model
            ->with([
                'details.item.unit',
                'details.condition',
                'details.destination',
                'billSundries',
                'account.state',
                'account.taxDetail',
                'purchaseType',
                'purchaseInvoice',
                'company.state',
            ])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->find($id);
    }
}
