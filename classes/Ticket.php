<?php
require_once __DIR__ . '/../config/database.php';
class Ticket {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function purchaseMultipleTickets($tripId, $userId, $seatNumbers, $couponCode = null) {
        try {
            $this->db->beginTransaction();
            // Get trip details
            $trip = $this->db->fetch(
                "SELECT * FROM Trips WHERE id = ?", 
                [$tripId]
            );
            if (!$trip) {
                throw new Exception('Sefer bulunamadı.');
            }
            // Validate seat availability
            foreach ($seatNumbers as $seatNumber) {
                if (!$this->isSeatAvailable($tripId, $seatNumber)) {
                    throw new Exception("Koltuk {$seatNumber} müsait değil.");
                }
                // TODO: Add seat number validation against bus layout
                if ($seatNumber < 1 || $seatNumber > $trip['capacity']) {
                    throw new Exception("Geçersiz koltuk numarası: {$seatNumber}");
                }
            }
            // Check user balance
            $user = $this->db->fetch("SELECT id, full_name, email, role, balance FROM User WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            if ($user['role'] === 'company') {
                throw new Exception('İşletmeler bilet alamaz.');
            }
            $totalPrice = count($seatNumbers) * $trip['price'];
            if ($couponCode) {
                $couponResult = $this->applyCoupon($couponCode, $trip['company_id'], $totalPrice);
                if ($couponResult['success']) {
                    $totalPrice = $couponResult['finalPrice'];
                }
            }
            if ($user['balance'] < $totalPrice) {
                throw new Exception('Yetersiz bakiye. Mevcut bakiye: ' . number_format($user['balance'], 2) . ' TL, Gerekli tutar: ' . number_format($totalPrice, 2) . ' TL');
            }
            $ticketIds = [];
            $pricePerTicket = $totalPrice / count($seatNumbers);
            foreach ($seatNumbers as $seatNumber) {
                $ticketId = $this->createTicketRecord($tripId, $userId, $seatNumber, $pricePerTicket);
                $ticketIds[] = $ticketId;
            }
            $newBalance = $user['balance'] - $totalPrice;
            error_log("Updating balance: Old={$user['balance']}, New=$newBalance, UserID=$userId, TotalPrice=$totalPrice");
            $this->db->execute(
                "UPDATE User SET balance = ? WHERE id = ?", 
                [$newBalance, $userId]
            );
            error_log("Balance updated successfully");
            $this->db->commit();
            return [
                'success' => true,
                'message' => count($seatNumbers) . ' adet bilet başarıyla satın alındı.',
                'ticketIds' => $ticketIds,
                'totalPrice' => $totalPrice,
                'newBalance' => $newBalance,
                'seatCount' => count($seatNumbers)
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    public function purchaseTicket($tripId, $userId, $seatNumber, $couponCode = null) {
        try {
            $this->db->beginTransaction();
            $trip = $this->db->fetch(
                "SELECT * FROM Trips WHERE id = ?", 
                [$tripId]
            );
            if (!$trip) {
                throw new Exception('Sefer bulunamadı.');
            }
            if (!$this->isSeatAvailable($tripId, $seatNumber)) {
                throw new Exception('Seçilen koltuk müsait değil.');
            }
            if ($seatNumber < 1 || $seatNumber > $trip['capacity']) {
                throw new Exception('Geçersiz koltuk numarası.');
            }
            $user = $this->db->fetch("SELECT id, full_name, email, role, balance FROM User WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            if ($user['role'] === 'company') {
                throw new Exception('İşletmeler bilet alamaz.');
            }
            $totalPrice = $trip['price'];
            if ($user['balance'] < $totalPrice) {
                throw new Exception('Yetersiz bakiye. Mevcut bakiye: ' . number_format($user['balance'], 2) . ' TL, Gerekli tutar: ' . number_format($totalPrice, 2) . ' TL');
            }
            $ticketId = $this->createTicketRecord($tripId, $userId, $seatNumber, $totalPrice);
            $newBalance = $user['balance'] - $totalPrice;
            $this->db->execute(
                "UPDATE User SET balance = ? WHERE id = ?", 
                [$newBalance, $userId]
            );
            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Bilet başarıyla satın alındı.',
                'ticketId' => $ticketId,
                'totalPrice' => $totalPrice,
                'newBalance' => $newBalance
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    public function isSeatAvailable($tripId, $seatNumber) {
        $trip = $this->db->fetch("SELECT capacity FROM Trips WHERE id = ?", [$tripId]);
        if (!$trip) {
            return false;
        }
        if ($seatNumber < 1 || $seatNumber > $trip['capacity']) {
            return false;
        }
        $seatTaken = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM Booked_Seats bs 
             JOIN Tickets t ON bs.ticket_id = t.id 
             WHERE t.trip_id = ? AND bs.seat_number = ?", 
            [$tripId, $seatNumber]
        );
        return ($seatTaken['count'] ?? 0) == 0;
    }
    public function getAvailableSeats($tripId) {
        $trip = $this->db->fetch("SELECT capacity FROM Trips WHERE id = ?", [$tripId]);
        if (!$trip) {
            return [];
        }
        $bookedSeats = $this->db->fetchAll(
            "SELECT bs.seat_number 
             FROM Booked_Seats bs 
             JOIN Tickets t ON bs.ticket_id = t.id 
             WHERE t.trip_id = ?", 
            [$tripId]
        );
        $bookedSeatNumbers = array_column($bookedSeats, 'seat_number');
        $availableSeats = [];
        for ($i = 1; $i <= $trip['capacity']; $i++) {
            if (!in_array($i, $bookedSeatNumbers)) {
                $availableSeats[] = $i;
            }
        }
        return $availableSeats;
    }
    public function getUserTickets($userId, $limit = null, $offset = 0) {
        $sql = "SELECT t.*, tr.departure_city, tr.destination_city, 
                       tr.departure_time, tr.arrival_time, tr.price as trip_price,
                       t.total_price as final_price,
                       bc.name as company_name, bs.seat_number
                FROM Tickets t
                JOIN Trips tr ON t.trip_id = tr.id
                LEFT JOIN Bus_Company bc ON tr.company_id = bc.id
                LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC";
        $params = [$userId];
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        return $this->db->fetchAll($sql, $params);
    }
    public function getUserTicketCount($userId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM Tickets WHERE user_id = ?", 
            [$userId]
        );
        return $result ? $result['count'] : 0;
    }
    public function getTicketById($ticketId, $userId = null) {
        $sql = "SELECT t.*, tr.departure_city, tr.destination_city, 
                       tr.departure_time, tr.arrival_time, tr.price as trip_price,
                       u.full_name as passenger_name, u.email as passenger_email,
                       bs.seat_number,
                       t.total_price as final_price,
                       bc.name as company_name
                FROM Tickets t
                JOIN Trips tr ON t.trip_id = tr.id
                JOIN User u ON t.user_id = u.id
                LEFT JOIN Booked_Seats bs ON t.id = bs.ticket_id
                LEFT JOIN Bus_Company bc ON tr.company_id = bc.id
                WHERE t.id = ?";
        $params = [$ticketId];
        if ($userId !== null) {
            $sql .= " AND t.user_id = ?";
            $params[] = $userId;
        }
        return $this->db->fetch($sql, $params);
    }
    public function cancelTicket($ticketId, $userId) {
        try {
            $this->db->beginTransaction();
            $ticket = $this->getTicketById($ticketId, $userId);
            if (!$ticket) {
                throw new Exception('Bilet bulunamadı veya size ait değil.');
            }
            if (!$this->canCancelTicket($ticketId)) {
                throw new Exception('Bilet iptal edilemez. Kalkış saatine 1 saatten az kaldı.');
            }
            $user = $this->db->fetch("SELECT balance FROM User WHERE id = ?", [$userId]);
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            $refundAmount = $ticket['total_price'];
            $newBalance = $user['balance'] + $refundAmount;
            $this->db->execute(
                "DELETE FROM Booked_Seats WHERE ticket_id = ?", 
                [$ticketId]
            );
            $this->db->execute(
                "DELETE FROM Tickets WHERE id = ?", 
                [$ticketId]
            );
            $this->db->execute(
                "UPDATE User SET balance = ? WHERE id = ?", 
                [$newBalance, $userId]
            );
            $this->db->commit();
            return [
                'success' => true,
                'message' => 'Bilet başarıyla iptal edildi. ' . number_format($refundAmount, 2) . ' TL bakiyenize eklendi.',
                'refundAmount' => $refundAmount,
                'newBalance' => $newBalance
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    public function canCancelTicket($ticketId) {
        $ticket = $this->db->fetch(
            "SELECT tr.departure_time FROM tickets t
             JOIN trips tr ON t.trip_id = tr.id
             WHERE t.id = ?", 
            [$ticketId]
        );
        if (!$ticket) {
            return false;
        }
        $departureTime = new DateTime($ticket['departure_time']);
        $currentTime = new DateTime();
        $timeDifference = $departureTime->getTimestamp() - $currentTime->getTimestamp();
        return $timeDifference > 3600;
    }
    private function createTicketRecord($tripId, $userId, $seatNumber, $totalPrice) {
        $ticketId = uniqid('ticket_', true);
        try {
            $this->db->execute(
                "INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) 
                 VALUES (?, ?, ?, 'active', ?, datetime('now'))",
                [$ticketId, $tripId, $userId, $totalPrice]
            );
            error_log("Ticket inserted: $ticketId");
            $this->db->execute(
                "INSERT INTO Booked_Seats (ticket_id, seat_number, created_at) 
                 VALUES (?, ?, datetime('now'))",
                [$ticketId, $seatNumber]
            );
            error_log("Seat booked: $seatNumber for ticket $ticketId");
        } catch (Exception $e) {
            error_log("createTicketRecord error: " . $e->getMessage());
            throw $e;
        }
        return $ticketId;
    }
    private function applyCoupon($couponCode, $companyId, $originalPrice) {
        $existingCoupon = $this->db->fetch(
            "SELECT * FROM Coupons WHERE code = ?", 
            [$couponCode]
        );
        if (!$existingCoupon) {
            return [
                'success' => false,
                'message' => 'Geçersiz kupon kodu.'
            ];
        }
        if ($existingCoupon['expire_date'] && strtotime($existingCoupon['expire_date']) <= time()) {
            return [
                'success' => false,
                'message' => 'Bu kuponun süresi dolmuş.'
            ];
        }
        if ($existingCoupon['usage_limit'] <= 0) {
            return [
                'success' => false,
                'message' => 'Bu kuponun kullanım limiti dolmuş.'
            ];
        }
        if ($existingCoupon['company_id'] !== null && $existingCoupon['company_id'] != $companyId) {
            return [
                'success' => false,
                'message' => 'Bu kupon sadece belirli bir firma için geçerlidir ve bu sefer için kullanılamaz.'
            ];
        }
        $coupon = $existingCoupon;
        $discountRate = $coupon['discount'] ?? 0;
        $discountAmount = ($originalPrice * $discountRate) / 100;
        $finalPrice = $originalPrice - $discountAmount;
        $this->db->execute(
            "UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?", 
            [$coupon['id']]
        );
        return [
            'success' => true,
            'discountAmount' => $discountAmount,
            'finalPrice' => $finalPrice,
            'discountRate' => $discountRate
        ];
    }
    public function generatePDF($ticketId, $userId = null) {
        require_once __DIR__ . '/PDFGenerator.php';
        $ticketData = $this->getTicketById($ticketId, $userId);
        if (!$ticketData) {
            return [
                'success' => false,
                'message' => 'Bilet bulunamadı veya size ait değil.'
            ];
        }
        $pdfGenerator = new PDFGenerator();
        return $pdfGenerator->generateTicketPDF($ticketData);
    }
    public function getTripStatistics($tripId) {
        $trip = $this->db->fetch("SELECT capacity, price FROM Trips WHERE id = ?", [$tripId]);
        if (!$trip) {
            return null;
        }
        $soldTickets = $this->db->fetchAll("SELECT * FROM Tickets WHERE trip_id = ?", [$tripId]);
        $soldSeats = count($soldTickets);
        $availableSeats = $trip['capacity'] - $soldSeats;
        $revenue = array_sum(array_column($soldTickets, 'total_price'));
        return [
            'totalSeats' => $trip['capacity'],
            'soldSeats' => $soldSeats,
            'availableSeats' => $availableSeats,
            'occupancyRate' => ($soldSeats / $trip['capacity']) * 100,
            'revenue' => $revenue
        ];
    }
}
