<!DOCTYPE html>
<html>
<head>
    <title>Course Allocation Request Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h2 style="color: #dc2626; margin-top: 0; border-bottom: 2px solid #dc2626; padding-bottom: 10px;">
            Course Allocation Request Update
        </h2>

        <p>Dear {{ $allocationRequest->staff->user->name ?? 'Lecturer' }},</p>

        <p>Your self course allocation request for the following course has been <strong>declined</strong> by the administration:</p>

        <div style="background-color: #fef2f2; border: 1px solid #fecaca; padding: 16px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 6px 0;"><strong>Course:</strong> {{ $allocationRequest->course->code ?? 'N/A' }} - {{ $allocationRequest->course->title ?? 'N/A' }}</p>
            <p style="margin: 6px 0;"><strong>Academic Session:</strong> {{ $allocationRequest->session->name ?? 'N/A' }}</p>
            @if(!empty($allocationRequest->rejection_reason))
            <p style="margin: 6px 0;"><strong>Reason:</strong> <em>"{{ $allocationRequest->rejection_reason }}"</em></p>
            @endif
            @if($allocationRequest->reviewer)
            <p style="margin: 6px 0;"><strong>Reviewed By:</strong> {{ $allocationRequest->reviewer->name }}</p>
            @endif
        </div>

        <p>You may log in to select an alternative available course for this session.</p>

        <p style="margin: 25px 0;">
            <a href="{{ route('admin.teaching.courses.self_allocation') }}"
                style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                View Available Courses
            </a>
        </p>

        <p style="color: #64748b; font-size: 13px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            Academic Affairs Portal &bull; Automated Notification
        </p>
    </div>
</body>
</html>
