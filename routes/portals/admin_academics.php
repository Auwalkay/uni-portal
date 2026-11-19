<?php

use App\Http\Controllers\Admin\AcademicController;
use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\CourseRegistrationController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Staff\CourseController;
use App\Http\Controllers\Staff\SelfCourseAllocationController;
use Illuminate\Support\Facades\Route;

// Admissions Management Hub & Modular Applicants
Route::get('/admissions', [AdmissionController::class, 'hub'])->name('admissions.index');
Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

// Undergraduate Applicants Module
Route::middleware(['permission:view_applications'])->prefix('admissions/ug')->name('admissions.ug.')->group(function () {
    Route::get('/', [AdmissionController::class, 'index'])->name('index');
    Route::get('/{applicant}', [AdmissionController::class, 'show'])->name('show');
    Route::get('/{applicant}/letter', [AdmissionController::class, 'downloadLetter'])->name('letter');

    Route::middleware(['permission:review_applications'])->group(function () {
        Route::put('/{applicant}', [AdmissionController::class, 'update'])->name('update');
    });
});

// Postgraduate Applicants Module
Route::middleware(['permission:view_pg_applications'])->prefix('admissions/pg')->name('admissions.pg.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PostgraduateAdmissionController::class, 'index'])->name('index');
    Route::get('/{applicant}', [\App\Http\Controllers\Admin\PostgraduateAdmissionController::class, 'show'])->name('show');
    Route::get('/{applicant}/letter', [\App\Http\Controllers\Admin\PostgraduateAdmissionController::class, 'downloadLetter'])->name('letter');
    Route::get('/{applicant}/transcript-label', [\App\Http\Controllers\Admin\PostgraduateAdmissionController::class, 'downloadTranscriptLabel'])->name('transcript_label');

    Route::middleware(['permission:review_pg_applications'])->group(function () {
        Route::put('/{applicant}', [\App\Http\Controllers\Admin\PostgraduateAdmissionController::class, 'update'])->name('update');
    });
});

// Legacy / Generic Admissions Route Fallbacks
Route::middleware(['permission:view_applications|view_pg_applications'])->group(function () {
    Route::get('/admissions/{applicant}', [AdmissionController::class, 'show'])->name('admissions.show');
    Route::get('/admissions/{applicant}/letter', [AdmissionController::class, 'downloadLetter'])->name('admissions.letter');

    Route::middleware(['permission:review_applications|review_pg_applications'])->group(function () {
        Route::put('/admissions/{applicant}', [AdmissionController::class, 'update'])->name('admissions.update');
    });
});

