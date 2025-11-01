<?php
class Database {
    private static $instance = null;
    private $connection;
    private $dbPath;
    private $isConnected = false;
    private function __construct() {
        $this->dbPath = __DIR__ . '/../database/bilet-satis-veritabani.db';
        $this->connect();
    }
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    // Connect to SQLite database
    private function connect() {
        try {
            // Check if DB file exists
            if (!file_exists($this->dbPath)) {
                throw new Exception('Database file not found: ' . $this->dbPath);
            }
            if (!is_readable($this->dbPath) || !is_writable($this->dbPath)) {
                throw new Exception('Database file permissions error');
            }
            $this->connection = new PDO('sqlite:' . $this->dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->isConnected = true;
        } catch (PDOException $e) {
            error_log('Database PDO Error: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please try again later.');
        } catch (Exception $e) {
            error_log('Database Error: ' . $e->getMessage());
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
    public function getConnection() {
        if (!$this->isConnected) {
            throw new Exception('Database not connected');
        }
        return $this->connection;
    }
    public function query($sql, $params = []) {
        try {
            if (!$this->isConnected) {
                throw new Exception('Database not connected');
            }
            $stmt = $this->connection->prepare($sql);
            if (!is_array($params)) {
                throw new Exception('Parameters must be an array');
            }
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database Query Error: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed. Please try again.');
        }
    }
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    public function lastInsertId() {
        if (!$this->isConnected) {
            throw new Exception('Database not connected');
        }
        return $this->connection->lastInsertId();
    }
    public function beginTransaction() {
        if (!$this->isConnected) {
            throw new Exception('Database not connected');
        }
        return $this->connection->beginTransaction();
    }
    public function commit() {
        if (!$this->isConnected) {
            throw new Exception('Database not connected');
        }
        return $this->connection->commit();
    }
    public function rollback() {
        if (!$this->isConnected) {
            throw new Exception('Database not connected');
        }
        return $this->connection->rollback();
    }
    public function inTransaction() {
        if (!$this->isConnected) {
            return false;
        }
        return $this->connection->inTransaction();
    }
    public function escapeLike($input) {
        return str_replace(['%', '_'], ['\%', '\_'], $input);
    }
    public function optimizeDatabase() {
        try {
            if (!$this->isConnected) {
                throw new Exception('Database not connected');
            }
            $this->connection->exec('PRAGMA journal_mode = WAL');
            $this->connection->exec('PRAGMA cache_size = 10000');
            $this->connection->exec('PRAGMA optimize');
            $this->connection->exec('PRAGMA synchronous = NORMAL');
            $this->connection->exec('PRAGMA mmap_size = 268435456'); // 256MB
            return true;
        } catch (Exception $e) {
            error_log('Database optimization error: ' . $e->getMessage());
            return false;
        }
    }
    public function createIndexes() {
        try {
            $indexes = [
                'CREATE INDEX IF NOT EXISTS idx_trips_departure_time ON trips(departure_time)',
                'CREATE INDEX IF NOT EXISTS idx_trips_departure_city ON trips(departure_city)',
                'CREATE INDEX IF NOT EXISTS idx_trips_destination_city ON trips(destination_city)',
                'CREATE INDEX IF NOT EXISTS idx_trips_company_id ON trips(company_id)',
                'CREATE INDEX IF NOT EXISTS idx_tickets_trip_id ON tickets(trip_id)',
                'CREATE INDEX IF NOT EXISTS idx_tickets_user_id ON tickets(user_id)',
                'CREATE INDEX IF NOT EXISTS idx_user_email ON User(email)',
                'CREATE INDEX IF NOT EXISTS idx_user_role ON User(role)',
                'CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code)',
                'CREATE INDEX IF NOT EXISTS idx_coupons_company_id ON coupons(company_id)'
            ];
            foreach ($indexes as $indexSQL) {
                $this->connection->exec($indexSQL);
            }
            return true;
        } catch (Exception $e) {
            error_log('Index creation error: ' . $e->getMessage());
            return false;
        }
    }
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}