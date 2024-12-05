<?php
namespace App\Services;

use App\Adapters\AFIPAdapter;
use Illuminate\Support\Facades\View;

class AfipService {

    protected $AFIPAdapter;

    public function __construct(AFIPAdapter $aFIPAdapter)
    {  
        $this->AFIPAdapter = $aFIPAdapter;
    }


    public function handleInvoice(array $data){
        $lastVaucher = $this->AFIPAdapter->getLastVoucher((int)env("FACTURA_C_PUNTO_DE_VENTA"),(int)env("FACTURA_C_TIPO_DE_COMPROBANTE"));
        
        $data["numero_de_factura"] = $lastVaucher + 1;
        $payLoad = $this->payload_voucher($data);
        $vaucher = $this->AFIPAdapter->createVoucher($payLoad);
        $data["CAE"] = $vaucher["CAE"];
        $data["CAEFchVto"] = $vaucher["CAEFchVto"];
        $data["QR"] = $this->generateQrCode($data);
        
        $html = View::make('bill', $data)->render();
        $pdf = $this->AFIPAdapter->createPDF($html,$data["numero_de_factura"]);

        return $pdf;

    }

    public function payload_voucher($data){
        $concepto = (int)env("FACTURA_C_CONCEPTO");
        return array(
            'CantReg' 	=> 1, // Cantidad de facturas a registrar
            'PtoVta' 	=> (int)env("FACTURA_C_PUNTO_DE_VENTA"),
            'CbteTipo' 	=> (int)env("FACTURA_C_TIPO_DE_COMPROBANTE"), 
            'Concepto' 	=> $concepto,
            'DocTipo' 	=> (int)env("FACTURA_C_TIPO_DE_DOCUMENTO"),
            'DocNro' 	=> (int)env("FACTURA_C_NUMERO_DE_DOCUMENTO"),
            'CbteDesde' => $data["numero_de_factura"],
            'CbteHasta' => $data["numero_de_factura"],
            'CbteFch' 	=> (int)date("Ymd"),
            'FchServDesde'  => ($concepto == 2 || $concepto == 3) ? $data["fecha_servicio_desde"] : null,
            'FchServHasta'  => ($concepto == 2 || $concepto == 3) ? $data["fecha_servicio_hasta"] : null, 
            'FchVtoPago'    => ($concepto == 2 || $concepto == 3) ? $data["fecha_vencimiento_pago"] : null, 
            'ImpTotal' 	=> $data["importeTotal"],
            'ImpTotConc'=> 0, // Importe neto no gravado
            'ImpNeto' 	=> $data["importeTotal"], // Importe neto
            'ImpOpEx' 	=> 0, // Importe exento al IVA
            'ImpIVA' 	=> 0, // Importe de IVA
            'ImpTrib' 	=> 0, //Importe total de tributos
            'MonId' 	=> 'PES', //Tipo de moneda usada en la factura ('PES' = pesos argentinos) 
            'MonCotiz' 	=> 1, // Cotización de la moneda usada (1 para pesos argentinos)  
        );
    }
    protected function generateQrCode($data)
    {
        $qrData = [
            "ver" => 1,
            "fecha" => date('Y-m-d'),
            "cuit" => env('AFIP_CUIT'),
            "ptoVta" => env('FACTURA_C_PUNTO_DE_VENTA'),
            "tipoCmp" => env('FACTURA_C_TIPO_DE_COMPROBANTE'),
            "nroCmp" => $data["numero_de_factura"],
            "importe" => $data['importeTotal'],
            "moneda" => "PES",
            "ctz" => 1,
            "tipoDocRec" => 99,
            "nroDocRec" => 0,
            "tipoCodAut" => 'E',
            "codAut" => $data['CAE'],
        ];

        $jsonBase64 = base64_encode(json_encode($qrData));
        $qrUrl = "https://www.afip.gob.ar/fe/qr/?p=" . $jsonBase64;

        // Generar el QR usando la librería
        return (new \chillerlan\QRCode\QRCode())->render($qrUrl);
    }
} 

?>