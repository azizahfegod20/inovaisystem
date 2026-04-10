<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CnpjLookupController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MunicipalParamsController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]));

/*
|--------------------------------------------------------------------------
| Auth Routes (públicas)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Routes autenticadas (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // CNPJ Lookup
    Route::get('/cnpj-lookup/{cnpj}', CnpjLookupController::class);

    // Municipal Params
    Route::get('/municipal-params/{codigoIbge}', MunicipalParamsController::class);

    // Companies
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{company}', [CompanyController::class, 'show']);
    Route::put('/companies/{company}', [CompanyController::class, 'update']);
    Route::post('/companies/{company}/select', [CompanyController::class, 'select']);

    // Company Members (admin only para gestão)
    Route::get('/companies/{company}/members', [CompanyController::class, 'members']);
    Route::middleware('company.role:admin')->group(function () {
        Route::post('/companies/{company}/members', [CompanyController::class, 'addMember']);
        Route::put('/companies/{company}/members/{userId}', [CompanyController::class, 'updateMemberRole']);
        Route::delete('/companies/{company}/members/{userId}', [CompanyController::class, 'removeMember']);
    });

    /*
    |--------------------------------------------------------------------------
    | Routes com empresa selecionada (middleware company)
    |--------------------------------------------------------------------------
    */

    Route::middleware('company')->group(function () {
        // Certificates (leitura: todos; escrita: admin)
        Route::get('/certificates', [CertificateController::class, 'index']);
        Route::middleware('company.role:admin')->group(function () {
            Route::post('/certificates', [CertificateController::class, 'store']);
            Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy']);
        });

        // Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

        // Services
        Route::get('/services', [ServiceController::class, 'index']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        // Invoices (leitura: todos; emissão/cancel/replace: admin, contador)
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
        Route::get('/invoices/{invoice}/xml', [InvoiceController::class, 'xml']);
        Route::middleware('company.role:admin,contador')->group(function () {
            Route::post('/invoices', [InvoiceController::class, 'store']);
            Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
            Route::post('/invoices/{invoice}/replace', [InvoiceController::class, 'replace']);
        });

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
    });
});
