<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JambController extends Controller
{
    /**
     * Simulate fetching JAMB details from an external API.
     */
    public function fetchDetails(Request $request)
    {
        $request->validate([
            'jamb_number' => 'required|string|size:10',
        ]);

        // Simulate API delay
        sleep(1);

        $jambNumber = $request->input('jamb_number');

        // Mock Data based on the input
        return response()->json([
            'status' => 'success',
            'data' => [
                'jamb_number' => $jambNumber,
                'first_name' => 'Chinedu',
                'last_name' => 'Okonkwo',
                'middle_name' => 'Emeka',
                'dob' => '2006-05-12',
                'gender' => 'Male',
                'state_of_origin' => 'Anambra',
                'lga' => 'Nnewi North',
                'jamb_score' => rand(200, 350), // Random realistic score
                'subjects' => [
                    'Use of English' => rand(50, 90),
                    'Mathematics' => rand(50, 90),
                    'Physics' => rand(50, 90),
                    'Chemistry' => rand(50, 90),
                ],
                'passport_url' => 'https://ui-avatars.com/api/?name=Chinedu+Okonkwo&background=random',
            ]
        ]);
    }
}
