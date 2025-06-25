<?php
require_once('tcpdf/tcpdf.php');

function generateInvoice($bookingData) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('MaramCAR');
    $pdf->SetAuthor('MaramCAR');
    $pdf->SetTitle('فاتورة الحجز #' . $bookingData['booking_id']);
    
    $pdf->AddPage();
    
    // محتوى الفاتورة (نفس التصميم الذي ورد في الصورة)
    $html = '
    <style>
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; }
        .info { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
    
    <div class="header">
        <h2>MARAM CAR</h2>
        <p>LOCATION DE VOITURE MARAM CAR</p>
        <p>BAB EZZOUAR - CITE 1577 BATIMENT N°28/CO4</p>
        <p>0556 58 88 65 / 0775 32 42 30 | maramcar22@gmail.com</p>
    </div>
    
    <div class="title">CONTRAT DE LOCATION N°: '.$bookingData['booking_id'].'</div>
    
    <div class="info">
        <h3>Renseignement sur le conducteur :</h3>
        <p>Nom et prénom: <strong>'.$bookingData['full_name'].'</strong></p>
        <p>Tél: '.$bookingData['phone'].'</p>
    </div>
    
    <h3>Informations sur le véhicule :</h3>
    <table>
        <tr><th>Modèle / Type</th><td>'.$bookingData['car_model'].'</td></tr>
        <tr><th>Date départ</th><td>'.$bookingData['pickup_date'].'</td></tr>
        <tr><th>Date retour</th><td>'.$bookingData['return_date'].'</td></tr>
        <tr><th>Nbr jours</th><td>'.$bookingData['days'].'</td></tr>
    </table>
    
    <p style="text-align: right; font-weight: bold;">
        Total: '.number_format($bookingData['total_price'], 2).' DZD
    </p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $filename = 'invoices/invoice_'.$bookingData['booking_id'].'.pdf';
    $pdf->Output(__DIR__.'/'.$filename, 'F');
    
    return $filename;
}
?>