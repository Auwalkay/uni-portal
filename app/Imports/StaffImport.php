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

            // Create or Update Staff Profile
            $staff = Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'staff_number' => $row['staff_number'],
                    'designation' => $row['designation'] ?? null,
                    'department_id' => $departmentId,
                    'is_academic' => filter_var($row['is_academic'] ?? true, FILTER_VALIDATE_BOOLEAN),
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
            'email' => 'required|email',
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
