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
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StudentImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    protected $processedCount = 0;
    protected $faculties = [];
    protected $departments = [];
    protected $programmes = [];
    protected $sessions = [];
    protected $states = [];
    protected $lgas = [];

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            // Find or Create User
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'password' => Hash::make($row['state'] ?? 'password'),
                ]
            );

            if (!$user->wasRecentlyCreated) {
                // Optionally update name if user exists
                $user->update(['name' => $row['first_name'] . ' ' . $row['last_name']]);
            }

            if (!$user->hasRole('student')) {
                $user->assignRole('student');
            }

            // Lookups with caching
            $facultyId = $this->getLookupId('faculty', $row['faculty']);
            $departmentId = $this->getLookupId('department', $row['department']);
            $programmeId = $this->getLookupId('programme', $row['programme']);
            $sessionId = $this->getLookupId('session', $row['session']);

            // State & LGA (Optional)
            $stateId = null;
            if (!empty($row['state'])) {
                $stateId = $this->getLookupId('state', $row['state']);
            }

            $lgaId = null;
            if (!empty($row['lga']) && $stateId) {
                // For LGA, we need to handle it carefully as names might be duplicated across states.
                // Simplified caching for now, assuming unique names or combined key could be used but
                // given the structure, let's just cache by name for simplicity or exact query if needed.
                // To be safe/correct with state dependency, let's query if not cached, or cache with state key.
                $lgaKey = $row['lga'] . '_' . $stateId;
                if (!isset($this->lgas[$lgaKey])) {
                    $this->lgas[$lgaKey] = Lga::where('name', 'like', '%' . $row['lga'] . '%')
                        ->where('state_id', $stateId)
                        ->value('id');
                }
                $lgaId = $this->lgas[$lgaKey];
            }

            // Generate or use provided matric number
            $matricNumber = $row['matric_number'] ?? \App\Helpers\MatriculationNumberHelper::generate();

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matriculation_number' => $matricNumber,
                    'faculty_id' => $facultyId,
                    'department_id' => $departmentId,
                    'program_id' => $programmeId,
                    'admitted_session_id' => $sessionId,
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

    protected function getLookupId($type, $value)
    {
        if (empty($value))
            return null;

        $cache = &$this->{Str::plural($type)}; // Dynamic property access: faculties, departments, etc.

        if (array_key_exists($value, $cache)) {
            return $cache[$value];
        }

        $modelClass = 'App\\Models\\' . ucfirst($type);
        // Special case for Programme -> 'Programme' model but property is 'programmes'
        if ($type === 'programme')
            $modelClass = Programme::class;
        if ($type === 'session')
            $modelClass = Session::class; // Ensure correct case if needed

        $id = $modelClass::where('name', 'like', '%' . $value . '%')->value('id');
        $cache[$value] = $id;

        return $id;
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

    public function chunkSize(): int
    {
        return 500;
    }

    public function getProcessedCount()
    {
        return $this->processedCount;
    }
}