// Results Management
Route::middleware(['permission:view_results'])->group(function () {
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{course}/entry', [ResultController::class, 'edit'])->name('results.edit');
    Route::get('/results/print', [ResultController::class, 'print'])->name('results.print');
    Route::post('/results/{course}', [ResultController::class, 'update'])->name('results.update');
    Route::post('/results/{course}/upload', [ResultController::class, 'upload'])->name('results.upload');
    Route::post('/results/{course}/publish', [ResultController::class, 'publish'])->name('results.publish');
    Route::post('/results/sessions/{session}/publish', [ResultController::class, 'publishSession'])->name('results.publish-session');

    // Postgraduate Capstone & Dissertation Management (SPGS)
    Route::get('/postgraduate/research/search-candidates', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'searchCandidates'])->name('postgraduate.research.search-candidates');
    Route::get('/postgraduate/research/search-supervisors', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'searchSupervisors'])->name('postgraduate.research.search-supervisors');
    Route::get('/postgraduate/research', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'index'])->name('postgraduate.research.index');
    Route::post('/postgraduate/research', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'store'])->name('postgraduate.research.store');
    Route::put('/postgraduate/research/{project}/supervisors', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'allocateSupervisors'])->name('postgraduate.research.supervisors');
    Route::put('/postgraduate/research/milestones/{milestone}/review', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'reviewMilestone'])->name('postgraduate.research.milestones.review');
    Route::put('/postgraduate/research/{project}/defense', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'scheduleDefense'])->name('postgraduate.research.defense.schedule');
    Route::post('/postgraduate/research/{project}/verdict', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'recordVerdict'])->name('postgraduate.research.defense.verdict');
    Route::post('/postgraduate/research/{project}/examiners', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'addExaminer'])->name('postgraduate.research.examiners.store');
    Route::post('/postgraduate/research/examiners/{examiner}/report', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'recordExaminerReport'])->name('postgraduate.research.examiners.report');
    Route::post('/postgraduate/research/candidacy-requests/{candidacyRequest}', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'processCandidacyRequest'])->name('postgraduate.research.candidacy-requests.process');
    Route::post('/postgraduate/research/progress-reports/{progressReport}', [\App\Http\Controllers\Admin\PostgraduateResearchController::class, 'reviewProgressReport'])->name('postgraduate.research.progress-reports.review');

    // Postgraduate Faculty & Supervisory Governance (SPGS)
    Route::get('/postgraduate/staff', [\App\Http\Controllers\Admin\PostgraduateStaffController::class, 'index'])->name('postgraduate.staff.index');
    Route::put('/postgraduate/staff/{staff}/accreditation', [\App\Http\Controllers\Admin\PostgraduateStaffController::class, 'updateAccreditation'])->name('postgraduate.staff.accreditation.update');
    Route::get('/postgraduate/staff/search-supervisors', [\App\Http\Controllers\Admin\PostgraduateStaffController::class, 'searchSupervisors'])->name('postgraduate.staff.search');
    Route::post('/postgraduate/external-examiners', [\App\Http\Controllers\Admin\PostgraduateStaffController::class, 'storeExternalExaminer'])->name('postgraduate.external-examiners.store');
    Route::put('/postgraduate/external-examiners/{examiner}/toggle', [\App\Http\Controllers\Admin\PostgraduateStaffController::class, 'toggleExternalExaminer'])->name('postgraduate.external-examiners.toggle');
});

// Student Management (Creation & Migration)
Route::middleware(['permission:create_students'])->group(function () {
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
});

Route::middleware(['permission:edit_students'])->group(function () {
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::put('/students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle_status');
    Route::post('/students/{student}/sessions', [\App\Http\Controllers\Admin\StudentSessionController::class, 'store'])->name('students.sessions.store');
    Route::put('/students/{student}/sessions/{session}', [\App\Http\Controllers\Admin\StudentSessionController::class, 'update'])->name('students.sessions.update');
    Route::post('/students/bulk-assign-scholarship', [StudentController::class, 'bulkAssignScholarship'])->name('students.bulk-assign-scholarship');
    Route::get('/students/search-bulk', [StudentController::class, 'searchBulk'])->name('students.search-bulk');
});

// Search & View Students (All Staff)
Route::get('/students', [StudentController::class, 'index'])->name('students.index');
Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

Route::middleware(['permission:manage_student_registrations|fix_course_registration'])->group(function () {
    Route::get('/course-registration', [CourseRegistrationController::class, 'index'])->name('course_registration.index');
    Route::get('/course-registration/export', [CourseRegistrationController::class, 'exportReport'])->name('course_registration.export');
    Route::get('/course-registration/{student}', [CourseRegistrationController::class, 'manage'])->name('course_registration.manage');
    Route::get('/course-registration/{student}/form', [CourseRegistrationController::class, 'downloadForm'])->name('course_registration.form');
    Route::post('/course-registration/{student}', [CourseRegistrationController::class, 'store'])->name('course_registration.store');
});

Route::put('/students/{student}/admission-session', [StudentController::class, 'updateAdmissionSession'])->name('students.update_admission_session');
Route::post('/students/{student}/promote', [StudentController::class, 'promote'])->name('students.promote');
Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset_password')->middleware('permission:reset_student_password|edit_students');

