<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateInvoice($bill)
    {
        $pdf = Pdf::loadView('pdf.invoice', [
            'bill' => $bill->load(['clinic', 'patient.user', 'doctor', 'items']),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("invoice-{$bill->invoice_number}.pdf");
    }

    public function generatePrescription($prescription)
    {
        $doctorProfile = $prescription->doctor->doctorProfile ?? null;
        $clinic = $prescription->encounter?->clinic ?? $prescription->doctor->clinic ?? null;

        $pdf = Pdf::loadView('pdf.prescription', [
            'prescription' => $prescription->load(['patient.user', 'doctor', 'encounter']),
            'doctorProfile' => $doctorProfile,
            'clinic' => $clinic,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("prescription-{$prescription->id}.pdf");
    }
}
