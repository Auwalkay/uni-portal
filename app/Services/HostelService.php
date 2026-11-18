<?php

namespace App\Services;

use App\Imports\HostelRoomImport;
use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelBooking;
use App\Models\HostelFloor;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class HostelService
{
    /**
     * Get hostels query filtered by user permissions and preloaded with room booking stats.
     */
    public function getHostelsForUser(User $user, ?string $sessionId = null): Collection
    {
        $query = Hostel::withCount(['floors', 'fees'])
            ->with(['blocks.floors.rooms' => function ($q) use ($sessionId) {
                $q->with(['bookings' => function ($bq) use ($sessionId) {
                    if ($sessionId) {
                        $bq->where('session_id', $sessionId);
                    }
                    $bq->whereIn('status', ['pending', 'confirmed']);
                }]);
            }]);

        if ($user->can('view_male_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            $query->whereIn('gender_type', ['male', 'mixed']);
        } elseif ($user->can('view_female_hostel_bookings') && !$user->can('manage_hostels') && !$user->hasRole('admin')) {
            $query->whereIn('gender_type', ['female', 'mixed']);
        }

        return $query->latest()->get();
    }

    /**
     * Load detailed hostel structure with active bookings for an admin view.
     */
    public function getHostelWithDetails(Hostel $hostel, ?string $sessionId = null): Hostel
    {
        return $hostel->load([
            'blocks.floors.rooms' => function ($q) use ($sessionId) {
                $q->with(['bookings' => function ($bq) use ($sessionId) {
                    if ($sessionId) {
                        $bq->where('session_id', $sessionId);
                    }
                    $bq->whereIn('status', ['pending', 'confirmed'])
                        ->with(['student.user', 'student.department', 'invoice']);
                }]);
            }
        ]);
    }

    /**
     * Check if a hostel has active bookings preventing deletion.
     */
    public function hasActiveBookings(Hostel $hostel): bool
    {
        return HostelBooking::whereIn('status', ['pending', 'confirmed'])
            ->whereHas('room.floor.block', function ($q) use ($hostel) {
                $q->where('hostel_id', $hostel->id);
            })->exists();
    }

    /**
     * Toggle hostel visibility.
     */
    public function toggleVisibility(Hostel $hostel, User $actor): Hostel
    {
        $hostel->update(['is_visible' => !$hostel->is_visible]);

        $statusText = $hostel->is_visible ? 'unblocked (made visible)' : 'blocked (hidden)';
        activity('hostel')
            ->performedOn($hostel)
            ->causedBy($actor)
            ->log("Hostel '{$hostel->name}' {$statusText}");

        return $hostel;
    }

    /**
     * Import room records from Excel or CSV file.
     */
    public function importRooms(UploadedFile $file, ?Hostel $hostel = null, ?string $blockId = null, ?string $floorId = null, ?User $actor = null): array
    {
        $targetBlock = $blockId ? HostelBlock::find($blockId) : null;
        $targetFloor = $floorId ? HostelFloor::find($floorId) : null;

        $import = new HostelRoomImport($hostel, $targetBlock, $targetFloor);
        Excel::import($import, $file);

        if ($actor) {
            $hostelName = $hostel ? "'{$hostel->name}'" : 'hostels';
            activity()
                ->causedBy($actor)
                ->log("Imported {$import->importedCount} rooms for {$hostelName}");
        }

        return [
            'imported' => $import->importedCount,
            'created' => $import->createdCount,
            'updated' => $import->updatedCount,
            'errors' => $import->errors,
        ];
    }

    /**
     * Cancel/unbook a student's hostel booking and release room capacity.
     */
    public function unbookBooking(HostelBooking $booking, User $actor): HostelBooking
    {
        $booking->update([
            'status' => 'cancelled',
            'updated_by' => $actor->id,
        ]);

        activity('hostel')
            ->performedOn($booking)
            ->causedBy($actor)
            ->withProperties([
                'student_name' => $booking->student?->user?->name,
                'hostel' => $booking->room?->floor?->block?->hostel?->name,
                'room_number' => $booking->room?->room_number,
            ])
            ->log("Cancelled hostel allocation for student {$booking->student?->user?->name}");

        return $booking;
    }

    /**
     * Reactivate/reallocate a previously cancelled or expired hostel booking.
     *
     * @throws \InvalidArgumentException
     */
    public function reallocateBooking(HostelBooking $booking, User $actor): HostelBooking
    {
        $room = $booking->room;
        $currentSession = Session::current();

        if (! $currentSession) {
            throw new \InvalidArgumentException('No active academic session found.');
        }

        $bookedCount = $room->bookings()
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $booking->id)
            ->count();

        if ($bookedCount >= $room->capacity) {
            throw new \InvalidArgumentException('Cannot re-allocate. This room is already fully booked.');
        }

        $activeBookingExists = HostelBooking::where('student_id', $booking->student_id)
            ->where('session_id', $currentSession->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('id', '!=', $booking->id)
            ->exists();

        if ($activeBookingExists) {
            throw new \InvalidArgumentException('This student already has another active hostel booking for this session.');
        }

        $invoiceStatus = $booking->invoice ? $booking->invoice->status : 'unpaid';
        $newStatus = in_array($invoiceStatus, ['paid', 'partial'], true) ? 'confirmed' : 'pending';

        $booking->update([
            'status' => $newStatus,
            'updated_by' => $actor->id,
        ]);

        activity('hostel')
            ->performedOn($booking)
            ->causedBy($actor)
            ->withProperties([
                'student_name' => $booking->student?->user?->name,
                'hostel' => $booking->room?->floor?->block?->hostel?->name,
                'room_number' => $booking->room?->room_number,
                'status' => $newStatus,
            ])
            ->log("Re-allocated room {$booking->room?->room_number} to student {$booking->student?->user?->name}");

        return $booking;
    }
}
