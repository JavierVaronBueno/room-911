<?php

namespace App\Http\Controllers;

use App\Models\ProductionDepartment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Manages production department operations including creation and retrieval.
 */
class ProductionDepartmentController extends Controller
{
    /**
     * Creates a new production department with the provided data.
     *
     * @param Request $request The HTTP request containing department data
     * @return \Illuminate\Http\JsonResponse JSON response with creation status and department details
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:production_departments,name|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $department = ProductionDepartment::create([
                'name' => $request->name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Successfully Saved',
                'department' => $department
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            DB::rollBack();
            Log::debug($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieves all production departments.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with all department records
     */
    public function index()
    {
        try {
            $departments = ProductionDepartment::all();
            return response()->json(
                $departments,
                Response::HTTP_OK);

        } catch (Exception $e) {
            Log::debug($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
