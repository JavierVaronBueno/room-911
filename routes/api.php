<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('v1')->middleware('api')->group(function(){
    Route::prefix('auth')->group(function(){
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('register', [AuthController::class, 'register']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});


Route::prefix('v1')->middleware(['api', 'jwt.verify'])->group(function(){
    Route::prefix('employees')->group(function(){
        Route::post('/', [EmployeeController::class, 'store']);
        Route::post('/bulk', [EmployeeController::class, 'bulkUpload']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::get('/search', [EmployeeController::class, 'search']);
    });

    Route::prefix('production-departments')->group(function(){

    });

    Route::prefix('access-attempts')->group(function(){

    });
});
