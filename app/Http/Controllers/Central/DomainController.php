<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stancl\Tenancy\Database\Models\Domain;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::with('tenant')->latest()->paginate(15);

        return Inertia::render('Central/Domains/Index', [
            'domains' => $domains,
        ]);
    }
}
