<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Admin/Users/Index', [
            'users' => \App\Models\User::with('roles')->paginate(10)
        ]);
    }
}
