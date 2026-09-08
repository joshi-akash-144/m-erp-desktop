<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Carbon\Carbon;


/**
 * ------------------------------------------------------------
 * Company & Financial Year Helpers
 * ------------------------------------------------------------
 */

if (!function_exists('company_id')) {
    function company_id()
    {
        return app()->bound('company_id') ? app('company_id') : session('company_id');
    }
}

if (!function_exists('financial_year_id')) {
    function financial_year_id()
    {
        return app()->bound('financial_year_id') ? app('financial_year_id') : session('financial_year_id');
    }
}

if (!function_exists('company_name')) {
    function company_name()
    {
        return app()->bound('company_name') ? app('company_name') : session('company_name');
    }
}

if (!function_exists('financial_year_name')) {
    function financial_year_name()
    {
        return app()->bound('financial_year_name') ? app('financial_year_name') : session('financial_year_name');
    }
}

if (!function_exists('financial_year_start')) {
    function financial_year_start()
    {
        return app()->bound('financial_year_start') ? app('financial_year_start') : session('financial_year_start');
    }
}

if (!function_exists('financial_year_end')) {
    function financial_year_end()
    {
        return app()->bound('financial_year_end') ? app('financial_year_end') : session('financial_year_end');
    }
}

if (!function_exists('company_state_id')) {
    function company_state_id()
    {
        return app()->bound('company_state_id') ? app('company_state_id') : session('company_state_id');
    }
}

if (!function_exists('company_uuid')) {
    function company_uuid()
    {
        return app()->bound('company_uuid') ? app('company_uuid') : session('company_uuid');
    }
}

if (!function_exists('company_type')) {
    function company_type()
    {
        return app()->bound('company_type') ? app('company_type') : session('company_type');
    }
}

/**
 * ------------------------------------------------------------
 * Auth & User Helpers
 * ------------------------------------------------------------
 */
if (!function_exists('current_user')) {
    function current_user()
    {
        return Auth::user();
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        return Auth::id();
    }
}

if (!function_exists('user_initials')) {
    function user_initials($user = null)
    {
        $user = $user ?? current_user();
        if (!$user) return '';

        $names = explode(' ', $user->name);
        $initials = '';

        for ($i = 0; $i < min(2, count($names)); $i++) {
            $initials .= strtoupper(substr($names[$i], 0, 1));
        }

        return $initials;
    }
}

/**
 * ------------------------------------------------------------
 * Route / URL Helpers
 * ------------------------------------------------------------
 */
if (!function_exists('is_route')) {
    function is_route($name)
    {
        return Route::currentRouteName() === $name;
    }
}

if (!function_exists('is_route_prefix')) {
    function is_route_prefix($prefix)
    {
        return Str::startsWith(Route::currentRouteName(), $prefix);
    }
}

/**
 * ------------------------------------------------------------
 * Number / Currency / Date Helpers
 * ------------------------------------------------------------
 */
