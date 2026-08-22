<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Services\AlarmService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AddCustomer extends Component
{
    public string $name_ar = '';

    public string $name_en = '';

    public string $phone = '';

    public string $address = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $successMessage = null;

    public function submit(AlarmService $alarms): void
    {
        $validated = $this->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = auth()->user();

        $customer = Customer::create([
            'company_id' => $user->activeCompanyId(),
            'route_id' => null,
            'code' => 'C-'.strtoupper(substr(uniqid(), -6)),
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'pending',
            'added_by' => $user->id,
            'is_active' => false,
        ]);

        $alarms->raise(
            'new_customer_pending',
            $customer,
            "New customer pending: {$customer->name_ar}",
            "Rep {$user->name} submitted new customer for approval",
            'warning',
        );

        $this->reset(['name_ar', 'name_en', 'phone', 'address', 'latitude', 'longitude']);
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم إرسال العميل للموافقة'
            : 'Customer submitted for approval';
    }

    public function render()
    {
        return view('livewire.app.add-customer');
    }
}
