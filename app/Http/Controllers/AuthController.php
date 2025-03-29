<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Handles user authentication and authorization operations.
 */
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        try {
            $credentials = request(['email', 'password']);

            if (! $token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }

            return $this->respondWithToken($token);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred during login: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register a new user and return a JSON response with user details.
     *
     * This method validates incoming request data, creates a new user with hashed password,
     * and returns a success response with the created user information.
     *
     * @param Request $request The HTTP request containing user registration data
     * @return \Illuminate\Http\JsonResponse Response containing success message or validation errors
     */
    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'rol' => 'required|string|max:14',
        ]);

        if ($validate->fails()) {
            return response()->json(['error' => $validate->messages()], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'rol' => $request->input('rol'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Successfully Saved',
                'user' => $user
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred during registration: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {
            return response()->json(auth()->user());
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while retrieving user data: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            auth()->logout();

            return response()->json(['message' => 'Successfully logged out']);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred during logout: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try{
            return $this->respondWithToken(auth()->refresh());
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while refreshing token: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        try {
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' - ' . $e->getFile() . ' - ' . $e->getLine());
            return response()->json([
                'error' => 'An error occurred while preparing token response: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
