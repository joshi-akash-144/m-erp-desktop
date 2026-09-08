<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use App\Helpers\AjaxResponse;
use App\Services\StockVoucherService;
use Symfony\Component\HttpFoundation\JsonResponse;
class StockController extends Controller
{
    protected StockVoucherService $service;
    public function __construct(StockVoucherService $service)
    {
        $this->middleware('permission:stock_status.list')->only(['index']);
        $this->middleware('permission:stock_status.print')->only(['print']);
        $this->middleware('permission:stock_status.export')->only(['export']);

        $this->service = $service;
    }
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $filters = $request->only([
                'start_date',
                'end_date',
                'as_at_date',
            ]);

            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 5);
            $stocks = $this->service->getStockStatus(companyId: company_id(),financialYearId: financial_year_id(),filters: $filters);

        // dd($stocks);
            return AjaxResponse::success(data: $stocks);
        }
        return view('company.pages.stock.index');
    }
    public function selectItemForMonthWise(Request $request){
        if (!$request->item_id) {
            return AjaxResponse::error('No item selected');
        }
        // Save item_id in session
        session(['selected_stock_item_id' => $request->item_id]);

        $isSaved = session()->has(['selected_stock_item_id']);

        return AjaxResponse::success(data: ['success' => $isSaved]);
    }

    public function monthWiseStockStatus(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        // Accept item_id from the request directly (fallback to session for backwards compat)
        $itemId = $request->input('item_id', session('selected_stock_item_id'));

        if (!$itemId) {
            return AjaxResponse::error('No item selected');
        }

        $filters = $request->only(['start_date', 'end_date', 'as_at_date', 'size']);

        $monthWiseStocks = $this->service->monthWiseStockStatus(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            id: (int) $itemId,
            filters: $filters,
        );

        return AjaxResponse::success(data: $monthWiseStocks);
    }
    public function selectItemForDateWise(Request $request){
        // dd("Month Selected, item_id: {$request->item_id}, start_date: {$request->start_date}, end_date: {$request->end_date}");
        session([
            'selected_month_wise_item_id' => $request->item_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        $isSaved = session()->has(['selected_month_wise_item_id', 'start_date', 'end_date']);

        return AjaxResponse::success(data: ['success' => $isSaved]);
    }
    public function dateWiseStockStatus(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $itemId    = $request->input('item_id', session('selected_month_wise_item_id'));
        $startDate = $request->input('start_date', session('start_date'));
        $endDate   = $request->input('end_date', session('end_date'));

        if (!$itemId || !$startDate || !$endDate) {
            return AjaxResponse::error('Missing required parameters');
        }

        $filters = [
            'size' => (int) $request->input('size', 9999),
            'page' => (int) $request->input('page', 1),
        ];

        $dateWiseStocks = $this->service->dateWiseStockStatus(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            id: $itemId,
            startDate: $startDate,
            endDate: $endDate,
            filters: $filters,
        );
        return AjaxResponse::success(data: $dateWiseStocks);
    }

}
