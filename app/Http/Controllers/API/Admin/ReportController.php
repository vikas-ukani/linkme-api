<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function getReport()
    {
        // Get Location wise counts for users.
        $totalCustomersByState = User::where('user_type', 0)
            ->groupBy('state')
            ->selectRaw('state,count(*) as totalcustomer')
            ->get();
        $totalProvidersByState = User::where('user_type', 1)
            ->groupBy('state')
            ->selectRaw('state,count(*) as totalprovider')
            ->get();

        $data = array_merge($totalProvidersByState->toArray(), $totalCustomersByState->toArray());
        return response()->json([
            'data' => $data,
        ]);
    }
}
