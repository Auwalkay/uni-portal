<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function show(ApplicantDocument $document)
    {
        if (! Storage::disk('public')->exists($document->path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($document->path)
        );
    }
}
