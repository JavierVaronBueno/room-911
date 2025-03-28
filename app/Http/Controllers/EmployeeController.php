<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;

class EmployeeController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'internal_id' => 'required|string|unique:employees,internal_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'production_department_id' => 'required|exists:production_departments,id',
            'has_room_911_access' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], Response::HTTP_BAD_REQUEST);
        }

        $employee = Employee::create($request->all());
        return response()->json($employee, Response::HTTP_CREATED);
    }

    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], Response::HTTP_BAD_REQUEST);
        }

        Excel::import(new EmployeesImport, $request->file('csv'));
        return response()->json(['message' => 'Employees uploaded successfully'], Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'internal_id' => 'string|unique:employees,internal_id,' . $employee->id,
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'production_department_id' => 'exists:production_departments,id',
            'has_room_911_access' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], Response::HTTP_BAD_REQUEST);
        }

        $employee->update($request->all());
        return response()->json($employee, Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $query = Employee::query();

        if ($request->has('internal_id')) {
            $query->where('internal_id', $request->internal_id);
        }
        if ($request->has('first_name')) {
            $query->where('first_name', 'like', '%' . $request->first_name . '%');
        }
        if ($request->has('last_name')) {
            $query->where('last_name', 'like', '%' . $request->last_name . '%');
        }
        if ($request->has('production_department_id')) {
            $query->where('production_department_id', $request->production_department_id);
        }

        return response()->json($query->get(), Response::HTTP_OK);
    }
}
