<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function show(\App\Models\ApplicantDocument $document)
    {
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($document->path)) {
            abort(404);
        }

        return response()->file(
            \Illuminate\Support\Facades\Storage::disk('public')->path($document->path)
        );
    }
}
