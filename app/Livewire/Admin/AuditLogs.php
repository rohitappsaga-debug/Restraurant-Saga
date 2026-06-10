<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogs extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        return ActivityLog::query()
            ->when($this->search, function ($query) {
                $query->where('action', 'like', '%' . $this->search . '%')
                    ->orWhere('details', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->with('user')
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.audit-logs', [
            'logs' => $this->logs,
        ])->layout('layouts.admin');
    }
}
