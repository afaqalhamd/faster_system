<?php

use Illuminate\Support\Facades\Route;

Route::get('/sale-orders/{id}/waybill-print', [App\Http\Controllers\Sale\SaleOrderController::class, 'printWaybill'])
    ->name('sale.order.waybill.print')
    ->middleware(['auth']);
