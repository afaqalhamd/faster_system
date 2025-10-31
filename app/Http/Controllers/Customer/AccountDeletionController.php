<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

class AccountDeletionController extends Controller
{
    /**
     * Show the account deletion page
     * Required for Google Play data deletion policy
     *
     * @return \Illuminate\View\View
     */
    public function showDeleteAccountPage()
    {
        return view('customer.delete-account');
    }
}
