<?php

// routes/api.php

use App\Http\Controllers\BillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Smart Bill Splitter
|--------------------------------------------------------------------------
|
| Semua route yang didaftarkan di sini otomatis mendapatkan prefix "/api/".
| Jadi Route::get('/bills', ...) bisa diakses via:
|   → GET http://localhost:8000/api/bills
|
| Middleware 'api' (throttle, JSON parsing) sudah otomatis terpasang
| untuk semua route di file ini oleh Laravel 11.
|
*/

Route::get('/bills', [BillController::class, 'index']);
Route::post('/bills', [BillController::class, 'store']);