<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Admin/Students/Index', [
            'students' => \App\Models\Student::with('user')->paginate(10)
        ]);
    }
}
