<?php
/**
 * توس تعمیر - Core Database (PDO, prepared statements)
 */
class Database {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo === null) {
            $cfg = require __DIR__ . '/../config/database.php';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $cfg['host'], $cfg['port'], $cfg['name']);
            try {
                self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_persian_ci",
                ]);
            } catch (PDOException $e) {
                error_log('[DB] ' . $e->getMessage());
                if (!headers_sent()) {
                    http_response_code(500);
                    echo 'خطای اتصال به پایگاه داده. لطفاً بعداً تلاش کنید.';
                }
                exit;
            }
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): \PDOStatement {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array {
        $r = self::run($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function value(string $sql, array $params = []) {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int {
        $cols = array_keys($data);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (:" . implode(',:', $cols) . ")";
        self::run($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $sets = [];
        foreach (array_keys($data) as $c) $sets[] = "`$c`=:$c";
        $sql = "UPDATE `$table` SET " . implode(',', $sets) . " WHERE $where";
        return self::run($sql, array_merge($data, $whereParams))->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int {
        return self::run("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }


    /** مقدار اول ستون اول نتیجه (با نام امن scalar برای سازگاری کد) */
    public static function scalar(string $sql, array $params = [], $default = null) {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? $default : $v;
    }

    public static function begin(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollBack(): void { if (self::pdo()->inTransaction()) self::pdo()->rollBack(); }
}
