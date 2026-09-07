<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            [
                'name' => 'Multipurpose Hall A',
                'code' => 'MPH-A',
                'building_type' => 'multipurpose_hall',
                'capacity' => 250,
                'usable_for_exams' => true,
                'status' => 'active',
                'description' => 'Main university multipurpose hall for large examination batches, convocation, and events.',
            ],
            [
                'name' => 'Multipurpose Hall B',
                'code' => 'MPH-B',
                'building_type' => 'multipurpose_hall',
                'capacity' => 200,
                'usable_for_exams' => true,
                'status' => 'active',
                'description' => 'Secondary hall equipped with air conditioning and partition walls.',
            ],
            [
                'name' => 'Grand Auditorium 1',
                'code' => 'AUD-1',
                'building_type' => 'auditorium',
                'capacity' => 350,
                'usable_for_exams' => true,
                'status' => 'active',
                'description' => 'Tiered seating auditorium equipped with dual projector screens and PA system.',
            ],
            [
                'name' => 'E-Library Complex',
                'code' => 'ELIB-01',
                'building_type' => 'e_library',
                'capacity' => 150,
                'usable_for_exams' => true,
                'status' => 'active',
                'description' => 'Computer laboratory hall for CBT online examinations.',
            ],
            [
                'name' => 'Faculty of Science Lecture Theatre 1',
                'code' => 'LT-01',
                'building_type' => 'lecture_theatre',
                'capacity' => 120,
                'usable_for_exams' => true,
                'status' => 'active',
                'description' => 'Science block main lecture theatre.',
            ],
            [
                'name' => 'Engineering Hall C',
                'code' => 'ENG-HALL-C',
                'building_type' => 'lecture_theatre',
                'capacity' => 180,
                'usable_for_exams' => true,
                'status' => 'under_maintenance',
                'description' => 'Currently under HVAC maintenance.',
            ],
            [
                'name' => 'Staff Administrative Annex Classroom 3',
                'code' => 'ADMIN-CR3',
                'building_type' => 'classroom',
                'capacity' => 45,
                'usable_for_exams' => false,
                'status' => 'active',
                'description' => 'Departmental meeting room, not approved for semester examinations.',
            ],
        ];

        foreach ($buildings as $data) {
            Building::updateOrCreate(['code' => mb_strtoupper($data['code'])], $data);
        }
    }
}
