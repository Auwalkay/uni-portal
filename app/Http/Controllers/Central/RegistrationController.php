<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\Tenant;
use App\Models\SubscriptionTier;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    public function show()
    {
        return Inertia::render('Central/Auth/Register', [
            'pricingTiers' => SubscriptionTier::orderBy('max_students', 'asc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'id' => 'required|string|unique:tenants,id|regex:/^[a-z0-9-]+$/|min:3',
            'domain' => 'required|string|unique:domains,domain',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
            'capacity_tier_id' => 'required|exists:subscription_tiers,id',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('tenant-logos', 'public');
        }

        $validated['logo_path'] = $logoPath;

        // Hash password before saving to pending, so we don't store plain text
        $validated['admin_password_hash'] = Hash::make($validated['admin_password']);
        unset($validated['admin_password']); // Remove plain password
        unset($validated['logo']); // Remove uploaded file instance

        $reference = (string) Str::uuid();

        $selectedTier = SubscriptionTier::findOrFail($validated['capacity_tier_id']);
        $amount = $selectedTier->price; // Dynamic Registration Fee in NGN

        $validated['registration_amount'] = $amount; // Store specific amount in payload Data

        $callbackUrl = route('central.register.callback');

        $transaction = $this->paystackService->initializeTransaction(
            $validated['email'],
            $amount,
            $reference,
            $callbackUrl
        );

        if (!$transaction) {
            return back()->withErrors(['paystack' => 'Unable to initialize payment. Please try again later.']);
        }

        PendingRegistration::create([
            'reference' => $reference,
            'data' => $validated,
            'status' => 'pending',
        ]);

        return Inertia::location($transaction['authorization_url']);
    }

    public function paymentCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) {
            return redirect()->route('central.home')->withErrors(['payment' => 'Invalid payment reference.']);
        }

        $pending = PendingRegistration::where('reference', $reference)->first();

        if (!$pending) {
            return redirect()->route('central.home')->withErrors(['payment' => 'Registration data not found.']);
        }

        if ($pending->status === 'paid' || $pending->status === 'processed') {
            return redirect()->route('central.home')->with('success', 'Your registration has already been processed and is awaiting admin approval.');
        }

        $transaction = $this->paystackService->verifyTransaction($reference);

        if ($transaction && $transaction['status'] === 'success') {
            $pending->update(['status' => 'paid']);
            $data = $pending->data;

            // Provision Tenant
            $tenant = Tenant::create([
                'id' => $data['id'],
                'school_name' => $data['school_name'],
                'is_active' => false,
                'email' => $data['email'],
                'address' => $data['address'],
                'logo_path' => $data['logo_path'],
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'admin_password_hash' => $data['admin_password_hash'],
            ]);

            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);

            // Create 1 Year Subscription Record
            $startDate = now();
            $tenant->subscriptions()->create([
                'amount' => $data['registration_amount'],
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addYear()->subDay(),
                'payment_reference' => $reference,
                'payment_method' => 'paystack',
                'notes' => 'Initial Registration Fee',
                'status' => 'active',
            ]);

            // Explicitly seed the newly created tenant to guarantee the custom Admin is generated
            Artisan::call('tenants:seed', [
                '--tenants' => [$tenant->id],
            ]);

            $pending->update(['status' => 'processed']);

            return redirect()->route('central.home')->with('success', 'Payment successful! Your polytechnic portal has been registered and is pending admin approval.');
        }

        $pending->update(['status' => 'failed']);
        return redirect()->route('central.register')->withErrors(['payment' => 'Payment verification failed. Please try again.']);
    }
}
