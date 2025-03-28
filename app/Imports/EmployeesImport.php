<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Validator;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts
{
    private $errors = [];

    /**
     * Converts a CSV row to an Employee model.
     */
    public function model(array $row)
    {
        return new Employee([
            'photo_path' => 'employees/default_user.png',
            'internal_id' => $row['internal_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'production_department_id' => $row['production_department_id'],
            'has_room_911_access' => isset($row['has_room_911_access']) ? (bool) $row['has_room_911_access'] : false,
        ]);
    }

    /**
     * Define the validation rules for each row.
     */
    public function rules(): array
    {
        return [
            'internal_id' => 'required|string|unique:employees,internal_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'production_department_id' => 'required|exists:production_departments,id',
            'has_room_911_access' => 'boolean',
        ];
    }

    /**
     * Handles validation failures.
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    /**
     * Batch size for inserts.
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Returns the accumulated errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
