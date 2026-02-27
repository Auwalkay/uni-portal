<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TenantSubscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = TenantSubscription::with('tenant')
            ->latest()
            ->paginate(15);

        return Inertia::render('Central/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
