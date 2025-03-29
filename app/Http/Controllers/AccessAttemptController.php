<?php

namespace App\Http\Controllers;

use App\Models\AccessAttempt;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Handles access attempt operations including simulation and history retrieval.
 */
class AccessAttemptController extends Controller
{
    /**
     * Simulates an access attempt for an employee using their internal ID.
     *
     * @param Request $request The HTTP request containing the internal_id
     * @return \Illuminate\Http\JsonResponse JSON response with access status and attempt details
     */
    public function simulateAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'internal_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $internal_id = $request->internal_id;
            $employee = Employee::where('internal_id', $internal_id)->first();
            $access_granted = $employee && $employee->has_room_911_access;
            $access_status = 'Access Denied';

            $attempt = AccessAttempt::create([
                'employee_id' => $employee ? $employee->id : null,
                'internal_id_attempted' => $internal_id,
                'access_granted' => $access_granted,
                'attempted_at' => now(),
            ]);

            if ($access_granted) {
                $access_status = 'Access Granted';
            }

            DB::commit();

            return response()->json([
                'status' => $access_status,
                'attempt' => $attempt
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error has occurred in the Access module' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieves the access attempt history for a specific employee.
     *
     * @param int $employee_id The ID of the employee
     * @param Request $request The HTTP request containing optional date filters
     * @return \Illuminate\Http\JsonResponse JSON response with access attempts
     */
    public function getAccessHistory($employee_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required_with:end_date|date_format:Y-m-d|before_or_equal:end_date',
            'end_date' => 'required_with:start_date|date_format:Y-m-d|after_or_equal:start_date',
        ], [
            'start_date.required_with' => 'The start_date field is required when end_date is present.',
            'end_date.required_with' => 'The end_date field is required when start_date is present.',
            'start_date.date_format' => 'The start_date must be in the format YYYY-MM-DD.',
            'end_date.date_format' => 'The end_date must be in the format YYYY-MM-DD.',
            'start_date.before_or_equal' => 'The start_date must be a date before or equal to end_date.',
            'end_date.after_or_equal' => 'The end_date must be a date after or equal to start_date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $employee = Employee::findOrFail($employee_id);
            $query = $employee->accessAttempts();

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay(); // 00:00:00
                $endDate = Carbon::parse($request->end_date)->endOfDay();       // 23:59:59
                $query->whereBetween('attempted_at', [$startDate, $endDate]);
            }

            $attempts = $query->get();
            return response()->json($attempts, Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while retrieving access history: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generates and returns a downloadable PDF of access history for an employee.
     *
     * @param int $employee_id The ID of the employee
     * @param Request $request The HTTP request containing optional date filters
     * @return \Illuminate\Http\JsonResponse JSON response with PDF download URL
     */
    public function downloadAccessHistoryPdf($employee_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required_with:end_date|date_format:Y-m-d|before_or_equal:end_date',
            'end_date' => 'required_with:start_date|date_format:Y-m-d|after_or_equal:start_date',
        ], [
            'start_date.required_with' => 'The start_date field is required when end_date is present.',
            'end_date.required_with' => 'The end_date field is required when start_date is present.',
            'start_date.date_format' => 'The start_date must be in the format YYYY-MM-DD.',
            'end_date.date_format' => 'The end_date must be in the format YYYY-MM-DD.',
            'start_date.before_or_equal' => 'The start_date must be a date before or equal to end_date.',
            'end_date.after_or_equal' => 'The end_date must be a date after or equal to start_date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $employee = Employee::findOrFail($employee_id);
            $query = $employee->accessAttempts();

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay(); // 00:00:00
                $endDate = Carbon::parse($request->end_date)->endOfDay();       // 23:59:59
                $query->whereBetween('attempted_at', [$startDate, $endDate]);
            }

            $attempts = $query->get();

            // Generate PDF content as dynamic HTML
            $html = $this->generatePdfContent($employee, $attempts);

            // Create PDF
            $pdf = Pdf::loadHTML($html);

            // Generate a unique name with UUID v4
            $fileName = 'access_history_' . Str::uuid() . '.pdf';
            $filePath = 'access_histories/' . $fileName;

            // Save the PDF in storage/app/public/access_histories
            Storage::disk('public')->put($filePath, $pdf->output());

            // Generate the public URL to download the file
            $downloadUrl = Storage::disk('public')->url($filePath);

            return response()->json([
                'message' => 'PDF generated successfully',
                'download_url' => $downloadUrl
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while generating the PDF: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generates HTML content for the access history PDF dynamically.
     *
     * @param Employee $employee The employee whose access history is being documented
     * @param \Illuminate\Support\Collection $attempts Collection of access attempts
     * @return string The generated HTML content
     */
    private function generatePdfContent(Employee $employee, $attempts): string
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Access History - {$employee->internal_id}</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h1>Access History for {$employee->first_name} {$employee->last_name} ({$employee->internal_id})</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Internal ID Attempted</th>
                        <th>Access Granted</th>
                        <th>Attempted At</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        if ($attempts->isEmpty()) {
            $html .= '<tr><td colspan="4">No access attempts found.</td></tr>';
        } else {
            foreach ($attempts as $attempt) {
                $accessGranted = $attempt->access_granted ? 'Yes' : 'No';
                $html .= <<<HTML
                <tr>
                    <td>{$attempt->id}</td>
                    <td>{$attempt->internal_id_attempted}</td>
                    <td>{$accessGranted}</td>
                    <td>{$attempt->attempted_at}</td>
                </tr>
                HTML;
            }
        }

        $html .= <<<HTML
                </tbody>
            </table>
        </body>
        </html>
        HTML;

        return $html;
    }
}
