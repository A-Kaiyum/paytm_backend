<?php

use App\Http\Controllers\PaytmController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('paytm-payment', [PaytmController::class, 'paymentNow']);
Route::post('paytm-callback', [PaytmController::class, 'paytmCallback']);
Route::get('paytm-status/{orderId}', [PaytmController::class, 'getPaytmStatus']);
