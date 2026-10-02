<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Staff;
use App\Models\Department;
use App\Models\Role;
use App\Mail\StaffAccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffImport implements ToModel, WithChunkReading, WithHeadingRow
{
    protected $processedCount = 0;
    protected $departments = [];

    public function model(array $row)
    {
        // Helper to retrieve value with alias fallback
        $getValue = function (...$keys) use ($row) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                    return trim((string) $row[$key]);
                }
            }
            return null;
        };

        $name = $getValue('name', 'full_name', 'staff_name');
        $email = strtolower((string) $getValue('email', 'email_address'));

        if (empty($name) || empty($email)) {
            Log::warning("[StaffImport] Skipped row due to missing name or email.", [
                'name' => $name,
                'email' => $email,
                'row' => $row,
            ]);
            return null;
        }

        // Determine staff number (auto-generate if null/empty)
        $staffNumber = $getValue('staff_number', 'staff_no', 'staff_num');
        if (empty($staffNumber)) {
            $staffNumber = \App\Helpers\StaffNumberHelper::generate();
            Log::info("[StaffImport] Auto-generated staff number {$staffNumber} for {$email}");
        }

        try {
            return DB::transaction(function () use ($row, $getValue, $name, $email, $staffNumber) {
                $password = Str::random(10);
                $isNewUser = false;

                // Find or Create User
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                    ]);
                    $isNewUser = true;
                    Log::info("[StaffImport] Created new user account: {$name} ({$email})");
                } else {
                    $user->update(['name' => $name]);
                    Log::info("[StaffImport] Updated existing user account: {$name} ({$email})");
                }

                if (!$user->hasRole('staff')) {
                    $user->assignRole('staff');
                }

                // Assign specific roles (e.g., Lecturer, Hostel Warden, Security) if provided
                $roleVal = $getValue('role', 'user_role');
                if (!empty($roleVal)) {
                    $roleNames = array_map('trim', explode(',', $roleVal));
                    foreach ($roleNames as $roleName) {
                        $role = Role::where('name', 'like', $roleName)->first();
                        if ($role && !$user->hasRole($role->name)) {
                            $user->assignRole($role->name);
                            Log::info("[StaffImport] Assigned role '{$role->name}' to user {$email}");
                        }
                    }
                }

                // Department Lookup
                $departmentName = $getValue('department', 'dept', 'department_name');
                $departmentId = $this->getDepartmentId($departmentName);

                // State & LGA Lookup
                $stateName = $getValue('state', 'st', 'state_of_origin');
                $stateId = null;
                if (!empty($stateName)) {
                    $stateId = \App\Models\State::where('name', 'like', '%' . $stateName . '%')->value('id');
                }

                $lgaName = $getValue('lga', 'local_government');
                $lgaId = null;
                if (!empty($lgaName) && $stateId) {
                    $lgaId = \App\Models\Lga::where('name', 'like', '%' . $lgaName . '%')
                        ->where('state_id', $stateId)
                        ->value('id');
                }

                // Date processing
                $dateOfBirth = $this->parseDate($getValue('date_of_birth', 'dob', 'birth_date'));
                $dateJoined = $this->parseDate($getValue('date_joined', 'employment_date', 'join_date'));

                // Parse is_academic ("1", "ACADEMIC", "NON ACADEMIC", "0", etc.)
                $rawAcademic = strtolower((string) $getValue('is_academic', 'academic_status'));
                $isAcademic = true;
                if (in_array($rawAcademic, ['0', 'false', 'no', 'non academic', 'non-academic', 'non_academic', 'nonacademic'])) {
                    $isAcademic = false;
                }

                // Create or Update Staff Profile
                $staff = Staff::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'staff_number' => $staffNumber,
                        'designation' => $getValue('designation', 'position', 'title'),
                        'department_id' => $departmentId,
                        'is_academic' => $isAcademic,
                        'phone_number' => $getValue('phone_number', 'phone', 'mobile'),
                        'gender' => strtolower($getValue('gender', 'sex') ?? 'male'),
                        'date_of_birth' => $dateOfBirth,
                        'marital_status' => $getValue('marital_status', 'marital_sta', 'marital'),
                        'address' => $getValue('address', 'contact_address'),
                        'nationality' => $getValue('nationality', 'country') ?? 'Nigerian',
                        'state_id' => $stateId,
                        'lga_id' => $lgaId,
                        'specialization' => $getValue('specialization', 'area_of_specialization'),
                        'research_interests' => $getValue('research_interests', 'research'),
                        'highest_qualification' => $getValue('highest_qualification', 'qualification'),
                        'date_joined' => $dateJoined,
                    ]
                );

                if ($isNewUser) {
                    try {
                        Mail::to($user->email)->send(new StaffAccountCreated($user, $password));
                        Log::info("[StaffImport] Sent credentials email to {$user->email}");
                    } catch (\Throwable $e) {
                        Log::warning("[StaffImport] Could not send staff welcome email to {$user->email}: " . $e->getMessage());
                    }
                }

                $this->processedCount++;
                Log::info("[StaffImport] Successfully imported staff record #{$this->processedCount}: {$name} (Staff No: {$staffNumber}, Email: {$email})");

                return $staff;
            });
        } catch (\Throwable $e) {
            Log::error("[StaffImport] Failed to import row for email {$email}: " . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'row' => $row,
            ]);
            throw $e;
        }
    }

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \Carbon\Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                )->format('Y-m-d');
            } catch (\Throwable $e) {}
        }

        $valStr = trim((string) $value);

        // Check DD/MM/YYYY or DD-MM-YYYY format (e.g. 20/09/1993, 26/08/1999)
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $valStr, $matches)) {
            try {
                $day = (int) $matches[1];
                $month = (int) $matches[2];
                $year = (int) $matches[3];
                if (checkdate($month, $day, $year)) {
                    return \Carbon\Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                }
            } catch (\Throwable $e) {}
        }

        try {
            return \Carbon\Carbon::parse($valStr)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getDepartmentId($name)
    {
        if (empty($name)) {
            return null;
        }

        $name = trim($name);

        if (isset($this->departments[$name])) {
            return $this->departments[$name];
        }

        $dept = Department::where('name', 'like', '%' . $name . '%')
            ->orWhere('code', 'like', '%' . $name . '%')
            ->first();

        if (!$dept) {
            $cleanName = preg_replace('/^(department of|dept of)\s+/i', '', $name);
            $dept = Department::where('name', 'like', '%' . $cleanName . '%')->first();
        }

        $id = $dept ? $dept->id : null;
        $this->departments[$name] = $id;

        return $id;
    }

    public function rules(): array
    {
        return [];
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
