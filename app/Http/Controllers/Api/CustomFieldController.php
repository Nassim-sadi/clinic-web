<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = $request->user()->clinic_id ?? session('active_clinic');
        $entityType = $request->get('entity_type');

        $query = CustomField::where('clinic_id', $clinicId);

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        $fields = $query->orderBy('entity_type')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $fields]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string|max:50',
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:text,number,date,select,textarea,checkbox',
            'options' => 'nullable|string',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clinicId = $request->user()->clinic_id ?? session('active_clinic');

        $exists = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $request->entity_type)
            ->where('field_name', $request->field_name)
            ->exists();

        if ($exists) {
            return response()->json(['errors' => ['field_name' => ['Field name already exists for this entity type']]], 422);
        }

        $maxOrder = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $request->entity_type)
            ->max('sort_order') ?? 0;

        $field = CustomField::create([
            'clinic_id' => $clinicId,
            'entity_type' => $request->entity_type,
            'field_name' => $request->field_name,
            'field_type' => $request->field_type,
            'options' => $request->field_type === 'select' ? explode(',', $request->options) : null,
            'required' => $request->required ?? false,
            'sort_order' => $request->sort_order ?? ($maxOrder + 1),
        ]);

        return response()->json(['data' => $field, 'message' => 'Custom field created successfully'], 201);
    }

    public function show(int $id)
    {
        $field = CustomField::findOrFail($id);

        return response()->json(['data' => $field]);
    }

    public function update(Request $request, int $id)
    {
        $field = CustomField::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'field_name' => 'sometimes|string|max:100',
            'field_type' => 'sometimes|in:text,number,date,select,textarea,checkbox',
            'options' => 'nullable|string',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('field_name') && $request->field_name !== $field->field_name) {
            $exists = CustomField::where('clinic_id', $field->clinic_id)
                ->where('entity_type', $field->entity_type)
                ->where('field_name', $request->field_name)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['field_name' => ['Field name already exists for this entity type']]], 422);
            }
        }

        $field->update($request->only(['field_name', 'field_type', 'required', 'sort_order']));

        if ($request->has('options') && $field->field_type === 'select') {
            $field->options = explode(',', $request->options);
            $field->save();
        }

        return response()->json(['data' => $field, 'message' => 'Custom field updated successfully']);
    }

    public function destroy(int $id)
    {
        $field = CustomField::findOrFail($id);
        $field->delete();

        return response()->json(['message' => 'Custom field deleted successfully']);
    }

    public function getByEntity(Request $request, string $entityType)
    {
        $clinicId = $request->user()->clinic_id ?? session('active_clinic');

        $fields = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $fields]);
    }

    public function getEntityValues(Request $request, string $entityType, int $entityId)
    {
        $clinicId = $request->user()->clinic_id ?? session('active_clinic');

        $fields = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->with(['values' => function ($query) use ($entityId) {
                $query->where('entity_id', $entityId);
            }])
            ->get();

        $values = [];
        foreach ($fields as $field) {
            $values[$field->field_name] = $field->values->first()?->value;
        }

        return response()->json(['data' => $values]);
    }

    public function saveEntityValues(Request $request, string $entityType)
    {
        $validator = Validator::make($request->all(), [
            'entity_id' => 'required|integer',
            'values' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clinicId = $request->user()->clinic_id ?? session('active_clinic');

        $fields = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->get()
            ->keyBy('field_name');

        foreach ($request->values as $fieldName => $value) {
            if (isset($fields[$fieldName])) {
                EntityCustomFieldValue::setValue(
                    $fields[$fieldName]->id,
                    $request->entity_id,
                    $value
                );
            }
        }

        return response()->json(['message' => 'Custom field values saved successfully']);
    }
}
