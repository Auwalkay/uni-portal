<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalCourseImportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $faculty;
    protected $department1;
    protected $department2;
    protected $programme;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $this->student = User::create([
            'name' => 'Student User',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->student->assignRole('student');

        $this->faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'FSC',
        ]);

        $this->department1 = Department::create([
            'name' => 'Computer Sciences',
            'code' => 'CSC',
            'faculty_id' => $this->faculty->id,
            'is_academic' => true,
        ]);

        $this->department2 = Department::create([
            'name' => 'Mathematics',
            'code' => 'MTH',
            'faculty_id' => $this->faculty->id,
            'is_academic' => true,
        ]);

        $this->programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'department_id' => $this->department1->id,
            'scholarship_eligible' => true,
        ]);
    }

    public function test_admin_can_download_global_course_import_template()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academics.courses.import_template'));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=global_course_import_template.xlsx');
    }

    public function test_admin_can_import_global_courses_from_csv()
    {
        // One row creates a course globally under CSC, another MTH, one connects CSC 202 to B.Sc Computer Science programme
        $csvContent = "course_code,course_title,units,level,semester,department_code,programme_name\n" .
                     "CSC 201,Data Structures,4,200,1,CSC,\n" .
                     "CSC 202,Database Systems,3,200,1,CSC,B.Sc Computer Science\n" .
                     "MTH 204,Linear Algebra,3,200,2,MTH,\n";

        $file = UploadedFile::fake()->createWithContent('courses.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(
            route('admin.academics.courses.import_excel'),
            ['file' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'created' => 3,
                'linked' => 1,
                'skipped' => 0,
                'errors' => []
            ]
        ]);

        // Verify global course records
        $this->assertDatabaseHas('courses', [
            'code' => 'CSC 201',
            'department_id' => $this->department1->id,
        ]);

        $this->assertDatabaseHas('courses', [
            'code' => 'CSC 202',
            'department_id' => $this->department1->id,
        ]);

        $this->assertDatabaseHas('courses', [
            'code' => 'MTH 204',
            'department_id' => $this->department2->id,
        ]);

        // Verify programme pivot association
        $csc202 = Course::where('code', 'CSC 202')->first();
        $this->assertTrue($this->programme->courses()->where('course_id', $csc202->id)->exists());
    }

    public function test_import_validates_department_code()
    {
        // Row has an invalid department code
        $csvContent = "course_code,course_title,units,level,semester,department_code,programme_name\n" .
                     "CSC 201,Data Structures,4,200,1,INVALID_DEPT,\n";

        $file = UploadedFile::fake()->createWithContent('courses.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(
            route('admin.academics.courses.import_excel'),
            ['file' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'created' => 0,
                'linked' => 0,
                'skipped' => 1,
            ]
        ]);

        $this->assertCount(1, $response->json()['stats']['errors']);
        $this->assertStringContainsString("Department with code 'INVALID_DEPT' not found.", $response->json()['stats']['errors'][0]);
    }

    public function test_unauthorized_user_cannot_import_global_courses()
    {
        $file = UploadedFile::fake()->create('courses.csv');

        $response = $this->actingAs($this->student)->post(
            route('admin.academics.courses.import_excel'),
            ['file' => $file]
        );

        $response->assertStatus(403);
    }
}
