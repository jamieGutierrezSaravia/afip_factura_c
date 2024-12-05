<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AfipController;

Route::get("/hola_mundo", function(Request $request){
    return response()->json([
        "message" => "hola mundo"
    ]);
});

Route::post("/invoice", [AfipController::class, "invoice"]);