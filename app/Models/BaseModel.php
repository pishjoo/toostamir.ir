<?php
/**
 * توس تعمیر - BaseModel: CRUD امن با prepared statements
 */
class BaseModel {
    protected static string $table = '';
    protected static string $pk = 'id';
    /** ستون‌های قابل‌ذخیره (سفید) */
    protected static array $fillable = [];

    public static function find(int $id): ?array {
        $rows = Database::all('SELECT * FROM `' . static::$table . '` WHERE `' . static::$pk . '` = ? LIMIT 1', [$id]);
        return $rows[0] ?? null;
    }

    public static function findBy(string $col, $val): ?array {
        if (!in_array($col, static::columns(), true)) throw new InvalidArgumentException('bad column');
        $rows = Database::all('SELECT * FROM `' . static::$table . '` WHERE `' . $col . '` = ? LIMIT 1', [$val]);
        return $rows[0] ?? null;
    }

    public static function all(string $where = '', array $params = [], string $order = '', int $limit = 0): array {
        $sql = 'SELECT * FROM `' . static::$table . '`' . ($where ? " WHERE $where" : '');
        if ($order !== '') $sql .= ' ORDER BY ' . $order;
        if ($limit > 0) $sql .= ' LIMIT ' . $limit;
        return Database::all($sql, $params);
    }

    public static function count(string $where = '', array $params = []): int {
        return Database::scalar('SELECT COUNT(*) FROM `' . static::$table . '`' . ($where ? " WHERE $where" : ''), $params, 0);
    }

    public static function insert(array $data): int {
        $data = array_intersect_key($data, array_flip(static::fillable()));
        if (!$data) return 0;
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
        Database::run($sql, array_values($data));
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $data = array_intersect_key($data, array_flip(static::fillable()));
        if (!$data) return false;
        $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $st = Database::run('UPDATE `' . static::$table . '` SET ' . $set . " WHERE `" . static::$pk . '` = ?', array_merge(array_values($data), [$id]));
        return $st->rowCount() >= 0;
    }

    public static function delete(int $id): bool {
        Database::run('DELETE FROM `' . static::$table . '` WHERE `' . static::$pk . '` = ?', [$id]);
        return true;
    }

    /** نام ستون‌های واقعی جدول (کش‌شده) */
    public static function columns(): array {
        static $cache = [];
        $t = static::$table;
        if (!isset($cache[$t])) {
            $rows = Database::all('SHOW COLUMNS FROM `' . $t . '`');
            $cache[$t] = array_column($rows, 'Field');
        }
        return $cache[$t];
    }

    public static function fillable(): array {
        if (static::$fillable) return static::$fillable;
        return static::columns();
    }
}
