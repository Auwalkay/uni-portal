<?php

use App\Http\Controllers\Admin\ExamScheduleController;
use Illuminate\Support\Facades\Route;

// Exam Management (Granular Permissions)
Route::middleware(['permission:manage_exams|view_exams|create_exams|edit_exams|delete_exams|publish_exams|assign_invigilators|scan_exam_cards|mark_exam_attendance|log_exam_incidents|access_admin_dashboard'])->group(function () {
    Route::post('exams/toggle-publish', [ExamScheduleController::class, 'togglePublish'])->name('exams.toggle_publish')->middleware('permission:manage_exams|publish_exams');
    Route::get('exams', [ExamScheduleController::class, 'index'])->name('exams.index')->middleware('permission:manage_exams|view_exams');
    Route::get('exams/exercises/{exam}', [ExamScheduleController::class, 'showExam'])->name('exams.exercises.show')->middleware('permission:manage_exams|view_exams');
    Route::post('exams/exercises', [ExamScheduleController::class, 'storeExam'])->name('exams.exercises.store')->middleware('permission:manage_exams|create_exams');
    Route::put('exams/exercises/{exam}', [ExamScheduleController::class, 'updateExam'])->name('exams.exercises.update')->middleware('permission:manage_exams|edit_exams');
    Route::delete('exams/exercises/{exam}', [ExamScheduleController::class, 'destroyExam'])->name('exams.exercises.destroy')->middleware('permission:manage_exams|delete_exams');
    Route::post('exams/exercises/{exam}/toggle-publish', [ExamScheduleController::class, 'togglePublishExam'])->name('exams.exercises.toggle_publish')->middleware('permission:manage_exams|publish_exams');
    Route::post('exams/exercises/{exam}/toggle-docket', [ExamScheduleController::class, 'toggleDocketExam'])->name('exams.exercises.toggle_docket')->middleware('permission:manage_exams|publish_exams');
    Route::get('exams/create', [ExamScheduleController::class, 'create'])->name('exams.create')->middleware('permission:manage_exams|create_exams');
    Route::get('exams/scanner', [ExamScheduleController::class, 'scanner'])->name('exams.scanner')->middleware('permission:manage_exams|scan_exam_cards|mark_exam_attendance');
    Route::get('exams/incidents', [ExamScheduleController::class, 'incidentsIndex'])->name('exams.incidents.index')->middleware('permission:manage_exams|log_exam_incidents');
    Route::post('exams/incidents', [ExamScheduleController::class, 'storeIncident'])->name('exams.incidents.general_store')->middleware('permission:manage_exams|log_exam_incidents');
    Route::post('exams', [ExamScheduleController::class, 'store'])->name('exams.store')->middleware('permission:manage_exams|create_exams');
    Route::post('exams/import', [ExamScheduleController::class, 'import'])->name('exams.import')->middleware('permission:manage_exams|create_exams');
    Route::get('exams/template', [ExamScheduleController::class, 'downloadTemplate'])->name('exams.template');
    Route::get('exams/verify-pass/{token}', [ExamScheduleController::class, 'verifyPass'])->name('exams.verify_pass')->middleware('permission:manage_exams|scan_exam_cards|mark_exam_attendance');
    Route::post('exams/mark-attendance', [ExamScheduleController::class, 'markAttendance'])->name('exams.mark_attendance')->middleware('permission:manage_exams|mark_exam_attendance|scan_exam_cards');
    Route::get('exams/{exam}/edit', [ExamScheduleController::class, 'edit'])->name('exams.edit')->middleware('permission:manage_exams|edit_exams');
    Route::put('exams/{exam}', [ExamScheduleController::class, 'update'])->name('exams.update')->middleware('permission:manage_exams|edit_exams');
    Route::delete('exams/{exam}', [ExamScheduleController::class, 'destroy'])->name('exams.destroy')->middleware('permission:manage_exams|delete_exams');
    Route::post('exams/{exam}/invigilators', [ExamScheduleController::class, 'assignInvigilator'])->name('exams.invigilators.assign')->middleware('permission:manage_exams|assign_invigilators');
    Route::delete('exam-invigilators/{invigilator}', [ExamScheduleController::class, 'removeInvigilator'])->name('exams.invigilators.remove')->middleware('permission:manage_exams|assign_invigilators');
    Route::post('exams/{exam}/incidents', [ExamScheduleController::class, 'logIncident'])->name('exams.incidents.store')->middleware('permission:manage_exams|log_exam_incidents');
    Route::put('exam-incidents/{incident}', [ExamScheduleController::class, 'updateIncident'])->name('exams.incidents.update')->middleware('permission:manage_exams|log_exam_incidents');

    // Course Registrations & Enrolments Explorer for Exams Office
    Route::get('exams/courses/students', [ExamScheduleController::class, 'courseStudentsIndex'])->name('exams.courses.students')->middleware('permission:manage_exams|view_exams');
    Route::get('exams/courses/{course}/students', [ExamScheduleController::class, 'courseStudents'])->name('exams.courses.show_students')->middleware('permission:manage_exams|view_exams');
    Route::get('exams/courses/{course}/export-students', [ExamScheduleController::class, 'exportCourseStudents'])->name('exams.courses.export_students')->middleware('permission:manage_exams|view_exams');
    Route::get('exams/api/course-enrolment-counts', [ExamScheduleController::class, 'apiCourseEnrolmentCounts'])->name('exams.api.enrolment_counts')->middleware('permission:manage_exams|view_exams|create_exams');
});
