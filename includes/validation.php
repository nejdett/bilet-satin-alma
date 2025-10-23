<?php
class InputValidator {
    private $data;
    private $errors = [];
    public function __construct($data = []) {
        $this->data = $data;
    }
    public function validate($field, $rules, $label = null) {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        foreach ($rules as $rule => $ruleValue) {
            if (is_numeric($rule)) {
                $rule = $ruleValue;
                $ruleValue = true;
            }
            switch ($rule) {
                case 'required':
                    if (empty($value)) {
                        $this->errors[$field] = $label . ' alanı zorunludur.';
                    }
                    break;
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field] = $label . ' geçerli bir e-posta adresi olmalıdır.';
                    }
                    break;
                case 'min_length':
                    if (!empty($value) && strlen($value) < $ruleValue) {
                        $this->errors[$field] = $label . ' en az ' . $ruleValue . ' karakter olmalıdır.';
                    }
                    break;
                case 'max_length':
                    if (!empty($value) && strlen($value) > $ruleValue) {
                        $this->errors[$field] = $label . ' en fazla ' . $ruleValue . ' karakter olmalıdır.';
                    }
                    break;
                case 'alpha':
                    if (!empty($value) && !preg_match('/^[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+$/u', $value)) {
                        $this->errors[$field] = $label . ' sadece harf içermelidir.';
                    }
                    break;
                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $this->errors[$field] = $label . ' sayısal bir değer olmalıdır.';
                    }
                    break;
            }
        }
        return $this;
    }
    public function isValid() {
        return empty($this->errors);
    }
    public function getErrors() {
        return $this->errors;
    }
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    public static function sanitizeForDatabase($data) {
        return trim($data);
    }
    public static function sanitizeForOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
class XSSProtection {
    public static function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    public static function cleanHTML($html, $allowedTags = []) {
        if (empty($allowedTags)) {
            return strip_tags($html);
        }
        return strip_tags($html, '<' . implode('><', $allowedTags) . '>');
    }
}
class CSRFProtection {
    public static function generateToken($action = 'default') {
        return SessionManager::generateCSRFToken();
    }
    public static function validateToken($token, $action = 'default') {
        return SessionManager::validateCSRFToken($token);
    }
    public static function getTokenField($action = 'default') {
        $token = self::generateToken($action);
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}