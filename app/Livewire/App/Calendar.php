<?php

namespace App\Livewire\App;

use App\Models\DailyVisitAssignment;
use App\Models\Invoice;
use App\Models\Todo;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property list<array{date: string, day: string, isToday: bool, hasEvents: bool}> $days
 * @property list<array{type: string, title: string, status: string, time: string, amount?: float|string}> $events
 */
#[Layout('layouts.app')]
class Calendar extends Component
{
    public string $currentMonth = '';

    public string $selectedDate = '';

    public function mount(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function previousMonth(): void
    {
        $this->currentMonth = now()->parse($this->currentMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->currentMonth = now()->parse($this->currentMonth.'-01')->addMonth()->format('Y-m');
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    /** @return list<array{date: string, day: string, isToday: bool, hasEvents: bool}> */
    public function getDaysProperty(): array
    {
        $start = now()->parse($this->currentMonth.'-01');
        $end = $start->copy()->endOfMonth();

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d'),
                'isToday' => $date->isToday(),
                'hasEvents' => $this->hasEventsForDate($date->format('Y-m-d')),
            ];
        }

        return $days;
    }

    /** @return list<array{type: string, title: string, status: string, time: string, amount?: float|string}> */
    public function getEventsProperty(): array
    {
        if (! $this->selectedDate) {
            return [];
        }

        $userId = auth()->id();
        $companyId = auth()->user()->company_id;
        $date = now()->parse($this->selectedDate);

        $events = [];

        // Visits for the day
        $visits = DailyVisitAssignment::where('user_id', $userId)
            ->whereDate('visit_date', $date)
            ->with('customer')
            ->get();

        foreach ($visits as $visit) {
            $events[] = [
                'type' => 'visit',
                'title' => $visit->customer->name_ar ?? $visit->customer->name_en,
                'status' => $visit->status,
                'time' => $visit->created_at->format('H:i'),
            ];
        }

        // Invoices for the day
        $invoices = Invoice::where('company_id', $companyId)
            ->where('created_at', '>=', $date->startOfDay())
            ->where('created_at', '<=', $date->endOfDay())
            ->where('status', '!=', 'cancelled')
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            $events[] = [
                'type' => 'invoice',
                'title' => $invoice->invoice_number.' - '.($invoice->customer->name_ar ?? $invoice->customer->name_en),
                'status' => $invoice->status,
                'time' => $invoice->created_at->format('H:i'),
                'amount' => (float) $invoice->total,
            ];
        }

        // Todos for the day
        $todos = Todo::where('user_id', $userId)
            ->whereDate('due_date', $date)
            ->get();

        foreach ($todos as $todo) {
            $events[] = [
                'type' => 'todo',
                'title' => $todo->title,
                'status' => $todo->status,
                'time' => $todo->due_date->format('H:i'),
            ];
        }

        return $events;
    }

    private function hasEventsForDate(string $date): bool
    {
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;
        $dateObj = now()->parse($date);

        return DailyVisitAssignment::where('user_id', $userId)
            ->whereDate('visit_date', $dateObj)
            ->exists()
            || Invoice::where('company_id', $companyId)
                ->whereDate('created_at', $dateObj)
                ->exists()
            || Todo::where('user_id', $userId)
                ->whereDate('due_date', $dateObj)
                ->exists();
    }

    public function render(): View
    {
        return view('livewire.app.calendar', [
            'days' => $this->days,
            'events' => $this->events,
        ]);
    }
}
