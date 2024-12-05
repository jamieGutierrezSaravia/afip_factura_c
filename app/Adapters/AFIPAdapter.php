<?php

namespace App\Adapters;

use Afip;
use Exception;
use Illuminate\Support\Facades\Log;

class AFIPAdapter
{
    protected $afip;

    public function __construct()
    {
        try { 
            $cuit       = env('AFIP_CUIT');
            if(env('AFIP_PRODUCTION')){
                $certPath   = storage_path('app/certificates/'.env('AFIP_CERT_NAME').'.crt');
                $keyPath    = storage_path('app/certificates/'.env('AFIP_CERT_NAME').'.key');
                $this->afip = new Afip([
                    'CUIT'          => $cuit,
                    'cert'          => file_get_contents($certPath),
                    'key'           => file_get_contents($keyPath),
                    'production'    => env('AFIP_PRODUCTION'),
                    'access_token'  => env('AFIP_PRODUCTION') ? env('AFIP_ACCESS_TOKEN') : ''
                ]);
            } else {
                $this->afip = new Afip(array('CUIT' => $cuit));

            }
            
        } catch (Exception $e) {
            Log::error("Error al inicializar el adaptador de AFIP: " . $e->getMessage());
            throw new Exception("Error al inicializar el adaptador de AFIP.");
        }
    }

    public function getLastVoucher(int $ptoVta, int $cbteTipo)
    {
        return $this->afip->ElectronicBilling->GetLastVoucher($ptoVta, $cbteTipo);
    }

    public function createVoucher(array $data)
    {
        try {
            return $this->afip->ElectronicBilling->CreateVoucher($data);
        } catch (Exception $e) {
            Log::error("Error al crear el comprobante en AFIP: " . $e->getMessage());
            throw new Exception("Error al crear el comprobante en AFIP.");
        }
    }

    public function createPDF(string $html, int $voucherNumber)
    {
        $options = [
            "width" => 8,
            "marginLeft" => 0.4,
            "marginRight" => 0.4,
            "marginTop" => 0.4,
            "marginBottom" => 0.4
        ];

        return $this->afip->ElectronicBilling->CreatePDF([
            "html" => $html,
            "file_name" => 'Factura_' . $voucherNumber,
            "options" => $options
        ]);
    }
}