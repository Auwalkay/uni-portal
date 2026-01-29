<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeType;
use App\Models\FeeConfiguration;
use App\Models\Session;
use Illuminate\Support\Str;

class FeeSeeder extends Seeder
{
    public function run()
    {
        $session = Session::where('is_current', true)->first();

        if (!$session) {
            $this->command->error('No active session found. Please seed academic sessions first.');
            return;
        }

        $fees = [
            'Tuition Fee' => 100000,
            'ICT Fee' => 15000,
            'Faculty Fee' => 5000,
            'Departmental Fee' => 3000,
            'Sports Fee' => 2000,
            'Medical Fee' => 5000,
            'Library Fee' => 2000,
        ];

        foreach ($fees as $name => $amount) {
            $feeType = FeeType::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $name . ' for the session']
            );

            FeeConfiguration::create([
                'fee_type_id' => $feeType->id,
                'session_id' => $session->id,
                'amount' => $amount,
                'level' => null, // Global
                'faculty_id' => null, // Global
                'department_id' => null, // Global
                'program_id' => null, // Global
                'is_compulsory' => true,
            ]);
        }
    }
}
