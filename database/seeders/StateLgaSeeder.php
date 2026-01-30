<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateLgaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            'Lagos' => ['Ikeja', 'Alimosho', 'Eti-Osa', 'Surulere', 'Ikorodu'],
            'Abuja' => ['Abaji', 'Bwari', 'Gwagwalada', 'Kuje', 'Kwali', 'Municipal Area Council'],
            'Rivers' => ['Port Harcourt', 'Obio-Akpor', 'Okrika', 'Eleme', 'Bonny'],
            'Kano' => ['Kano Municipal', 'Fagge', 'Dala', 'Gwale', 'Tarauni'],
        ];

        foreach ($states as $stateName => $lgas) {
            $state = \App\Models\State::create(['name' => $stateName]);
            foreach ($lgas as $lgaName) {
                $state->lgas()->create(['name' => $lgaName]);
            }
        }
    }
}
