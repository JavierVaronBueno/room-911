<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Manages employee-related operations including creation, updates, and searches.
 */
class EmployeeController extends Controller
{
    /**
     * Creates a new employee record with the provided data.
     *
     * @param Request $request The HTTP request containing employee data
     * @return \Illuminate\Http\JsonResponse JSON response with creation status and employee details
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'internal_id' => 'required|string|unique:employees,internal_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'production_department_id' => 'required|exists:production_departments,id',
            'has_room_911_access' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('employees', $photoName, 'public');
                $data['photo_path'] = $path;
            }

            $employee = Employee::create($data);

            DB::commit();

            return response()->json([
                'message' => 'Successfully Saved',
                'employee' => $employee
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handles bulk upload of employee records from a CSV file.
     *
     * @param Request $request The HTTP request containing the CSV file
     * @return \Illuminate\Http\JsonResponse JSON response with upload status and results
     */
    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], Response::HTTP_BAD_REQUEST);
        }

        try {
            $import = new EmployeesImport();
            Excel::import($import, $request->file('csv'));

            $errors = $import->getErrors();

            if (!empty($errors)) {
                return response()->json([
                    'message' => 'Some rows failed to import',
                    'errors' => $errors,
                    'imported' => Employee::count()
                ], Response::HTTP_OK);
            }

            return response()->json([
                'message' => 'Employees uploaded successfully',
                'imported' => Employee::count()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while loading the employee CSV file: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Updates an existing employee record with the provided data.
     *
     * @param Request $request The HTTP request containing updated employee data
     * @param int $id The ID of the employee to update
     * @return \Illuminate\Http\JsonResponse JSON response with update status and employee details
     */
    public function update(Request $request, int $id)
    {
        $employee = Employee::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'internal_id' => 'sometimes|string|unique:employees,internal_id,' . $employee->id,
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'production_department_id' => 'sometimes|exists:production_departments,id',
            'has_room_911_access' => 'sometimes|boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            // Handle the image upload if sent
            if ($request->hasFile('photo')) {
                // Delete the previous image if it exists
                if ($employee->photo_path && Storage::disk('public')->exists($employee->photo_path)) {
                    Storage::disk('public')->delete($employee->photo_path);
                }

                // Save the new image
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('employees', $photoName, 'public');
                $data['photo_path'] = $path;
            } else {
                // Keep the existing image if a new one is not submitted
                unset($data['photo']); // Prevent 'photo' from interfering
                $data['photo_path'] = $employee->photo_path; // Preserving the current value
            }

            $employee->update($data);

            if ($employee->photo_path) {
                $employee->photo_url = Storage::url($employee->photo_path);
            }

            DB::commit();

            return response()->json([
                'message' => 'Successfully Updated',
                'employee' => $employee
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while updating an employees data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Searches for employees based on provided criteria.
     *
     * @param Request $request The HTTP request containing search parameters
     * @return \Illuminate\Http\JsonResponse JSON response with matching employee records
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'sometimes|integer|exists:employees,id',
            'internal_id' => 'sometimes|string|exists:employees,internal_id',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'production_department_id' => 'sometimes|exists:production_departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $query = Employee::query();

            if ($request->has('id')) {
                $query->where('id', $request->id);
            }
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
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while searching for employee information: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
