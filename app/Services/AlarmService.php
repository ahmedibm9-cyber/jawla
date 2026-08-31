<?php

namespace App\Services;

use App\Models\Alarm;
use App\Models\AlarmRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AlarmService implements Contracts\AlarmService
{
    public function raise(string $type, Model $ref, string $title, string $description, string $severity): Alarm
    {
        return DB::transaction(function () use ($type, $ref, $title, $description, $severity): Alarm {
            $companyId = $ref->company_id;

            if ($companyId === null) {
                throw new \InvalidArgumentException('Alarm reference must have a company_id');
            }

            $alarm = Alarm::create([
                'company_id' => $companyId,
                'type' => $type,
                'reference_type' => get_class($ref),
                'reference_id' => $ref->id,
                'title' => $title,
                'description' => $description,
                'severity' => $severity,
            ]);

            $recipients = $this->recipientsFor($type, $alarm->company_id);

            foreach ($recipients as $userId) {
                AlarmRead::firstOrCreate([
                    'alarm_id' => $alarm->id,
                    'user_id' => $userId,
                ], [
                    'read_at' => null,
                ]);
            }

            return $alarm;
        });
    }

    public function acknowledge(Alarm $alarm, int $userId): Alarm
    {
        return DB::transaction(function () use ($alarm, $userId): Alarm {
            $alarm->update(['is_read' => true, 'read_by' => $userId, 'read_at' => now()]);

            AlarmRead::updateOrCreate(
                ['alarm_id' => $alarm->id, 'user_id' => $userId],
                ['acknowledged' => true, 'acknowledged_at' => now(), 'read_at' => now()],
            );

            return $alarm;
        });
    }

    public function resolve(Alarm $alarm, int $userId): Alarm
    {
        return DB::transaction(function () use ($alarm, $userId): Alarm {
            $alarm->update(['is_read' => true, 'read_by' => $userId, 'read_at' => now()]);

            AlarmRead::updateOrCreate(
                ['alarm_id' => $alarm->id, 'user_id' => $userId],
                ['acknowledged' => true, 'resolved' => true, 'acknowledged_at' => now(), 'resolved_at' => now(), 'read_at' => now()],
            );

            return $alarm;
        });
    }

    /** @return array<int, string> */
    private function recipientsFor(string $type, int $companyId): array
    {
        $roleMap = [
            'out_of_stock_request' => ['accounts', 'sales_manager', 'executive'],
            'customer_complaint' => ['sales_manager'],
            'new_customer_pending' => ['sales_manager'],
            'price_quotation_requested' => ['sales_manager'],
            'purchase_request' => ['sales_manager', 'purchasing'],
        ];

        $roles = $roleMap[$type] ?? ['admin'];

        return User::forCompany($companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->pluck('id')
            ->toArray();
    }
}
