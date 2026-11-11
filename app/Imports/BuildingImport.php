<?php

namespace App\Imports;

use App\Models\Building;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BuildingImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation
{
    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');
        if (empty($name)) {
            return null;
        }

        $code = isset($row['code']) && !empty(trim($row['code']))
            ? mb_strtoupper(trim($row['code']))
            : Building::generateUniqueCode($name);

        $buildingType = isset($row['building_type']) && !empty(trim($row['building_type']))
            ? strtolower(trim(str_replace([' ', '-'], '_', $row['building_type'])))
            : 'classroom';

        $validTypes = [
            'multipurpose_hall', 'auditorium', 'lecture_theatre', 'e_library', 'classroom',
            'admin_block', 'office', 'laboratory', 'clinic', 'hostel',
            'sports_complex', 'library', 'maintenance', 'other',
        ];
        if (!in_array($buildingType, $validTypes)) {
            $buildingType = 'classroom';
        }

        $nonSeatingTypes = ['admin_block', 'office', 'clinic', 'hostel', 'sports_complex', 'maintenance'];

        $capacity = isset($row['capacity']) && is_numeric($row['capacity'])
            ? (int) $row['capacity']
            : (in_array($buildingType, $nonSeatingTypes) ? null : 100);

        $usableForExams = !in_array($buildingType, $nonSeatingTypes);
        if (isset($row['usable_for_exams'])) {
            $val = strtolower(trim((string) $row['usable_for_exams']));
            if (in_array($val, ['false', '0', 'no', 'disabled'])) {
                $usableForExams = false;
            } elseif (in_array($val, ['true', '1', 'yes', 'enabled'])) {
                $usableForExams = true;
            }
        }

        $status = isset($row['status']) && !empty(trim($row['status']))
            ? strtolower(trim(str_replace(' ', '_', $row['status'])))
            : 'active';

        $validStatuses = ['active', 'under_maintenance', 'inactive'];
        if (!in_array($status, $validStatuses)) {
            $status = 'active';
        }

        return Building::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'building_type' => $buildingType,
                'capacity' => $capacity,
                'usable_for_exams' => $usableForExams,
                'status' => $status,
                'description' => $row['description'] ?? null,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
