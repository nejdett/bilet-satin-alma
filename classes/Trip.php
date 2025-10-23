<?php
require_once __DIR__ . '/../config/database.php';
class Trip {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    // Search trips with filters
    // FIXME: Add caching for popular routes
    public function searchTrips($departureLocation, $arrivalLocation, $date = null) {
        try {
            // Build dynamic query
            $sql = "SELECT t.*, c.name as company_name 
                    FROM Trips t 
                    LEFT JOIN Bus_Company c ON t.company_id = c.id 
                    WHERE 1=1";
            $params = [];
            if (!empty($departureLocation)) {
                $sql .= " AND (LOWER(t.departure_city) = LOWER(?) OR LOWER(t.departure_city) = LOWER(?))";
                $params[] = $departureLocation;
                $normalizedDeparture = str_replace(['İ', 'I'], ['i', 'ı'], $departureLocation);
                $params[] = $normalizedDeparture;
            }
            if (!empty($arrivalLocation)) {
                $sql .= " AND (LOWER(t.destination_city) = LOWER(?) OR LOWER(t.destination_city) = LOWER(?))";
                $params[] = $arrivalLocation;
                $normalizedArrival = str_replace(['İ', 'I'], ['i', 'ı'], $arrivalLocation);
                $params[] = $normalizedArrival;
            }
            if (!empty($date)) {
                $sql .= " AND DATE(t.departure_time) = ?";
                $params[] = $date;
            }
            $sql .= " AND t.departure_time > datetime('now')";
            $sql .= " ORDER BY t.departure_time ASC";
            $cacheTTL = 300;
            $trips = $this->db->fetchAll($sql, $params, $cacheTTL);
            foreach ($trips as &$trip) {
                $availableSeatsArray = $this->getAvailableSeatsArray($trip['id']);
                $trip['available_seats'] = is_array($availableSeatsArray) ? count($availableSeatsArray) : 0;
                $trip['available_seats_array'] = $availableSeatsArray; // Keep array for other uses
            }
            return $trips;
        } catch (Exception $e) {
            error_log('Trip search error: ' . $e->getMessage());
            return [];
        }
    }
    public function getTripById($tripId) {
        try {
            $sql = "SELECT t.*, c.name as company_name 
                    FROM Trips t 
                    LEFT JOIN Bus_Company c ON t.company_id = c.id 
                    WHERE t.id = ?";
            $trip = $this->db->fetch($sql, [$tripId]);
            if ($trip) {
                $availableSeatsArray = $this->getAvailableSeats($tripId);
                $trip['available_seats'] = is_array($availableSeatsArray) ? count($availableSeatsArray) : 0;
                $trip['available_seats_array'] = $availableSeatsArray; // Keep array for other uses
            }
            return $trip;
        } catch (Exception $e) {
            error_log('Get trip by ID error: ' . $e->getMessage());
            return false;
        }
    }
    public function getAvailableSeats($tripId) {
        try {
            $tripSql = "SELECT capacity FROM Trips WHERE id = ?";
            $trip = $this->db->fetch($tripSql, [$tripId]);
            if (!$trip) {
                return [];
            }
            $bookedSql = "SELECT bs.seat_number FROM Booked_Seats bs 
                         INNER JOIN Tickets t ON bs.ticket_id = t.id 
                         WHERE t.trip_id = ?";
            $bookedSeats = $this->db->fetchAll($bookedSql, [$tripId]);
            $bookedNumbers = array_column($bookedSeats, 'seat_number');
            $capacity = (int)$trip['capacity'];
            $availableSeats = [];
            for ($i = 1; $i <= $capacity; $i++) {
                if (!in_array($i, $bookedNumbers)) {
                    $availableSeats[] = $i;
                }
            }
            return $availableSeats;
        } catch (Exception $e) {
            error_log('Get available seats error: ' . $e->getMessage());
            return [];
        }
    }
    public function getAvailableSeatsArray($tripId) {
        return $this->getAvailableSeats($tripId);
    }
    public function hasAvailableSeats($tripId, $requestedSeats = 1) {
        return $this->getAvailableSeats($tripId) >= $requestedSeats;
    }
    public function createTrip($companyId, $departureLocation, $arrivalLocation, $departureTime, $arrivalTime, $price, $seatCount) {
        try {
            $sql = "INSERT INTO trips (company_id, departure_location, arrival_location, departure_time, arrival_time, price, seat_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $companyId,
                $departureLocation,
                $arrivalLocation,
                $departureTime,
                $arrivalTime,
                $price,
                $seatCount
            ];
            $this->db->query($sql, $params);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            error_log('Create trip error: ' . $e->getMessage());
            return false;
        }
    }
    public function updateTrip($tripId, $data) {
        try {
            $existingTrip = $this->getTripById($tripId);
            if (!$existingTrip) {
                return false;
            }
            $updateFields = [];
            $params = [];
            $allowedFields = ['departure_location', 'arrival_location', 'departure_time', 'arrival_time', 'price', 'seat_count'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($updateFields)) {
                return false;
            }
            $params[] = $tripId;
            $sql = "UPDATE trips SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log('Update trip error: ' . $e->getMessage());
            return false;
        }
    }
    public function deleteTrip($tripId) {
        try {
            $trip = $this->getTripById($tripId);
            if (!$trip) {
                return false;
            }
            $sql = "DELETE FROM trips WHERE id = ?";
            $this->db->query($sql, [$tripId]);
            return true;
        } catch (Exception $e) {
            error_log('Delete trip error: ' . $e->getMessage());
            return false;
        }
    }
    public function getCompanyTrips($companyId, $futureOnly = true) {
        try {
            $sql = "SELECT t.*, c.name as company_name 
                    FROM trips t 
                    LEFT JOIN companies c ON t.company_id = c.id 
                    WHERE t.company_id = ?";
            $params = [$companyId];
            if ($futureOnly) {
                $sql .= " AND t.departure_time > datetime('now')";
            }
            $sql .= " ORDER BY t.departure_time ASC";
            $trips = $this->db->fetchAll($sql, $params);
            foreach ($trips as &$trip) {
                $availableSeatsArray = $this->getAvailableSeatsArray($trip['id']);
                $trip['available_seats'] = is_array($availableSeatsArray) ? count($availableSeatsArray) : 0;
                $trip['available_seats_array'] = $availableSeatsArray; // Keep array for other uses
            }
            return $trips;
        } catch (Exception $e) {
            error_log('Get trips by company error: ' . $e->getMessage());
            return [];
        }
    }
    public function getDepartureCities() {
        try {
            $sql = "SELECT DISTINCT departure_city 
                    FROM Trips 
                    WHERE departure_city IS NOT NULL AND departure_city != '' 
                    ORDER BY departure_city ASC";
            $result = $this->db->fetchAll($sql, [], );
            $cities = array_column($result, 'departure_city');
            $normalizedCities = [];
            $seenCities = [];
            foreach ($cities as $city) {
                $normalizedCity = $this->normalizeTurkishText($city);
                if (!in_array($normalizedCity, $seenCities)) {
                    $normalizedCities[] = $city;
                    $seenCities[] = $normalizedCity;
                }
            }
            sort($normalizedCities, SORT_LOCALE_STRING);
            return $normalizedCities;
        } catch (Exception $e) {
            error_log('Get departure cities error: ' . $e->getMessage());
            return [];
        }
    }
    public function getDestinationCities() {
        try {
            $sql = "SELECT DISTINCT destination_city 
                    FROM Trips 
                    WHERE destination_city IS NOT NULL AND destination_city != '' 
                    ORDER BY destination_city ASC";
            $result = $this->db->fetchAll($sql, [], );
            $cities = array_column($result, 'destination_city');
            $normalizedCities = [];
            $seenCities = [];
            foreach ($cities as $city) {
                $normalizedCity = $this->normalizeTurkishText($city);
                if (!in_array($normalizedCity, $seenCities)) {
                    $normalizedCities[] = $city;
                    $seenCities[] = $normalizedCity;
                }
            }
            sort($normalizedCities, SORT_LOCALE_STRING);
            return $normalizedCities;
        } catch (Exception $e) {
            error_log('Get destination cities error: ' . $e->getMessage());
            return [];
        }
    }
    public function getPopularRoutes($limit = 5) {
        try {
            $sql = "SELECT departure_city, destination_city, COUNT(*) as trip_count
                    FROM Trips 
                    WHERE departure_city IS NOT NULL AND departure_city != '' 
                    AND destination_city IS NOT NULL AND destination_city != ''
                    AND departure_city != destination_city
                    GROUP BY departure_city, destination_city 
                    ORDER BY trip_count DESC, departure_city ASC";
            $rawResults = $this->db->fetchAll($sql, [], 7200);
            $normalizedRoutes = [];
            foreach ($rawResults as $route) {
                $normalizedDeparture = $this->normalizeTurkishText($route['departure_city']);
                $normalizedDestination = $this->normalizeTurkishText($route['destination_city']);
                $routeKey = $normalizedDeparture . '→' . $normalizedDestination;
                if (isset($normalizedRoutes[$routeKey])) {
                    $normalizedRoutes[$routeKey]['trip_count'] += $route['trip_count'];
                } else {
                    $normalizedRoutes[$routeKey] = [
                        'departure_city' => $route['departure_city'],
                        'destination_city' => $route['destination_city'],
                        'trip_count' => $route['trip_count']
                    ];
                }
            }
            uasort($normalizedRoutes, function($a, $b) {
                return $b['trip_count'] - $a['trip_count'];
            });
            return array_slice(array_values($normalizedRoutes), 0, $limit);
        } catch (Exception $e) {
            error_log('Get popular routes error: ' . $e->getMessage());
            return [
                ['departure_city' => 'İstanbul', 'destination_city' => 'Ankara', 'trip_count' => 10],
                ['departure_city' => 'İstanbul', 'destination_city' => 'İzmir', 'trip_count' => 8],
                ['departure_city' => 'Ankara', 'destination_city' => 'Antalya', 'trip_count' => 6]
            ];
        }
    }
    private function validateTripData($departureCity, $destinationCity, $departureTime, $arrivalTime, $price, $capacity) {
        if (empty($departureCity) || empty($destinationCity) || empty($departureTime) || empty($arrivalTime)) {
            return [
                'success' => false,
                'message' => 'Tüm alanlar zorunludur.'
            ];
        }
        if (strlen($departureCity) < 2 || strlen($destinationCity) < 2) {
            return [
                'success' => false,
                'message' => 'Şehir adları en az 2 karakter olmalıdır.'
            ];
        }
        if (strtolower($departureCity) === strtolower($destinationCity)) {
            return [
                'success' => false,
                'message' => 'Kalkış ve varış şehirleri aynı olamaz.'
            ];
        }
        $departureTimestamp = strtotime($departureTime);
        $arrivalTimestamp = strtotime($arrivalTime);
        if (!$departureTimestamp || !$arrivalTimestamp) {
            return [
                'success' => false,
                'message' => 'Geçersiz tarih formatı.'
            ];
        }
        if ($departureTimestamp <= time()) {
            return [
                'success' => false,
                'message' => 'Kalkış zamanı gelecekte olmalıdır.'
            ];
        }
        if ($arrivalTimestamp <= $departureTimestamp) {
            return [
                'success' => false,
                'message' => 'Varış zamanı kalkış zamanından sonra olmalıdır.'
            ];
        }
        if (!is_numeric($price) || $price <= 0) {
            return [
                'success' => false,
                'message' => 'Fiyat pozitif bir sayı olmalıdır.'
            ];
        }
        if (!is_numeric($capacity) || $capacity <= 0 || $capacity > 100) {
            return [
                'success' => false,
                'message' => 'Kapasite 1-100 arasında olmalıdır.'
            ];
        }
        return ['success' => true];
    }
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    public function formatTripTime($datetime, $format = 'd.m.Y H:i') {
        try {
            $date = new DateTime($datetime);
            return $date->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }
    public function calculateDuration($departureTime, $arrivalTime) {
        try {
            $departure = new DateTime($departureTime);
            $arrival = new DateTime($arrivalTime);
            $interval = $departure->diff($arrival);
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;
            if ($hours > 0) {
                return $hours . 'sa ' . $minutes . 'dk';
            } else {
                return $minutes . 'dk';
            }
        } catch (Exception $e) {
            return 'Bilinmiyor';
        }
    }
    private function normalizeTurkishText($text) {
        $turkishChars = ['İ', 'I', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'];
        $normalizedChars = ['i', 'ı', 'ğ', 'ü', 'ş', 'ö', 'ç'];
        $normalized = str_replace($turkishChars, $normalizedChars, $text);
        return mb_strtolower($normalized, 'UTF-8');
    }
}