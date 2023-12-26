<?php

namespace App\Http\Controllers\API\Admin;

use App\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{



    /**
     * Fetch admin user from id
     *
     * @param [type] $id
     * @return void
     */
    public function profile($id)
    {
        $admin = Admin::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $admin
        ]);
    }

    /**
     * Update admin profile
     *
     * @param [type] $id
     * @param Request $request
     * @return void
     */
    public function updateProfile($id, Request $request)
    {
        $admin = Admin::where('id', $id)->first();
        if ($admin) {
            Admin::where('id', $id)->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
            return response()->json([
                'status' => 'success',
                'data' => $admin->refresh(),
                'message' => 'Profile updated successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'Admin not found'
            ]);
        }
    }
}
