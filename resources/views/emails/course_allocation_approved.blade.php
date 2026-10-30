<!DOCTYPE html>
<html>
<head>
    <title>Course Allocation Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h2 style="color: #059669; margin-top: 0; border-bottom: 2px solid #059669; padding-bottom: 10px;">
            Course Allocation Approved!
        </h2>

        <p>Dear {{ $allocationRequest->staff->user->name ?? 'Lecturer' }},</p>

        <p>Your self course allocation request has been <strong>approved</strong> by the Department / Faculty Administration.</p>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 6px 0;"><strong>Course Code:</strong> <span style="color: #059669; font-weight: bold;">{{ $allocationRequest->course->code ?? 'N/A' }}</span></p>
            <p style="margin: 6px 0;"><strong>Course Title:</strong> {{ $allocationRequest->course->title ?? 'N/A' }}</p>
            <p style="margin: 6px 0;"><strong>Credit Units:</strong> {{ $allocationRequest->course->units ?? 0 }} Units</p>
            <p style="margin: 6px 0;"><strong>Academic Session:</strong> {{ $allocationRequest->session->name ?? 'N/A' }}</p>
            @if($allocationRequest->reviewer)
            <p style="margin: 6px 0;"><strong>Approved By:</strong> {{ $allocationRequest->reviewer->name }}</p>
            @endif
        </div>

        <p>This course is now officially assigned to you. You can view registered students, download course templates, and manage teaching materials from your Teaching portal.</p>

        <p style="margin: 25px 0;">
            <a href="{{ route('admin.teaching.courses.index') }}"
                style="display: inline-block; padding: 12px 24px; background-color: #059669; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Go to My Courses
            </a>
        </p>

        <p style="color: #64748b; font-size: 13px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            Academic Affairs Portal &bull; Automated Notification
        </p>
    </div>
</body>
</html>
