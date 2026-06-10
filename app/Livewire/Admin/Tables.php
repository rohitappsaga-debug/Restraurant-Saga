<?php

namespace App\Livewire\Admin;

use App\Enums\TableStatus;
use App\Models\Table;
use App\Models\Reservation;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;

class Tables extends Component
{
    public $showAddDialog = false;
    public $showBulkDialog = false;
    public $showReservationDialog = false;
    public $showGroupConfirm = false;
    
    public $editingTableId = null;
    public $selectedTableForRes = null;
    
    // Form fields
    public $number;
    public $capacity = 2;
    public $status = 'free';
    
    // Bulk Add fields
    public $startNumber = 1;
    public $quantity = 1;
    public $bulkCapacity = 2;
    
    // Reservation fields
    public $customerName = '';
    public $customerPhone = '';
    public $resDate = '';
    public $startTime = '12:00';
    public $endTime = '13:00';
    
    // Grouping Mode
    public $isGroupMode = false;
    public $selectedForGroup = [];
    public $primaryTableForGroup = null;

    public function mount()
    {
        $this->resDate = date('Y-m-d');
    }

    public function toggleGroupMode()
    {
        $this->isGroupMode = !$this->isGroupMode;
        $this->selectedForGroup = [];
        $this->primaryTableForGroup = null;
    }

    public function toggleGroupSelection($number)
    {
        if (in_array($number, $this->selectedForGroup)) {
            $this->selectedForGroup = array_diff($this->selectedForGroup, [$number]);
            if ($this->primaryTableForGroup == $number) $this->primaryTableForGroup = null;
        } else {
            $table = Table::where('number', $number)->first();
            if ($table && $table->status === TableStatus::FREE && !$table->group_id) {
                $this->selectedForGroup[] = $number;
            }
        }
    }

    public function createGroup()
    {
        if (count($this->selectedForGroup) < 2 || !$this->primaryTableForGroup) return;

        $groupId = (string) Str::uuid();

        Table::whereIn('number', $this->selectedForGroup)->update([
            'group_id' => $groupId,
            'is_primary' => false
        ]);

        Table::where('number', $this->primaryTableForGroup)->update([
            'is_primary' => true
        ]);

        $this->toggleGroupMode();
        $this->showGroupConfirm = false;
        $this->dispatch('notify', ['message' => 'Tables grouped successfully', 'type' => 'success']);
    }

    public function ungroup($groupId)
    {
        Table::where('group_id', $groupId)->update([
            'group_id' => null,
            'is_primary' => false
        ]);
        $this->dispatch('notify', ['message' => 'Tables ungrouped successfully', 'type' => 'success']);
    }

    public function openEdit($id)
    {
        $table = Table::findOrFail($id);
        $this->editingTableId = $id;
        $this->number = $table->number;
        $this->capacity = $table->capacity;
        $this->status = $table->status->value;
        $this->showAddDialog = true;
    }

    public function save()
    {
        $this->validate([
            'number' => 'required|integer|unique:tables,number,' . $this->editingTableId,
            'capacity' => 'required|integer|min:1',
            'status' => 'required|string',
        ]);

        if ($this->editingTableId) {
            Table::findOrFail($this->editingTableId)->update([
                'number' => $this->number,
                'capacity' => $this->capacity,
                'status' => $this->status,
            ]);
            $this->dispatch('notify', ['message' => 'Table updated successfully', 'type' => 'success']);
        } else {
            Table::create([
                'number' => $this->number,
                'capacity' => $this->capacity,
                'status' => $this->status,
            ]);
            $this->dispatch('notify', ['message' => 'Table created successfully', 'type' => 'success']);
        }

        $this->resetForm();
    }

    public function createBulk()
    {
        $this->validate([
            'startNumber' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'bulkCapacity' => 'required|integer|min:1',
        ]);

        for ($i = 0; $i < $this->quantity; $i++) {
            $num = $this->startNumber + $i;
            if (!Table::where('number', $num)->exists()) {
                Table::create([
                    'number' => $num,
                    'capacity' => $this->bulkCapacity,
                    'status' => TableStatus::FREE,
                ]);
            }
        }

        $this->showBulkDialog = false;
        $this->dispatch('notify', ['message' => "{$this->quantity} tables created", 'type' => 'success']);
    }

    public function delete($id)
    {
        Table::findOrFail($id)->delete();
        $this->dispatch('notify', ['message' => 'Table deleted', 'type' => 'success']);
    }

    public function updateStatus($id, $newStatus)
    {
        Table::findOrFail($id)->update(['status' => $newStatus]);
        $this->dispatch('notify', ['message' => "Status updated to $newStatus", 'type' => 'success']);
    }

    public function openReservation($tableId)
    {
        $this->selectedTableForRes = Table::findOrFail($tableId);
        $this->showReservationDialog = true;
    }

    public function createReservation()
    {
        $this->validate([
            'customerName' => 'required|string',
            'resDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
        ]);

        Reservation::create([
            'table_number' => $this->selectedTableForRes->number,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'date' => $this->resDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'status' => 'pending'
        ]);

        $this->selectedTableForRes->update([
            'status' => TableStatus::RESERVED,
            'reserved_by' => $this->customerName,
            'reserved_time' => $this->resDate . ' ' . $this->startTime
        ]);

        $this->showReservationDialog = false;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->dispatch('notify', ['message' => 'Reservation created', 'type' => 'success']);
    }

    public function checkIn($number)
    {
        $table = Table::where('number', $number)->firstOrFail();
        $reservation = Reservation::where('table_number', $number)->where('status', 'pending')->first();
        
        if ($reservation) {
            $reservation->update(['status' => 'checked-in']);
        }

        $table->update([
            'status' => TableStatus::OCCUPIED,
        ]);

        $this->dispatch('notify', ['message' => 'Checked in successfully', 'type' => 'success']);
    }

    public function cancelReservation($number)
    {
        $table = Table::where('number', $number)->firstOrFail();
        $reservation = Reservation::where('table_number', $number)->where('status', 'pending')->first();
        
        if ($reservation) {
            $reservation->delete();
        }

        $table->update([
            'status' => TableStatus::FREE,
            'reserved_by' => null,
            'reserved_time' => null
        ]);

        $this->dispatch('notify', ['message' => 'Reservation cancelled', 'type' => 'success']);
    }

    public function resetForm()
    {
        $this->editingTableId = null;
        $this->number = null;
        $this->capacity = 2;
        $this->status = 'free';
        $this->showAddDialog = false;
    }

    #[Computed]
    public function tables()
    {
        return Table::query()
            ->orderByRaw("CASE 
                WHEN status = 'reserved' THEN 1 
                WHEN status = 'occupied' THEN 2 
                WHEN status = 'free' THEN 3 
                ELSE 4 
            END")
            ->orderBy('number')
            ->get();
    }

    #[Computed]
    public function stats()
    {
        $tables = $this->tables;
        return [
            'total' => $tables->count(),
            'free' => $tables->where('status', TableStatus::FREE)->count(),
            'occupied' => $tables->where('status', TableStatus::OCCUPIED)->count(),
            'reserved' => $tables->where('status', TableStatus::RESERVED)->count(),
        ];
    }

    #[Computed]
    public function groupedTablesMap()
    {
        return $this->tables->groupBy('group_id');
    }

    public function render()
    {
        return view('livewire.admin.tables')->layout('layouts.admin');
    }
}
