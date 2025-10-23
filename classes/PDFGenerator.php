<?php
class PDFGenerator {
    public function generateTicketPDF($ticketData) {
        try {
            require_once __DIR__ . '/SimplePDF.php';
            $simplePDF = new SimplePDF();
            $filepath = $simplePDF->generateTicketPDF($ticketData);
            $filename = basename($filepath);
            return [
                'success' => true,
                'message' => 'Bilet PDF\'i başarıyla oluşturuldu.',
                'filepath' => $filepath,
                'filename' => $filename,
                'downloadUrl' => 'download_ticket.php?ticket=' . $ticketData['id']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'PDF oluşturulurken hata: ' . $e->getMessage()
            ];
        }
    }
    private function createTicketHTML($ticketData) {
        $qrData = $this->generateTicketQR($ticketData['id']);
        $html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otobüs Bileti - ' . htmlspecialchars($ticketData['id']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .ticket {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #007bff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .ticket-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .ticket-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .ticket-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .ticket-body {
            padding: 30px;
        }
        .route-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .location {
            text-align: center;
            flex: 1;
        }
        .location h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .location p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .arrow {
            flex: 0 0 60px;
            text-align: center;
            font-size: 24px;
            color: #007bff;
        }
        .ticket-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        .detail-item label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .detail-item span {
            font-size: 16px;
            color: #007bff;
            font-weight: 600;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            background: #ddd;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 12px;
            color: #666;
        }
        .ticket-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #666;
        }
        .price-highlight {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            body { background: white; }
            .ticket { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>🚌 OTOBÜS BİLETİ</h1>
            <p>Bilet No: ' . htmlspecialchars($ticketData['id']) . '</p>
        </div>
        <div class="ticket-body">
            <div class="route-info">
                <div class="location">
                    <h3>' . htmlspecialchars($ticketData['departure_city'] ?? 'N/A') . '</h3>
                    <p>' . (isset($ticketData['departure_time']) ? date('d.m.Y H:i', strtotime($ticketData['departure_time'])) : 'N/A') . '</p>
                </div>
                <div class="arrow">→</div>
                <div class="location">
                    <h3>' . htmlspecialchars($ticketData['destination_city'] ?? 'N/A') . '</h3>
                    <p>' . (isset($ticketData['arrival_time']) ? date('d.m.Y H:i', strtotime($ticketData['arrival_time'])) : 'N/A') . '</p>
                </div>
            </div>
            <div class="ticket-details">
                <div class="detail-item">
                    <label>Yolcu Adı</label>
                    <span>' . htmlspecialchars($ticketData['passenger_name'] ?? 'N/A') . '</span>
                </div>
                <div class="detail-item">
                    <label>Koltuk No</label>
                    <span>' . htmlspecialchars($ticketData['seat_number'] ?? 'N/A') . '</span>
                </div>
                <div class="detail-item">
                    <label>Satın Alma Tarihi</label>
                    <span>' . (isset($ticketData['created_at']) ? date('d.m.Y H:i', strtotime($ticketData['created_at'])) : 'N/A') . '</span>
                </div>
                <div class="detail-item">
                    <label>Firma</label>
                    <span>' . htmlspecialchars($ticketData['company_name'] ?? 'N/A') . '</span>
                </div>
                <div class="detail-item">
                    <label>Durum</label>
                    <span>' . $this->getStatusText($ticketData['status'] ?? 'unknown') . '</span>
                </div>
                <div class="detail-item">
                    <label>Bilet ID</label>
                    <span style="font-size: 12px;">' . htmlspecialchars(substr($ticketData['id'], 0, 20)) . '...</span>
                </div>
            </div>
            <div class="price-highlight">
                Ödenen Tutar: ' . number_format(($ticketData['final_price'] ?? $ticketData['total_price'] ?? 0), 2) . ' TL
            </div>
            <div class="qr-section">
                <div class="qr-code">
                    ' . $qrData . '
                </div>
                <p><strong>Doğrulama Kodu:</strong> ' . $this->generateVerificationCode($ticketData['id']) . '</p>
                <p><small>Bu kodu kontrol sırasında gösteriniz</small></p>
            </div>
        </div>
        <div class="ticket-footer">
            <p><strong>Önemli Bilgiler:</strong></p>
            <p>• Lütfen kalkıştan 30 dakika önce terminalde bulununuz</p>
            <p>• Bu bilet kişiye özeldir ve devredilemez</p>
            <p>• Bilet iptali kalkıştan 1 saat öncesine kadar mümkündür</p>
            <p>• Sorularınız için: info@otobusbileti.com</p>
            <br>
            <p>Yazdırma Tarihi: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
    <script>
    </script>
</body>
</html>';
        return $html;
    }
    private function generateTicketQR($ticketId) {
        return "QR: " . substr(md5($ticketId), 0, 8);
    }
    private function generateVerificationCode($ticketId) {
        return strtoupper(substr(md5($ticketId . 'verify'), 0, 6));
    }
    private function getStatusText($status) {
        switch ($status) {
            case 'active':
                return 'Aktif';
            case 'cancelled':
                return 'İptal Edildi';
            case 'completed':
                return 'Tamamlandı';
            default:
                return 'Bilinmiyor';
        }
    }
    public function cleanupTempFiles($olderThanHours = 24) {
        $tempDir = __DIR__ . '/../temp/';
        if (!is_dir($tempDir)) {
            return;
        }
        $files = glob($tempDir . 'ticket_*.html');
        $cutoffTime = time() - ($olderThanHours * 3600);
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}