// Course Registrations & Academic Management
Route::middleware(['permission:manage_courses|manage_academic_sessions'])->group(function () {
    Route::get('/courses/{course}/registrations', [CourseRegistrationController::class, 'index'])->name('courses.registrations.index');
    Route::get('/courses/{course}/registrations/export', [CourseRegistrationController::class, 'export'])->name('courses.registrations.export');
});

// Staff Course Management (Teaching)
Route::middleware(['permission:view_results'])->prefix('teaching')->name('teaching.')->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses-self-allocation', [SelfCourseAllocationController::class, 'index'])->name('courses.self_allocation');
    Route::post('/courses-self-allocation', [SelfCourseAllocationController::class, 'store'])->name('courses.self_allocation.store');
    Route::delete('/courses-self-allocation/{id}', [SelfCourseAllocationController::class, 'cancel'])->name('courses.self_allocation.cancel');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/students/upload', [CourseController::class, 'uploadStudents'])->name('courses.students.upload');
    Route::get('/courses/{course}/students/template', [CourseController::class, 'downloadStudentTemplate'])->name('courses.students.template');
    Route::get('/courses/{course}/students/export', [CourseController::class, 'exportStudents'])->name('courses.students.export');
    Route::get('/courses/{course}/attendance/template', [CourseController::class, 'downloadAttendanceTemplate'])->name('courses.attendance.template');
    Route::post('/courses/{course}/attendance/upload', [CourseController::class, 'uploadAttendance'])->name('courses.attendance.upload');
    Route::delete('/courses/{course}/attendance/{date}', [CourseController::class, 'deleteAttendanceDate'])->name('courses.attendance.delete');
    Route::get('/courses/{course}/attendance/{date}/details', [CourseController::class, 'attendanceDateDetails'])->name('courses.attendance.details');
    Route::get('/courses/{course}/attendance/export', [CourseController::class, 'exportAttendance'])->name('courses.attendance.export');
});

// University-Wide Student Attendance Records (Admin / Dean / HOD)
Route::middleware(['permission:manage_courses|view_results|view_department_courses|view_faculty_courses'])->group(function () {
    Route::get('/student-attendance', [\App\Http\Controllers\Admin\StudentAttendanceController::class, 'index'])->name('student_attendance.index');
    Route::get('/student-attendance/export', [\App\Http\Controllers\Admin\StudentAttendanceController::class, 'export'])->name('student_attendance.export');
});

