<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Staff;
use App\Models\Department;
use App\Models\Role;
use App\Mail\StaffAccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffImport implements ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    protected $processedCount = 0;
    protected $departments = [];

    public function model(array $row)
    {
        // Skip if email already exists
        if (User::where('email', $row['email'])->exists()) {
            return null;
        }

        // Skip if staff number already exists
        if (Staff::where('staff_number', $row['staff_number'])->exists()) {
            return null;
        }

        return DB::transaction(function () use ($row) {
            $password = Str::random(10);
            $isNewUser = false;

            // Find or Create User
            $user = User::where('email', $row['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                ]);
                $isNewUser = true;
            } else {
                $user->update(['name' => $row['name']]);
            }

            if (!$user->hasRole('staff')) {
                $user->assignRole('staff');
            }

            // Assign specific role (e.g., Lecturer) if provided
            if (!empty($row['role'])) {
                $role = Role::where('name', $row['role'])->first();
                if ($role && !$user->hasRole($role->name)) {
                    $user->assignRole($role->name);
                }
            }

            // Department Lookup
            $departmentId = $this->getDepartmentId($row['department']);

            // State & LGA Lookup
            $stateId = null;
            if (!empty($row['state'])) {
                $stateId = \App\Models\State::where('name', 'like', '%' . trim($row['state']) . '%')->value('id');
            }
            $lgaId = null;
            if (!empty($row['lga']) && $stateId) {
                $lgaId = \App\Models\Lga::where('name', 'like', '%' . trim($row['lga']) . '%')
                    ->where('state_id', $stateId)
                    ->value('id');
            }

            // Date processing
            $dateOfBirth = null;
            if (!empty($row['date_of_birth'])) {
                try {
                    $dateOfBirth = is_numeric($row['date_of_birth'])
                        ? \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_of_birth']))->format('Y-m-d')
                        : \Carbon\Carbon::parse($row['date_of_birth'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dateOfBirth = null;
                }
            }

            $dateJoined = null;
            if (!empty($row['date_joined'])) {
                try {
                    $dateJoined = is_numeric($row['date_joined'])
                        ? \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date_joined']))->format('Y-m-d')
                        : \Carbon\Carbon::parse($row['date_joined'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dateJoined = null;
                }
            }

            // Create or Update Staff Profile
            $staff = Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'staff_number' => $row['staff_number'],
                    'designation' => $row['designation'] ?? null,
                    'department_id' => $departmentId,
                    'is_academic' => filter_var($row['is_academic'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'phone_number' => $row['phone_number'] ?? null,
                    'gender' => strtolower($row['gender'] ?? 'male'),
                    'date_of_birth' => $dateOfBirth,
                    'marital_status' => $row['marital_status'] ?? null,
                    'address' => $row['address'] ?? null,
                    'nationality' => $row['nationality'] ?? null,
                    'state_id' => $stateId,
                    'lga_id' => $lgaId,
                    'specialization' => $row['specialization'] ?? null,
                    'research_interests' => $row['research_interests'] ?? null,
                    'highest_qualification' => $row['highest_qualification'] ?? null,
                    'date_joined' => $dateJoined,
                ]
            );

            if ($isNewUser) {
                Mail::to($user->email)->send(new StaffAccountCreated($user, $password));
            }

            $this->processedCount++;

            return $staff;
        });
    }

    protected function getDepartmentId($name)
    {
        if (empty($name)) {
            return null;
        }

        if (isset($this->departments[$name])) {
            return $this->departments[$name];
        }

        $id = Department::where('name', 'like', '%' . $name . '%')->value('id');
        $this->departments[$name] = $id;

        return $id;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\User::where('email', $value)
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'student');
                        })->exists();
                    if ($exists) {
                        $fail("The email {$value} is already associated with a student.");
                    }
                }
            ],
            'staff_number' => 'required|string',
            'department' => 'nullable|string',
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
