<?php
/**
 * Database Class - PDO with prepared statements
 */
class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    private $error;
    
    private function __construct() {
        $host = Config::get('db_host');
        $name = Config::get('db_name');
        $user = Config::get('db_user');
        $pass = Config::get('db_pass');
        $charset = Config::get('db_charset', 'utf8mb4');
        
        $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE utf8mb4_unicode_ci",
            PDO::MYSQL_ATTR_FOUND_ROWS => true
        ];
        
        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
    
    /**
     * Keys whose values must never be written to a log file.
     */
    private static $sensitiveKeys = [
        'password', 'password_hash', 'confirm_password', 'current_password',
        'new_password', 'old_password', 'pass', 'pwd',
        'csrf_token', '_csrf_token', '_token', 'token',
        'two_factor_secret', 'temp_2fa_secret', 'session_token',
        'remember_token', 'password_reset_token', 'api_key', 'secret',
        'smtp_password', 'mail_password',
    ];

    /**
     * Produce a log-safe copy of a parameter set with secrets masked.
     */
    public static function redact($data) {
        if (!is_array($data)) {
            return $data;
        }
        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), self::$sensitiveKeys, true)) {
                $out[$k] = '***';
            } elseif (is_array($v)) {
                $out[$k] = self::redact($v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    public static function query($sql, $params = []) {
        try {
            $conn = self::getInstance();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    public static function fetchAll($sql, $params = []) {
        try {
            $stmt = self::query($sql, $params);
            $result = $stmt ? $stmt->fetchAll() : [];
            return is_array($result) ? $result : [];
        } catch (Throwable $e) {
            error_log("fetchAll Error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function fetchOne($sql, $params = []) {
        try {
            $stmt = self::query($sql, $params);
            $result = $stmt ? $stmt->fetch() : null;
            return is_array($result) ? $result : null;
        } catch (Throwable $e) {
            error_log("fetchOne Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function insert($table, $data) {
        try {
            $conn = self::getInstance();
            
            $columns = implode('`, `', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO `$table` (`$columns`) VALUES ($placeholders)";

            $stmt = $conn->prepare($sql);
            $success = $stmt->execute(array_values($data));

            if ($success) {
                return $conn->lastInsertId();
            }

            error_log("Insert failed on `$table` - execute returned false");
            return false;

        } catch (PDOException $e) {
            error_log("Insert Error on `$table`: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function update($table, $data, $where, $whereParams = []) {
        try {
            $conn = self::getInstance();
            
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "`$column` = ?";
            }
            $setClause = implode(', ', $set);
            
            $sql = "UPDATE `$table` SET $setClause WHERE $where";

            $stmt = $conn->prepare($sql);
            $stmt->execute(array_merge(array_values($data), $whereParams));

            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Update Error on `$table`: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function delete($table, $where, $params = []) {
        try {
            $conn = self::getInstance();
            
            $sql = "DELETE FROM `$table` WHERE $where";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Delete Error on `$table`: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function tableExists($table) {
        try {
            $conn = self::getInstance();
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * $column defaults to 'command_id' — the name every scoped table uses
     * except `requisitions`, which calls this with 'requesting_command_id'.
     * Passing the wrong name here doesn't fail loudly: it splices in a
     * reference to a column the table doesn't have, the query throws, and
     * fetchAll()/fetchOne() catch that and quietly return []/null — so a
     * command-restricted viewer just sees an empty list, not an error.
     * (That's exactly what happened to requisitions before this parameter
     * existed — every call site here hardcoded "command_id".)
     */
    public static function applyCommandFilter($sql, $alias, &$params, $column = 'command_id') {
        if (!Auth::isCommandRestricted()) {
            return $sql;
        }
        return self::injectCondition($sql, " `{$alias}`.`{$column}` = ? ", $params, Auth::commandId());
    }

    /**
     * Splice an arbitrary "alias.column = ?" filter into $sql when $value
     * is non-empty — the ad-hoc counterpart to applyCommandFilter() for a
     * filter the *viewer* picks (a Command/Formation dropdown on weapons/
     * ammunition), rather than one derived from their own account.
     */
    public static function applyOptionalFilter($sql, $alias, $column, $value, &$params) {
        if ($value === null || $value === '') {
            return $sql;
        }
        return self::injectCondition($sql, " `{$alias}`.`{$column}` = ? ", $params, $value);
    }

    /**
     * Splice an extra condition into $sql, AND-combined with any existing
     * WHERE, or inserted as a new WHERE (before ORDER BY/GROUP BY/LIMIT)
     * when there isn't one yet. Shared building block behind
     * applyCommandFilter() and applyOptionalFilter().
     */
    private static function injectCondition($sql, $condition, &$params, $paramValue) {
        if (stripos($sql, ' WHERE ') !== false) {
            $pos = stripos($sql, ' WHERE ');
            $sql = substr_replace($sql, ' WHERE ' . $condition . ' AND ', $pos, 7);
            array_unshift($params, $paramValue);
        } else {
            $keywords = [' ORDER BY ', ' GROUP BY ', ' LIMIT '];
            $insertPos = strlen($sql);
            foreach ($keywords as $kw) {
                $pos = stripos($sql, $kw);
                if ($pos !== false && $pos < $insertPos) {
                    $insertPos = $pos;
                }
            }
            $sql = substr_replace($sql, ' WHERE ' . $condition, $insertPos, 0);
            $params[] = $paramValue;
        }

        return $sql;
    }

    public static function beginTransaction() {
        return self::getInstance()->beginTransaction();
    }
    
    public static function commit() {
        return self::getInstance()->commit();
    }
    
    public static function rollBack() {
        return self::getInstance()->rollBack();
    }
}