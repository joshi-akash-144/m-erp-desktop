<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\Http\Request;

class MailLogController extends Controller
{
    public function index()
    {
        return view('company.pages.mail-logs.index');
    }

    public function getData(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = MailLog::with(['user', 'company', 'financialYear'])->where('company_id', $companyId)->where('financial_year_id', $financialYearId);

        $logs = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user ? $log->user->name : 'System',
                    'recipient' => $log->recipient,
                    'subject' => $log->subject,
                    'status' => $log->status,
                    'error_message' => $log->error_message,
                    'sent_at' => $log->sent_at ? $log->sent_at->format('d/m/Y H:i:s') : '',
                ];
            })
        ]);
    }
}
