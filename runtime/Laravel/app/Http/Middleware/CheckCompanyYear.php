<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckCompanyYear
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('company_id') || !session()->has('financial_year_id')) {
            // redirect if not set
            return redirect()->route('company-selection.index')
                             ->with('error', 'Please select a company and year first.');
        }

        // Bind session values to the service container for global access
        app()->instance('company_id', (int) session('company_id'));
        app()->instance('financial_year_id', (int) session('financial_year_id'));
        app()->instance('company_name', session('company_name'));
        app()->instance('financial_year_name', session('financial_year_name'));
        app()->instance('financial_year_start', session('financial_year_start'));
        app()->instance('financial_year_end', session('financial_year_end'));
        app()->instance('company_state_id', session('company_state_id'));
        app()->instance('company_uuid', session('company_uuid'));
        app()->instance('company_type', session('company_type'));

        return $next($request);
    }
}
