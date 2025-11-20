<?php
/**
 * Database Connection and Query Functions
 * PDO-based secure database operations
 */

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Veritabanı bağlantı hatası. Lütfen daha sonra tekrar deneyin.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute a query and return all results
 */
function dbQuery($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Execute a query and return single row
 */
function dbQueryOne($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Execute INSERT, UPDATE, DELETE queries
 */
function dbExecute($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Execute Error: " . $e->getMessage() . " | SQL: " . $sql);
        return false;
    }
}

/**
 * Insert data and return last insert ID
 */
function dbInsert($table, $data) {
    try {
        $db = getDB();

        $columns = array_keys($data);
        $values = array_values($data);

        $columnString = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = "INSERT INTO $table ($columnString) VALUES ($placeholders)";
        $stmt = $db->prepare($sql);

        if ($stmt->execute($values)) {
            return $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update data
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    try {
        $db = getDB();

        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }

        $setString = implode(', ', $setParts);
        $values = array_merge($values, $whereParams);

        $sql = "UPDATE $table SET $setString WHERE $where";
        $stmt = $db->prepare($sql);

        return $stmt->execute($values);
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete data
 */
function dbDelete($table, $where, $params = []) {
    try {
        $db = getDB();
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Count rows
 */
function dbCount($table, $where = '1=1', $params = []) {
    try {
        $db = getDB();
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        error_log("Count Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if record exists
 */
function dbExists($table, $where, $params = []) {
    return dbCount($table, $where, $params) > 0;
}

/**
 * Get single value
 */
function dbGetValue($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result ? $result[0] : null;
    } catch (PDOException $e) {
        error_log("Get Value Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Begin transaction
 */
function dbBeginTransaction() {
    return getDB()->beginTransaction();
}

/**
 * Commit transaction
 */
function dbCommit() {
    return getDB()->commit();
}

/**
 * Rollback transaction
 */
function dbRollback() {
    return getDB()->rollBack();
}

/**
 * Escape string for LIKE queries
 */
function dbEscapeLike($string) {
    return str_replace(['%', '_'], ['\%', '\_'], $string);
}
