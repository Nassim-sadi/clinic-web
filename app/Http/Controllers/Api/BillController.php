<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $bills = Bill::query()
            ->with(['patient:id,name,clinic_id', 'doctor:id,name', 'clinic:id,name', 'encounter'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->when($request->clinic_id && $request->user()->isSuperAdmin(), fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->patient_id, fn ($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->payment_status, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($bills);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $validated = $request->validate([
            'encounter_id' => 'nullable|exists:encounters,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.service_id' => 'nullable|exists:services,id',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:draft,sent,paid,partial,cancelled',
            'payment_status' => 'nullable|in:unpaid,pending,paid,refunded',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $invoiceNumber = 'INV-'.strtoupper(Str::random(8));

        $bill = Bill::create([
            'clinic_id' => $clinicId,
            'encounter_id' => $validated['encounter_id'] ?? null,
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'invoice_number' => $invoiceNumber,
            'subtotal' => $validated['subtotal'],
            'discount' => $validated['discount'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'total_amount' => $validated['total_amount'],
            'status' => $validated['status'] ?? Bill::STATUS_DRAFT,
            'payment_status' => $validated['payment_status'] ?? Bill::PAYMENT_STATUS_UNPAID,
            'notes' => $validated['notes'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $bill->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
                'service_id' => $item['service_id'] ?? null,
            ]);
        }

        $bill->load(['patient:id,name', 'doctor:id,name']);

        return response()->json($bill->load(['patient', 'doctor', 'clinic', 'items']), 201);
    }

    public function show(Bill $bill): JsonResponse
    {
        $bill->load(['patient', 'doctor', 'clinic', 'encounter', 'items.service']);

        return response()->json($bill);
    }

    public function update(Request $request, Bill $bill): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.service_id' => 'nullable|exists:services,id',
            'subtotal' => 'sometimes|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,paid,partial,cancelled',
            'payment_status' => 'sometimes|in:unpaid,pending,paid,refunded',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        if (isset($validated['items'])) {
            $bill->items()->delete();

            foreach ($validated['items'] as $item) {
                $bill->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                    'service_id' => $item['service_id'] ?? null,
                ]);
            }
        }

        $bill->update($validated);

        return response()->json($bill->load(['patient', 'doctor', 'clinic', 'items']));
    }

    public function destroy(Bill $bill): JsonResponse
    {
        $bill->items()->delete();
        $bill->delete();

        return response()->json(['message' => 'Bill deleted successfully']);
    }

    public function pay(Request $request, Bill $bill): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $amount = $validated['amount_paid'] ?? $bill->total_amount;

        $bill->update([
            'payment_status' => Bill::PAYMENT_STATUS_PAID,
            'amount_paid' => $amount,
            'status' => Bill::STATUS_PAID,
            'payment_method' => $validated['payment_method'] ?? null,
            'paid_at' => now(),
        ]);

        $bill->load(['patient:id,name', 'doctor:id,name']);

        return response()->json($bill->load(['patient', 'doctor', 'clinic', 'items']));
    }

    public function byEncounter(Request $request, int $encounterId): JsonResponse
    {
        $bill = Bill::query()
            ->with(['patient:id,name', 'doctor:id,name', 'clinic:id,name', 'items'])
            ->where('encounter_id', $encounterId)
            ->first();

        if (! $bill) {
            return response()->json(['message' => 'No bill found for this encounter'], 404);
        }

        return response()->json($bill);
    }

    public function byPatient(Request $request, int $patientId): JsonResponse
    {
        $bills = Bill::query()
            ->with(['doctor:id,name', 'clinic:id,name', 'items'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($bills);
    }

    public function downloadPdf(Bill $bill)
    {
        $bill->load(['clinic', 'patient.user', 'doctor', 'items']);

        $pdf = app(PdfService::class)->generateInvoice($bill);

        return $pdf;
    }

    private function getClinicId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->clinic_id;
    }
}
