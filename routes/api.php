<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BinSensorController;

Route::prefix('bins')->group(function () {
    // Update single compartment kapasitas
    Route::post('/{device_id}/update', [BinSensorController::class, 'updateCompartment']);

    // Get bin status
    Route::get('/{device_id}/status', [BinSensorController::class, 'getStatus']);

    // Batch update multiple compartments
    Route::post('/{device_id}/batch-update', [BinSensorController::class, 'batchUpdate']);

    Route::post('/{device_id}/detection', [BinSensorController::class, 'logDetection']);
});
