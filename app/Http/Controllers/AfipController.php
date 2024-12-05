<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AfipService;

class AfipController extends Controller
{
    protected $afipService;
    
    public function __construct(AfipService $afipService)
    {
        $this->afipService = $afipService;
    }
    
    public function invoice(Request $request){
        
        $response= $this->afipService->handleInvoice($request->all());
        return response()->json($response);
    }
}
