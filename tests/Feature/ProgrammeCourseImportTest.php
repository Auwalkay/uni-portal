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

class ProgrammeCourseImportTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $faculty;
    protected $department;
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

        $this->department = Department::create([
            'name' => 'Computer Sciences',
            'code' => 'CSC',
            'faculty_id' => $this->faculty->id,
            'is_academic' => true,
        ]);

        $this->programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'department_id' => $this->department->id,
            'scholarship_eligible' => true,
        ]);
    }

    public function test_admin_can_download_course_import_template()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academics.programmes.courses.import_template'));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=programme_course_import_template.xlsx');
    }

    public function test_admin_can_import_courses_from_csv()
    {
        $csvContent = "course_code,course_title,units,level,semester,is_compulsory\n" .
                     "CSC 201,Data Structures,4,200,1,1\n" .
                     "MTH 204,Linear Algebra,3,200,2,0\n";

        $file = UploadedFile::fake()->createWithContent('courses.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(
            route('admin.academics.programmes.courses.import_excel', $this->programme->id),
            ['file' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'created' => 2,
                'linked' => 2,
                'skipped' => 0,
                'errors' => []
            ]
        ]);

        // Assert courses exist in the global courses table
        $this->assertDatabaseHas('courses', [
            'code' => 'CSC 201',
            'title' => 'Data Structures',
            'units' => 4,
            'level' => 200,
            'semester' => '1',
            'department_id' => $this->programme->department_id,
        ]);

        $this->assertDatabaseHas('courses', [
            'code' => 'MTH 204',
            'title' => 'Linear Algebra',
            'units' => 3,
            'level' => 200,
            'semester' => '2',
            'department_id' => $this->programme->department_id,
        ]);

        // Assert courses are linked to the programme
        $csc201 = Course::where('code', 'CSC 201')->first();
        $mth204 = Course::where('code', 'MTH 204')->first();

        $this->assertTrue($this->programme->courses()->where('course_id', $csc201->id)->exists());
        $this->assertTrue($this->programme->courses()->where('course_id', $mth204->id)->exists());

        // Assert pivot values are mapped properly
        $this->assertEquals(1, $this->programme->courses()->where('course_id', $csc201->id)->first()->pivot->is_compulsory);
        $this->assertEquals(0, $this->programme->courses()->where('course_id', $mth204->id)->first()->pivot->is_compulsory);
    }

    public function test_admin_cannot_duplicate_existing_courses_but_will_link_them()
    {
        // Pre-create the course CSC 201
        $existingCourse = Course::create([
            'code' => 'CSC 201',
            'title' => 'Existing Data Structures',
            'units' => 3,
            'level' => 100,
            'semester' => '2',
            'department_id' => $this->programme->department_id,
            'is_active' => true,
        ]);

        $csvContent = "course_code,course_title,units,level,semester,is_compulsory\n" .
                     "CSC 201,Data Structures,4,200,1,1\n"; // Different attributes in sheet, but code matches

        $file = UploadedFile::fake()->createWithContent('courses.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(
            route('admin.academics.programmes.courses.import_excel', $this->programme->id),
            ['file' => $file]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats' => [
                'created' => 0,
                'linked' => 1,
                'skipped' => 0,
            ]
        ]);

        // Ensure there is only one CSC 201 record in the database (no duplication)
        $this->assertEquals(1, Course::where('code', 'CSC 201')->count());

        // Ensure the pre-existing course attributes are unchanged
        $this->assertDatabaseHas('courses', [
            'id' => $existingCourse->id,
            'title' => 'Existing Data Structures',
            'units' => 3,
            'level' => 100,
            'semester' => '2',
        ]);

        // Assert it is linked to the programme with the compilation setting from the sheet
        $this->assertTrue($this->programme->courses()->where('course_id', $existingCourse->id)->exists());
        $this->assertEquals(1, $this->programme->courses()->where('course_id', $existingCourse->id)->first()->pivot->is_compulsory);
    }

    public function test_existing_link_updates_compulsion_setting()
    {
        // Pre-create and link course
        $existingCourse = Course::create([
            'code' => 'CSC 201',
            'title' => 'Existing Data Structures',
            'units' => 3,
            'level' => 100,
            'semester' => '2',
            'department_id' => $this->programme->department_id,
            'is_active' => true,
        ]);
        
        $this->programme->courses()->attach($existingCourse->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'is_compulsory' => 0 // Elective
        ]);

        $csvContent = "course_code,course_title,units,level,semester,is_compulsory\n" .
                     "CSC 201,Data Structures,4,200,1,1\n"; // Set to Compulsory (1) in sheet

        $file = UploadedFile::fake()->createWithContent('courses.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(
            route('admin.academics.programmes.courses.import_excel', $this->programme->id),
            ['file' => $file]
        );

        $response->assertStatus(200);
        
        // Assert it is updated on the pivot and counted as skipped (already linked)
        $this->assertEquals(1, $this->programme->courses()->where('course_id', $existingCourse->id)->first()->pivot->is_compulsory);
        $response->assertJsonFragment(['skipped' => 1, 'linked' => 0, 'created' => 0]);
    }

    public function test_unauthorized_users_cannot_import_courses()
    {
        $file = UploadedFile::fake()->create('courses.csv');

        $response = $this->actingAs($this->student)->post(
            route('admin.academics.programmes.courses.import_excel', $this->programme->id),
            ['file' => $file]
        );

        $response->assertStatus(403);
    }
}
