<?php

namespace App\Http\Controllers;

use App\Models\ProductionDepartment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ProductionDepartmentController extends Controller
{
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

            $department = ProductionDepartment::create([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Successfully Saved',
                'department' => $department
            ], Response::HTTP_CREATED);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        try {
            $departments = ProductionDepartment::all();
            return response()->json(
                $departments,
                Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
}