// General Academics & Sessions
Route::middleware(['permission:manage_faculties|manage_departments|manage_programmes|manage_courses|manage_academic_sessions|view_courses|view_department_courses|view_faculty_courses'])->group(function () {
    Route::get('/academics', fn () => redirect()->route('admin.academics.faculties'))->name('academics.index');
    Route::get('/academics/faculties', [AcademicController::class, 'faculties'])->name('academics.faculties');
    Route::get('/academics/departments', [AcademicController::class, 'departments'])->name('academics.departments');
    Route::get('/academics/programmes', [AcademicController::class, 'programmes'])->name('academics.programmes');
    Route::get('/academics/programmes/{programme}/show', [AcademicController::class, 'showProgrammeDetail'])->name('academics.programmes.show');
    Route::get('/academics/courses', [AcademicController::class, 'courses'])->name('academics.courses');
    Route::get('/academics/units', [AcademicController::class, 'units'])->name('academics.units');

    Route::post('/academics/store', [AcademicController::class, 'store'])->name('academics.store');
    Route::post('/academics/update', [AcademicController::class, 'update'])->name('academics.update');
    Route::post('/academics/toggle', [AcademicController::class, 'toggle'])->name('academics.toggle');
    Route::post('/academics/bulk-toggle', [AcademicController::class, 'bulkToggle'])->name('academics.bulk_toggle');
    Route::get('/academics/programmes/{programme}/courses', [AcademicController::class, 'programmeCourses'])->name('academics.programmes.courses');
    Route::post('/academics/programmes/{programme}/courses', [AcademicController::class, 'storeProgrammeCourse'])->name('academics.programmes.courses.store');
    Route::post('/academics/programmes/{programme}/courses/import', [AcademicController::class, 'importProgrammeCourses'])->name('academics.programmes.courses.import');
    Route::post('/academics/programmes/{programme}/courses/import-excel', [AcademicController::class, 'importProgrammeCoursesFromExcel'])->name('academics.programmes.courses.import_excel');
    Route::get('/academics/faculties/export', [AcademicController::class, 'exportFaculties'])->name('academics.faculties.export');
    Route::get('/academics/departments/export', [AcademicController::class, 'exportDepartments'])->name('academics.departments.export');
    Route::get('/academics/programmes/export', [AcademicController::class, 'exportProgrammes'])->name('academics.programmes.export');
    Route::get('/academics/units/export', [AcademicController::class, 'exportUnits'])->name('academics.units.export');
    Route::get('/academics/programmes/{programme}/courses/export', [AcademicController::class, 'exportProgrammeCourses'])->name('academics.programmes.courses.export');
    Route::get('/academics/courses/export', [AcademicController::class, 'exportCourses'])->name('academics.courses.export');
    Route::post('/academics/courses/import-excel', [AcademicController::class, 'importGlobalCoursesFromExcel'])->name('academics.courses.import_excel');
    Route::get('/academics/courses/import-template', [AcademicController::class, 'downloadGlobalCourseImportTemplate'])->name('academics.courses.import_template');
    Route::delete('/academics/programmes/{programme}/courses/{course}', [AcademicController::class, 'destroyProgrammeCourse'])->name('academics.programmes.courses.destroy');
});

Route::middleware(['permission:manage_academic_sessions'])->group(function () {
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');

    // Restricted Session Management
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::put('/sessions/{session}/settings', [SessionController::class, 'updateSettings'])->name('sessions.settings');
    Route::post('/sessions/{session}/fees', [SessionController::class, 'storeFee'])->name('sessions.fees.store');
    Route::delete('/sessions/{session}/fees/{feeConfiguration}', [SessionController::class, 'destroyFee'])->name('sessions.fees.destroy');
    Route::post('/sessions/{session}/activation', [SessionController::class, 'activate'])->name('sessions.activate');
    Route::post('/sessions/{session}/promote', [SessionController::class, 'promoteStudents'])->name('sessions.promote');
    Route::post('/sessions/{session}/toggle-registration', [SessionController::class, 'toggleRegistration'])->name('sessions.toggle_registration');
    Route::post('/sessions/{session}/semesters', [SessionController::class, 'storeSemester'])->name('sessions.semesters.store');
    Route::post('/sessions/{session}/semesters/{semester}/activate', [SessionController::class, 'activateSemester'])->name('sessions.semesters.activate');
    Route::put('/sessions/{session}/semesters/{semester}', [SessionController::class, 'updateSemester'])->name('sessions.semesters.update');
    Route::delete('/sessions/{session}/semesters/{semester}', [SessionController::class, 'destroySemester'])->name('sessions.semesters.destroy');
});

// Timetable Management
Route::middleware(['permission:manage_timetables'])->group(function () {
    Route::post('timetables/toggle-publish', [\App\Http\Controllers\Admin\TimetableController::class, 'togglePublish'])->name('timetables.toggle_publish');
    Route::post('timetables/import', [\App\Http\Controllers\Admin\TimetableController::class, 'import'])->name('timetables.import');
    Route::get('timetables/template', [\App\Http\Controllers\Admin\TimetableController::class, 'template'])->name('timetables.template');
    Route::resource('timetables', \App\Http\Controllers\Admin\TimetableController::class)->only(['index', 'store', 'destroy']);
});
