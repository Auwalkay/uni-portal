<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\Lga;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class StudentImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $processedCount = 0;

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            // Find or Create User
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'password' => Hash::make($row['password'] ?? 'password'),
                ]
            );

            if (!$user->wasRecentlyCreated) {
                // Optionally update name if user exists
                $user->update(['name' => $row['first_name'] . ' ' . $row['last_name']]);
            }

            $user->assignRole('student');

            // Lookups
            $faculty = Faculty::where('name', 'like', '%' . $row['faculty'] . '%')->first();
            $department = Department::where('name', 'like', '%' . $row['department'] . '%')->first();
            $programme = Programme::where('name', 'like', '%' . $row['programme'] . '%')->first();
            $session = Session::where('name', 'like', '%' . $row['session'] . '%')->first();

            // State & LGA (Optional)
            $stateId = null;
            if (!empty($row['state'])) {
                $stateId = State::where('name', 'like', '%' . $row['state'] . '%')->value('id');
            }

            $lgaId = null;
            if (!empty($row['lga']) && $stateId) {
                $lgaId = Lga::where('name', 'like', '%' . $row['lga'] . '%')
                    ->where('state_id', $stateId)
                    ->value('id');
            }

            // Generate or use provided matric number
            $matricNumber = $row['matric_number'] ?? $this->generateMatricNumber();

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matriculation_number' => $matricNumber,
                    'faculty_id' => $faculty?->id,
                    'department_id' => $department?->id,
                    'program_id' => $programme?->id,
                    'admitted_session_id' => $session?->id,
                    'current_level' => $row['level'] ?? '100',
                    'gender' => strtolower($row['gender'] ?? 'male'),
                    'dob' => $row['dob'] ?? null,
                    'phone_number' => $row['phone_number'] ?? null,
                    'address' => $row['address'] ?? null,
                    'entry_mode' => $row['entry_mode'] ?? 'UTME',
                    'state_id' => $stateId,
                    'lga_id' => $lgaId,
                    'jamb_registration_number' => $row['jamb_reg'] ?? null,
                    'jamb_score' => $row['jamb_score'] ?? null,
                    'previous_institution' => $row['previous_institution'] ?? null,
                ]
            );

            $this->processedCount++;
            return $student;
        });
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'faculty' => 'required|string',
            'department' => 'required|string',
            'programme' => 'required|string',
            'session' => 'required|string',
        ];
    }

    protected function generateMatricNumber()
    {
        $year = date('Y');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $matricNumber = "UNI/{$year}/{$random}";

        while (Student::where('matriculation_number', $matricNumber)->exists()) {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $matricNumber = "UNI/{$year}/{$random}";
        }

        return $matricNumber;
    }

    public function getProcessedCount()
    {
        return $this->processedCount;
    }
}
