<?php
class SimplePDF {
    private $content = '';
    private $filename = '';
    public function __construct() {
        $this->content = "%PDF-1.4\n";
    }
    public function generateTicketPDF($data) {
        $html = $this->createPrintableHTML($data);
        $filename = 'ticket_' . substr($data['id'], -8) . '.html';
        $filepath = __DIR__ . '/../temp/' . $filename;
        $tempDir = __DIR__ . '/../temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        file_put_contents($filepath, $html);
        return $filepath;
    }
    private function createPrintableHTML($data) {
        $ticketId = htmlspecialchars(substr($data['id'], -8));
        $passengerName = htmlspecialchars($data['passenger_name'] ?? 'N/A');
        $departure = htmlspecialchars($data['departure_city'] ?? 'N/A');
        $destination = htmlspecialchars($data['destination_city'] ?? 'N/A');
        $departureTime = isset($data['departure_time']) ? date('d.m.Y H:i', strtotime($data['departure_time'])) : 'N/A';
        $arrivalTime = isset($data['arrival_time']) ? date('d.m.Y H:i', strtotime($data['arrival_time'])) : 'N/A';
        $seatNumber = htmlspecialchars($data['seat_number'] ?? 'N/A');
        $price = number_format($data['final_price'] ?? $data['total_price'] ?? 0, 2);
        $company = htmlspecialchars($data['company_name'] ?? 'N/A');
        $purchaseDate = isset($data['created_at']) ? date('d.m.Y H:i', strtotime($data['created_at'])) : 'N/A';
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Otobüs Bileti - {$ticketId}</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
            @page { size: A4; margin: 0; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 3px solid #007bff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .ticket-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .ticket-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .ticket-header .ticket-id {
            font-size: 18px;
            opacity: 0.9;
            letter-spacing: 2px;
        }
        .route-section {
            padding: 40px;
            background: #f8f9fa;
        }
        .route-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .city-box {
            flex: 1;
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .city-box h2 {
            font-size: 28px;
            color: #007bff;
            margin-bottom: 10px;
        }
        .city-box .time {
            font-size: 16px;
            color: #666;
        }
        .arrow {
            flex: 0 0 80px;
            text-align: center;
            font-size: 40px;
            color: #007bff;
            font-weight: bold;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 40px;
        }
        .detail-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #007bff;
        }
        .detail-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .detail-box .value {
            font-size: 20px;
            color: #333;
            font-weight: bold;
        }
        .price-section {
            background: #28a745;
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
        }
        .footer-info {
            padding: 30px;
            background: #f8f9fa;
            border-top: 2px dashed #dee2e6;
        }
        .footer-info h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .footer-info ul {
            list-style: none;
            padding: 0;
        }
        .footer-info li {
            padding: 8px 0;
            color: #666;
            font-size: 14px;
        }
        .footer-info li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .print-date {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
        }
        .no-print {
            text-align: center;
            padding: 20px;
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ PDF Olarak Kaydet / Yazdır</button>
    </div>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>🚌 OTOBÜS BİLETİ</h1>
            <div class="ticket-id">Bilet No: {$ticketId}</div>
        </div>
        <div class="route-section">
            <div class="route-display">
                <div class="city-box">
                    <h2>{$departure}</h2>
                    <div class="time">📅 {$departureTime}</div>
                </div>
                <div class="arrow">→</div>
                <div class="city-box">
                    <h2>{$destination}</h2>
                    <div class="time">📅 {$arrivalTime}</div>
                </div>
            </div>
        </div>
        <div class="details-grid">
            <div class="detail-box">
                <div class="label">👤 Yolcu Adı</div>
                <div class="value">{$passengerName}</div>
            </div>
            <div class="detail-box">
                <div class="label">💺 Koltuk Numarası</div>
                <div class="value">{$seatNumber}</div>
            </div>
            <div class="detail-box">
                <div class="label">🏢 Otobüs Firması</div>
                <div class="value">{$company}</div>
            </div>
            <div class="detail-box">
                <div class="label">📅 Satın Alma Tarihi</div>
                <div class="value">{$purchaseDate}</div>
            </div>
        </div>
        <div class="price-section">
            💰 Ödenen Tutar: {$price} TL
        </div>
        <div class="footer-info">
            <h3>📋 Önemli Bilgiler</h3>
            <ul>
                <li>Lütfen kalkıştan 30 dakika önce terminalde bulununuz</li>
                <li>Bu bilet kişiye özeldir ve devredilemez</li>
                <li>Bilet iptali kalkıştan 1 saat öncesine kadar mümkündür</li>
                <li>Yolculuk sırasında kimlik ibraz etmeniz gerekebilir</li>
                <li>Bagaj limitleri için firma kurallarına uyunuz</li>
            </ul>
        </div>
        <div class="print-date">
            Yazdırma Tarihi: {$purchaseDate} | www.otobusbileti.com
        </div>
    </div>
    <script>
        window.onload = function() {
        };
    </script>
</body>
</html>
HTML;
    }
}
