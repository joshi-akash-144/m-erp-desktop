<?php

namespace App\Http\Controllers;

class NavigationController extends Controller
{
    /**
     * Redirect to the previous page within the active company's
     * navigation stack (see TrackPreviousUrl middleware).
     */
    public function back()
    {
        $companyId = session('company_id');
        $key = "nav_history.$companyId";
        $stack = session($key, []);

        array_pop($stack); // discard the current page
        $target = array_pop($stack);

        session([$key => $stack]);

        return redirect($target ?? route('dashboard'));
    }
}
