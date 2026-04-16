<?php

namespace App\Observers;

use App\Models\Bill;
use App\Services\NotificationService;

class BillObserver
{
    public function created(Bill $bill): void
    {
        $bill->load(['patient:id,name']);

        NotificationService::billCreated([
            'id' => $bill->id,
            'patient_name' => $bill->patient?->name,
            'total_amount' => $bill->total_amount,
        ], $bill->clinic_id);
    }

    public function updated(Bill $bill): void
    {
        if ($bill->wasChanged('payment_status') && $bill->payment_status === Bill::PAYMENT_STATUS_PAID) {
            $bill->load(['patient:id,name']);

            NotificationService::billPaid([
                'id' => $bill->id,
                'patient_name' => $bill->patient?->name,
                'amount_paid' => $bill->amount_paid ?? $bill->total_amount,
            ], $bill->clinic_id);
        }
    }
}
