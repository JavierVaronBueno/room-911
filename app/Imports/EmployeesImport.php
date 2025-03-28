<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class EmployeesImport implements ToModel, WithHeadingRow
{
    /**
     * Convierte una fila del CSV en un modelo Employee.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Validar los datos de la fila
        $validator = Validator::make($row, [
            'internal_id' => 'required|string|unique:employees,internal_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'production_department_id' => 'required|exists:production_departments,id',
            'has_room_911_access' => 'boolean',
        ]);

        if ($validator->fails()) {
            // Si la validación falla, puedes lanzar una excepción o ignorar la fila
            return null; // Ignora la fila inválida
        }

        // Crear y devolver un nuevo empleado
        return new Employee([
            'internal_id' => $row['internal_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'production_department_id' => $row['production_department_id'],
            'has_room_911_access' => isset($row['has_room_911_access']) ? (bool) $row['has_room_911_access'] : false,
        ]);
    }
}
