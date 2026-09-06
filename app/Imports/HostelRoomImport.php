<?php

namespace App\Imports;

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HostelRoomImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public ?Hostel $hostel;
    public ?HostelBlock $block;
    public ?HostelFloor $floor;

    public int $importedCount = 0;
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public array $errors = [];

    public function __construct(?Hostel $hostel = null, ?HostelBlock $block = null, ?HostelFloor $floor = null)
    {
        $this->hostel = $hostel;
        $this->block = $block;
        $this->floor = $floor;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $hostelName = trim($row['hostel'] ?? $row['hostel_name'] ?? '');
            $blockName = trim($row['block'] ?? $row['block_name'] ?? '');
            $floorName = trim($row['floor'] ?? $row['floor_name'] ?? '');
            $roomNumber = trim($row['room_number'] ?? $row['room'] ?? $row['unit'] ?? '');
            
            $capacityRaw = $row['capacity'] ?? $row['bed_capacity'] ?? 4;
            $capacity = intval($capacityRaw);
            if ($capacity <= 0) {
                $capacity = 4;
            }

            $isVisible = true;
            if (isset($row['is_visible'])) {
                $visVal = strtolower(trim((string)$row['is_visible']));
                $isVisible = !in_array($visVal, ['0', 'false', 'no', 'disabled']);
            }

            if (empty($roomNumber)) {
                continue; // Skip rows without room number
            }

            // 1. Resolve Target Hostel
            $targetHostel = $this->hostel;
            if (!$targetHostel && !empty($hostelName)) {
                $targetHostel = Hostel::where('name', 'LIKE', $hostelName)->first();
            }

            if (!$targetHostel) {
                $targetHostel = Hostel::first();
            }

            if (!$targetHostel) {
                $this->errors[] = "Row {$rowNumber}: No valid Hostel found to assign room '{$roomNumber}'.";
                continue;
            }

            // 2. Resolve Target Block
            $targetBlock = $this->block;
            if (!$targetBlock || $targetBlock->hostel_id !== $targetHostel->id) {
                if (!empty($blockName)) {
                    $targetBlock = HostelBlock::firstOrCreate([
                        'hostel_id' => $targetHostel->id,
                        'name' => $blockName,
                    ]);
                } else {
                    $targetBlock = HostelBlock::firstOrCreate([
                        'hostel_id' => $targetHostel->id,
                        'name' => 'Block A',
                    ]);
                }
            }

            // 3. Resolve Target Floor
            $targetFloor = $this->floor;
            if (!$targetFloor || $targetFloor->hostel_block_id !== $targetBlock->id) {
                if (!empty($floorName)) {
                    $targetFloor = HostelFloor::firstOrCreate([
                        'hostel_block_id' => $targetBlock->id,
                        'name' => $floorName,
                    ]);
                } else {
                    $targetFloor = HostelFloor::firstOrCreate([
                        'hostel_block_id' => $targetBlock->id,
                        'name' => 'Ground Floor',
                    ]);
                }
            }

            // 4. Update or Create HostelRoom
            $room = HostelRoom::where('hostel_floor_id', $targetFloor->id)
                ->where('room_number', $roomNumber)
                ->first();

            if ($room) {
                $room->update([
                    'capacity' => $capacity,
                    'is_visible' => $isVisible,
                ]);
                $this->updatedCount++;
            } else {
                HostelRoom::create([
                    'hostel_floor_id' => $targetFloor->id,
                    'room_number' => $roomNumber,
                    'capacity' => $capacity,
                    'is_visible' => $isVisible,
                ]);
                $this->createdCount++;
            }

            $this->importedCount++;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
