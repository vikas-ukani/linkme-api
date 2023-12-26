<?php

namespace App\Http\Controllers\API;

use App\User;
use Validator;
use Illuminate\Http\Request;
use App\ProviderbussinessTime;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;

class ProviderAvailbillityController extends Controller
{
    public function Providerbussinesstime(Request $request)
    {
        if (Auth::check()) {

            if (Auth::user()->hasRole('service-provider')) {

                $validator = Validator::make($request->all(), [
                    'availbillity' => 'required', 'vacations' => 'required'

                ]);

                if ($validator->fails()) {
                    return response()
                        ->json(['error' => $validator->errors()], 401);
                }

                $businesstime = ProviderbussinessTime::where('providerid', Auth::user()->id)
                    ->first();
                if ($businesstime != null) {
                    ProviderbussinessTime::where('providerid', Auth::user()->id)
                        ->update(['availbillity' => $request->availbillity, 'vacations' => $request->vacations]);
                    $businesstime = ProviderbussinessTime::where('providerid', Auth::user()->id)
                        ->first();
                    return response()
                        ->json(['message' => 'Availbillity updated', 'businesstime' => $businesstime], 200);
                } else {
                    $businesstimes = new ProviderbussinessTime;
                    $businesstimes->providerid = Auth::user()->id;
                    $businesstimes->availbillity = $request->availbillity;
                    $businesstimes->vacations = $request->vacations;
                    $businesstimes->save();
                    return response()
                        ->json(['message' => 'Availbillity created', 'businesstime' => $businesstimes], 200);
                }
            } else {

                return response()->json(['message' => "Not Authorized User"], 401);
            }
        } else {
            return response()
                ->json(['message' => "Not Authorized User"], 401);
        }
    }

    public function Getavailbillity()
    {

        if (Auth::check()) {

            if (Auth::user()
                ->hasRole('service-provider')
            ) {
                $av = ProviderbussinessTime::where('providerid', Auth::user()->id)
                    ->first();
                if ($av != null) {
                    $av['vacations'] = json_decode($av->vacations);
                    $av['availbillity'] = json_decode($av->availbillity);
                    return response()
                        ->json(['availbillity' => $av], 200);
                } else {
                    return response()->json(['availbillity' => $av], 200);
                }
            } else {

                return response()->json(['message' => "Not Authorized User"], 401);
            }
        } else {
            return response()
                ->json(['message' => "Not Authorized User"], 401);
        }
    }

    public function GetavailbillityProvider($id)
    {

        $availbillity = ProviderbussinessTime::where('providerid', $id)->first();
        $availbillity['vacations'] = json_decode($availbillity->vacations);
        $availbillity['availbillity'] = json_decode($availbillity->availbillity);
        return response()
            ->json(['availbillity' => $availbillity], 200);
    }
}
