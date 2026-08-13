<?php

namespace App\Imports;

use App\Helpers\MatriculationNumberHelper;
use App\Models\Lga;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\StudentAccountCreated;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentImport implements ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    protected $processedCount = 0;
    protected $facultyId;
    protected $departmentId;
    protected $programmeId;
    protected $sessionId;
    protected $level;
    protected $deptCode;
    protected $scholarshipId;

    protected $states = [];

    protected $lgas = [];

    protected $scholarships = [];

    public function __construct($facultyId, $departmentId, $programmeId, $sessionId, $level, $scholarshipId = null)
    {
        $this->facultyId = $facultyId;
        $this->departmentId = $departmentId;
        $this->programmeId = $programmeId;
        $this->sessionId = $sessionId;
        $this->level = $level;
        $this->scholarshipId = $scholarshipId;
        $this->deptCode = \App\Models\Department::where('id', $departmentId)->value('code');
    }

    public function model(array $row)
    {
        return DB::transaction(function () use ($row) {
            $password = Str::random(10);
            $isNewUser = false;

            // Find or Create User
            $user = User::where('email', $row['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name'     => $row['first_name'] . ' ' . $row['last_name'],
                    'email'    => $row['email'],
                    'password' => Hash::make($password),
                ]);
                $isNewUser = true;
            } else {
                $user->update(['name' => $row['first_name'] . ' ' . $row['last_name']]);
            }

            $user->syncRoles(['student']);

            // ── Resolve Programme (batch default OR per-row from Excel) ──────────
            $facultyId    = $this->facultyId;
            $departmentId = $this->departmentId;
            $programmeId  = $this->programmeId;
            $deptCode     = $this->deptCode;

            if (!$programmeId && !empty($row['programme'])) {
                $prog = \App\Models\Programme::with('department.faculty')
                    ->where('name', 'like', '%' . trim($row['programme']) . '%')
                    ->first();

                if ($prog) {
                    $programmeId  = $prog->id;
                    $departmentId = $prog->department_id;
                    $facultyId    = $prog->department?->faculty_id;
                    $deptCode     = $prog->department?->code;
                }
            }

            // ── Resolve Level (batch default OR per-row from Excel) ──────────────
            $level = $this->level;
            if (!$level && !empty($row['level'])) {
                $rowLevel = (string) intval($row['level']);
                if (in_array($rowLevel, ['100', '200', '300', '400', '500'])) {
                    $level = $rowLevel;
                }
            }
            // Final fallback
            $level = $level ?? '100';

            // State & LGA (Optional)
            $stateId = null;
            if (! empty($row['state'])) {
                $stateId = $this->getLookupId('state', $row['state']);
            }

            $lgaId = null;
            if (! empty($row['lga']) && $stateId) {
                $lgaKey = $row['lga'].'_'.$stateId;
                if (! isset($this->lgas[$lgaKey])) {
                    $this->lgas[$lgaKey] = Lga::where('name', 'like', '%'.$row['lga'].'%')
                        ->where('state_id', $stateId)
                        ->value('id');
                }
                $lgaId = $this->lgas[$lgaKey];
            }

            // Generate or use provided matric number
            $matricNumber = !empty($row['matric_number'])
                ? strtoupper(trim($row['matric_number']))
                : MatriculationNumberHelper::generate([
                    'dept_code' => $deptCode,
                    'level'     => $level,
                ]);

            $dob = null;
            if (!empty($row['dob'])) {
                try {
                    $dob = is_numeric($row['dob'])
                        ? \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['dob']))->format('Y-m-d')
                        : \Carbon\Carbon::parse($row['dob'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dob = $row['dob'];
                }
            }

            $prog        = \App\Models\Programme::find($programmeId);
            $entryLevel  = (int) $level;
            $duration    = max(($prog?->duration ?? 4) - ($entryLevel === 200 ? 1 : ($entryLevel === 300 ? 2 : 0)), 1);

            // Resolve Scholarship (batch default OR per-row from Excel)
            $scholarshipId = $this->scholarshipId;
            if (!empty($row['scholarship'])) {
                $scholarshipId = $this->getLookupId('scholarship', $row['scholarship']);
            }

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matriculation_number'  => $matricNumber,
                    'faculty_id'            => $facultyId,
                    'department_id'         => $departmentId,
                    'program_id'            => $programmeId,
                    'admitted_session_id'   => $this->sessionId,
                    'current_level'         => $level,
                    'gender'                => strtolower($row['gender'] ?? 'male'),
                    'dob'                   => $dob,
                    'phone_number'          => $row['phone_number'] ?? null,
                    'address'               => $row['address'] ?? null,
                    'entry_mode'            => $row['entry_mode'] ?? 'UTME',
                    'state_id'              => $stateId,
                    'lga_id'               => $lgaId,
                    'jamb_registration_number' => $row['jamb_reg'] ?? null,
                    'jamb_score'            => $row['jamb_score'] ?? null,
                    'previous_institution'  => $row['previous_institution'] ?? null,
                    'program_duration'      => $duration,
                    'scholarship_id'        => $scholarshipId,
                ]
            );

            $currentActiveSession = \App\Models\Session::current();
            $enrollmentSession    = $currentActiveSession ?? \App\Models\Session::find($this->sessionId);
            $currenSemester       = $enrollmentSession ? $enrollmentSession->semesters()->where('is_current', true)->first() : null;

            StudentSession::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'session_id' => $enrollmentSession?->id ?? $this->sessionId,
                ],
                [
                    'level'    => $level,
                    'status'   => 'active',
                    'semester' => $currenSemester?->name ?? 'First Semester',
                ]
            );

            if ($isNewUser) {
                Mail::to($user->email)->send(new StudentAccountCreated($user, $password));
            }

            $this->processedCount++;

            return $student;
        });
    }

    protected function getLookupId($type, $value)
    {
        if (empty($value)) {
            return null;
        }

        $cache = &$this->{Str::plural($type)}; // Dynamic property access: faculties, departments, etc.

        if (array_key_exists($value, $cache)) {
            return $cache[$value];
        }

        $modelClass = 'App\\Models\\'.ucfirst($type);
        // Special case for Programme -> 'Programme' model but property is 'programmes'
        if ($type === 'programme') {
            $modelClass = Programme::class;
        }
        if ($type === 'session') {
            $modelClass = Session::class;
        } // Ensure correct case if needed

        $id = $modelClass::where('name', 'like', '%'.$value.'%')->value('id');
        $cache[$value] = $id;

        return $id;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\User::where('email', $value)
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'staff');
                        })->exists();
                    if ($exists) {
                        $fail("The email {$value} is already associated with a staff member.");
                    }
                }
            ],
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
