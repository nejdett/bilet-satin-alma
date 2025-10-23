<?php
require_once __DIR__ . '/../config/database.php';
class Coupon {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance();
    }
    public function getCoupons($companyId = null, $includeExpired = false) {
        try {
            $sql = "SELECT * FROM Coupons WHERE 1=1";
            $params = [];
            if ($companyId === null) {
                $sql .= " AND company_id IS NULL";
            } else {
                $sql .= " AND company_id = ?";
                $params[] = $companyId;
            }
            if (!$includeExpired) {
                $sql .= " AND (expiry_date IS NULL OR expiry_date > datetime('now'))";
                $sql .= " AND usage_limit > 0";
            }
            $sql .= " ORDER BY created_at DESC";
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Get coupons error: ' . $e->getMessage());
            return [];
        }
    }
    public function getCompanyCoupons($companyId, $includeExpired = false) {
        return $this->getCoupons($companyId, $includeExpired);
    }
    public function getCouponById($couponId) {
        try {
            $sql = "SELECT * FROM Coupons WHERE id = ?";
            return $this->db->fetch($sql, [$couponId]);
        } catch (Exception $e) {
            error_log('Get coupon by ID error: ' . $e->getMessage());
            return false;
        }
    }
    public function createCoupon($code, $discountRate, $usageLimit, $expiryDate = null, $companyId = null) {
        try {
            $validation = $this->validateCouponData($code, $discountRate, $usageLimit);
            if (!$validation['success']) {
                return $validation;
            }
            if ($this->couponCodeExists($code)) {
                return [
                    'success' => false,
                    'message' => 'Bu kupon kodu zaten kullanılmaktadır.'
                ];
            }
            $sql = "INSERT INTO Coupons (code, discount, usage_limit, expire_date, company_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $params = [$code, $discountRate, $usageLimit, $expiryDate, $companyId];
            $this->db->execute($sql, $params);
            return [
                'success' => true,
                'message' => 'Kupon başarıyla oluşturuldu.',
                'coupon_id' => $this->db->lastInsertId()
            ];
        } catch (Exception $e) {
            error_log('Create coupon error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kupon oluşturulurken bir hata oluştu.'
            ];
        }
    }
    public function applyCoupon($couponCode, $originalAmount, $companyId = null) {
        try {
            $checkSql = "SELECT * FROM Coupons WHERE code = ?";
            $existingCoupon = $this->db->fetch($checkSql, [$couponCode]);
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
            $discountAmount = ($originalAmount * $coupon['discount']) / 100;
            $finalAmount = $originalAmount - $discountAmount;
            $this->db->execute(
                "UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?",
                [$coupon['id']]
            );
            return [
                'success' => true,
                'discountAmount' => $discountAmount,
                'finalAmount' => $finalAmount,
                'discountRate' => $coupon['discount']
            ];
        } catch (Exception $e) {
            error_log('Apply coupon error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kupon uygulanırken bir hata oluştu.'
            ];
        }
    }
    public function validateCoupon($couponCode, $companyId = null) {
        try {
            $sql = "SELECT * FROM Coupons WHERE code = ?";
            $coupon = $this->db->fetch($sql, [$couponCode]);
            if (!$coupon) {
                return false;
            }
            if ($coupon['expire_date'] && strtotime($coupon['expire_date']) <= time()) {
                return false;
            }
            if ($coupon['usage_limit'] <= 0) {
                return false;
            }
            if ($coupon['company_id'] !== null && $coupon['company_id'] != $companyId) {
                return false;
            }
            return $coupon;
        } catch (Exception $e) {
            error_log('Validate coupon error: ' . $e->getMessage());
            return false;
        }
    }
    public function updateCoupon($couponId, $data) {
        try {
            $coupon = $this->getCouponById($couponId);
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'Kupon bulunamadı.'
                ];
            }
            $updateFields = [];
            $params = [];
            $allowedFields = ['code', 'discount_rate', 'usage_limit', 'expiry_date'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'Güncellenecek veri bulunamadı.'
                ];
            }
            $params[] = $couponId;
            $sql = "UPDATE coupons SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->execute($sql, $params);
            return [
                'success' => true,
                'message' => 'Kupon başarıyla güncellendi.'
            ];
        } catch (Exception $e) {
            error_log('Update coupon error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kupon güncellenirken bir hata oluştu.'
            ];
        }
    }
    public function deleteCoupon($couponId) {
        try {
            $coupon = $this->getCouponById($couponId);
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'Kupon bulunamadı.'
                ];
            }
            $sql = "DELETE FROM coupons WHERE id = ?";
            $this->db->execute($sql, [$couponId]);
            return [
                'success' => true,
                'message' => 'Kupon başarıyla silindi.'
            ];
        } catch (Exception $e) {
            error_log('Delete coupon error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Kupon silinirken bir hata oluştu.'
            ];
        }
    }
    private function validateCouponData($code, $discountRate, $usageLimit) {
        if (empty($code)) {
            return [
                'success' => false,
                'message' => 'Kupon kodu zorunludur.'
            ];
        }
        if (strlen($code) < 3 || strlen($code) > 20) {
            return [
                'success' => false,
                'message' => 'Kupon kodu 3-20 karakter arasında olmalıdır.'
            ];
        }
        if (!preg_match('/^[A-Z0-9]+$/', $code)) {
            return [
                'success' => false,
                'message' => 'Kupon kodu sadece büyük harf ve rakam içermelidir.'
            ];
        }
        if (!is_numeric($discountRate) || $discountRate <= 0 || $discountRate > 100) {
            return [
                'success' => false,
                'message' => 'İndirim oranı 1-100 arasında olmalıdır.'
            ];
        }
        if (!is_numeric($usageLimit) || $usageLimit <= 0) {
            return [
                'success' => false,
                'message' => 'Kullanım limiti pozitif bir sayı olmalıdır.'
            ];
        }
        return ['success' => true];
    }
    private function couponCodeExists($code, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM coupons WHERE code = ?";
            $params = [$code];
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            $result = $this->db->fetch($sql, $params);
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log('Coupon code exists check error: ' . $e->getMessage());
            return true;
        }
    }
    public function getUserCoupons($userId) {
        try {
            $sql = "SELECT c.*, bc.name as company_name
                    FROM coupons c
                    LEFT JOIN Bus_Company bc ON c.company_id = bc.id
                    WHERE (c.expire_date IS NULL OR c.expire_date > datetime('now'))
                    AND c.usage_limit > 0
                    ORDER BY c.created_at DESC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Error getting user coupons: ' . $e->getMessage());
            return [];
        }
    }
}