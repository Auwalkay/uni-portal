<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Admin/Payments/Index', [
            'invoices' => \App\Models\Invoice::with('user')->latest()->paginate(10)
        ]);
    }
}
