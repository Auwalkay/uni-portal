<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SubscriptionTier;

class SubscriptionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Up to 500 students', 'max_students' => 500, 'price' => 1000000],
            ['name' => '501 - 1,000 students', 'max_students' => 1000, 'price' => 1500000],
            ['name' => '1,001 - 5,000 students', 'max_students' => 5000, 'price' => 2500000],
            ['name' => 'Over 5,000 students', 'max_students' => null, 'price' => 5000000],
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::create($tier);
        }
    }
}
