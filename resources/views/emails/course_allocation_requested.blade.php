<!DOCTYPE html>
<html>
<head>
    <title>Course Allocation Request</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8fafc;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h2 style="color: #1e293b; margin-top: 0; border-bottom: 2px solid #4f46e5; padding-bottom: 10px;">
            New Course Allocation Request
        </h2>

        <p>A lecturer has submitted a self course allocation request requiring your review.</p>

        <div style="background-color: #f1f5f9; padding: 16px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 6px 0;"><strong>Lecturer:</strong> {{ $allocationRequest->staff->user->name ?? 'N/A' }}</p>
            <p style="margin: 6px 0;"><strong>Staff Number:</strong> {{ $allocationRequest->staff->staff_number ?? 'N/A' }}</p>
            <p style="margin: 6px 0;"><strong>Department:</strong> {{ $allocationRequest->department->name ?? 'N/A' }}</p>
            @if($allocationRequest->department && $allocationRequest->department->faculty)
            <p style="margin: 6px 0;"><strong>Faculty:</strong> {{ $allocationRequest->department->faculty->name }}</p>
            @endif
            <hr style="border: none; border-top: 1px solid #cbd5e1; margin: 12px 0;">
            <p style="margin: 6px 0;"><strong>Course Code:</strong> <span style="color: #4f46e5; font-weight: bold;">{{ $allocationRequest->course->code ?? 'N/A' }}</span></p>
            <p style="margin: 6px 0;"><strong>Course Title:</strong> {{ $allocationRequest->course->title ?? 'N/A' }}</p>
            <p style="margin: 6px 0;"><strong>Credit Units:</strong> {{ $allocationRequest->course->units ?? 0 }} Units</p>
            <p style="margin: 6px 0;"><strong>Academic Session:</strong> {{ $allocationRequest->session->name ?? 'N/A' }}</p>
            @if(!empty($allocationRequest->notes))
            <p style="margin: 6px 0;"><strong>Lecturer Note:</strong> <em>"{{ $allocationRequest->notes }}"</em></p>
            @endif
        </div>

        <p>Please log in to the portal to approve or reject this request.</p>

        <p style="margin: 25px 0;">
            <a href="{{ route('admin.course-allocations.index') }}"
                style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Review Course Requests
            </a>
        </p>

        <p style="color: #64748b; font-size: 13px; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            This is an automated notification from the Academic Affairs Portal.
        </p>
    </div>
</body>
</html>