if (!function_exists('format_currency')) {
    function format_currency($amount, $symbol = '₹')
    {
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('format_number')) {
    function format_number($number, $decimals = 2)
    {
        return number_format($number, $decimals);
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'd-m-Y')
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($date, $format = 'd-m-Y H:i:s')
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

/**
 * ------------------------------------------------------------
 * Permission / Access Helpers
 * ------------------------------------------------------------
 */
if (!function_exists('has_permission')) {
    function has_permission($permission)
    {
        /** @var \App\Models\User $user */
        $user = current_user();
        return $user && $user->can($permission);
    }
}

/**
 * ------------------------------------------------------------
 * Misc / Utility Helpers
 * ------------------------------------------------------------
 */
if (!function_exists('uuid')) {
    function uuid()
    {
        return (string) Str::uuid();
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path)
    {
        return URL::asset($path);
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax($request)
    {
        return $request->ajax() || $request->wantsJson();
    }
}


if (!function_exists('company_module_enabled')) {
    /**
     * Check if a module (by title) is enabled for the current company.
     * If the company has no module assignments at all, all modules are shown.
     */
    function company_module_enabled(string $moduleTitle): bool
    {
        $companyId = company_id();
        if (!$companyId) {
            return true;
        }

        $cacheKey = "company_modules_{$companyId}";

        $enabledModules = app()->bound($cacheKey) ? app($cacheKey) : null;

        if ($enabledModules === null) {
            $rows = \Illuminate\Support\Facades\DB::table('company_modules as cm')
                ->join('modules as m', 'm.id', '=', 'cm.module_id')
                ->where('cm.company_id', $companyId)
                ->where('cm.is_active', true)
                ->pluck('m.title')
                ->toArray();

            // If no assignments exist, allow all modules
            $enabledModules = empty($rows) ? null : $rows;

            app()->instance($cacheKey, $enabledModules ?? []);
        }

        // null means "no restrictions" — allow everything
        if (empty($enabledModules)) {
            return true;
        }

        return in_array($moduleTitle, $enabledModules);
    }
}

if (!function_exists('menu_can_access')) {
    /**
     * Check if menu item is accessible for current user
     */
    function menu_can_access(array $item): bool
    {
        // dd($item);
        $user = current_user();


        // If item has no permission, allow
        if (empty($item['permission']) && empty($item['children']) && empty($item['sections'])) {
            return true;
        }

        // If item has permission, check user
        /** @var \App\Models\User $user */
        if (!empty($item['permission']) && $user->can($item['permission'])) {
            // dd($item['permission'], $user->name);
            return true;
        }

        // If item has children, check if at least one child is accessible
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (menu_can_access($child)) {
                    return true;
                }
            }
        }
        
        // If item has sections, check if at least one section is accessible
        if (!empty($item['sections'])) {
            foreach ($item['sections'] as $section) {
                if (menu_can_access($section)) {
                    return true;
                }
            }
        }
        
        // No permission
        return false;
    }
}

if (!function_exists('menu_is_active')) {

    function menu_is_active($item)
    {
        // 1. Exact match
        if (isset($item['route']) && Route::is($item['route'])) {
            return true;
        }

        // 2. Base route match (e.g. edit/show belong to index)
        if (isset($item['route']) && str_ends_with($item['route'], '.index')) {
            $baseRoute = substr($item['route'], 0, -6); // Remove '.index'
            if (Route::is($baseRoute . '.show') || Route::is($baseRoute . '.edit') || Route::is($baseRoute . '.update')) {
                return true;
            }
        }

        // 3. Fallback for flat routes like 'dashboard'
        if (isset($item['route']) && !str_contains($item['route'], '.') && Route::is($item['route'])) {
            return true;
        }

        // 4. Check if any children are active
        if (isset($item['children'])) {
            foreach ($item['children'] as $child) {
                if (menu_is_active($child)) {
                    return true;
                }
            }
        }

        // 5. Check sections for modules (Critical for navbar dropdowns)
        if (isset($item['sections'])) {
            foreach ($item['sections'] as $section) {
                if (isset($section['children'])) {
                    foreach ($section['children'] as $child) {
                        if (menu_is_active($child)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}


if (!function_exists('userPermissions')) {
    function userPermissions(array $keys, bool $short = false): array
    {
        $user = current_user();
        /** @var \App\Models\User $user */
        $permissions = [];

        foreach ($keys as $key) {
            $shortKey = $short ? explode('.', $key)[1] : $key;
            $permissions[$shortKey] = $user ? $user->can($key) : false;
        }

        return $permissions;
    }
}


if (!function_exists('exportActions')) {
    /**
     * Generate standard export/print actions for any module
     *
     * @param  string  $module   The route prefix (e.g. 'account-groups')
     * @param  string  $permBase The base permission key (e.g. 'account_group')
     * @return array
     */
    function exportActions(string $module, string $permBase): array
    {
        return [
            [
                'key' => 'print',
                'label' => 'Print',
                'permission' => "{$permBase}.print",
                'icon' => 'icons.print',
                'route' => route("{$module}.print"),
                'attrs' => [
                    'id' => 'export_print_btn',
                    'data-format' => 'print',
                    'data-type' => 'print',
                ],
            ],
            [
                'key' => 'excel',
                'label' => 'Excel',
                'permission' => "{$permBase}.export",
                'icon' => 'icons.xlsx',
                'route' => route("{$module}.export.excel"),
                'attrs' => [
                    'id' => 'export_xlsx_btn',
                    'data-format' => 'xlsx',
                    'data-type' => 'excel',
                ],
            ],
        ];
    }

    function daysDiff($date1, $date2)
    {
        $startDate = new DateTime($date1);
        $endDate = new DateTime($date2);
        // Calculate the difference between two dates
        $interval = $startDate->diff($endDate);
        // Get the difference in days
        $daysDifference = $interval->days;
        return $daysDifference;
    }

    function addDecimals($number, $decimals = 2)
    {
        return number_format($number, $decimals, '.', '');
    }

    function current_date_dmy()
    {
        return date('d-m-Y');
    }


    function amountInWords($number)
    {
        $no = floor($number);
        $point = round($number - $no, 2) * 100;
        $hundred = null;
        $digits_1 = strlen($no);
        $digits_2 = strlen($point);
        $i = 0;
        $j = 0;
        $str = [];
        $pointStr = [];
        $words = ['0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen', '14' => 'fourteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen', '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty', '30' => 'thirty', '40' => 'forty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy', '80' => 'eighty', '90' => 'ninety'];
        $digits = ['', 'hundred', 'thousand', 'lakh', 'crore'];
        while ($i < $digits_1) {
            $divider = $i == 2 ? 10 : 100;
            $number = floor($no % $divider);

            $no = floor($no / $divider);

            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = ($counter = count($str)) && $number > 9 ? 's' : null;

                $hundred = $counter == 1 && $str[0] ? ' and ' : null;
                $str[] = $number < 21 ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else {
                $str[] = null;
            }
        }
        while ($j < $digits_2) {
            $divider = $j == 2 ? 10 : 100;
            $number = floor($point % $divider);

            $no = floor($point / $divider);
            $j += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = ($counter = count($pointStr)) && $number > 9 ? 's' : null;

                $hundred = $counter == 1 && $pointStr[0] ? ' and ' : null;
                $pointStr[] = $number < 21 ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else {
                $pointStr[] = null;
            }
        }

        $str = array_reverse($str);
        $pstr = array_reverse($pointStr);
        $result = implode('', $str);
        $pointresult = implode('', $pstr);
        $points = $point ? '& ' . $pointresult : '& Zero ';
        return ucwords($result) . 'Rupees  ' . ucwords($points) . 'Paisa.';
    }


    function formatIndianNumber($num, $decimal = 2)
    {
        $num = number_format($num, $decimal, '.', ''); // Ensure 2 decimals
        $explodit = explode('.', $num);
        $whole = $explodit[0];
        $decimal = $explodit[1];

        $lastThree = substr($whole, -3);
        $restUnits = substr($whole, 0, -3);

        if ($restUnits != '') {
            $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
            return $restUnits . ',' . $lastThree . '.' . $decimal;
        }

        return $lastThree . '.' . $decimal;
    }

    function convertToSpaceSeparated($inputString)
    {
        $spaceSeparatedString = str_replace('_', '-', $inputString);
        return $spaceSeparatedString;
    }

    function convertToSnakeCase($inputString)
    {
        $lowerCaseString = strtolower($inputString);
        $snakeCaseString = str_replace(' ', '_', $lowerCaseString);
        return $snakeCaseString;
    }

    function isAuditLog()
    {
        if (!config('constants.is_audit_enable', true)) {
            return false;
        }

        return true;
    }
}
