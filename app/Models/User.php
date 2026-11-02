<?php
/** مدل کاربر پنل مدیریت + احراز هویت امن */
class User extends BaseModel {
    protected static string $table = 'users';
    protected static array $fillable = ['name','email','username','password_hash','role_id','is_active'];

    public static function findByUsername(string $u): ?array {
        return static::findBy('username', $u);
    }

    public static function verifyLogin(string $username, string $password): ?array {
        $username = trim($username);
        // محدودیت تلاش ناموفق: ۵ بار در ۱۵ دقیقه برای هر IP+نام‌کاربری
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $recent = Database::scalar(
            "SELECT COUNT(*) FROM login_attempts WHERE ip_address=? AND username=? AND success=0 AND created_at > (NOW() - INTERVAL 15 MINUTE)",
            [$ip, $username], 0);
        if ($recent >= 5) return null;

        $u = static::findByUsername($username);
        $ok = $u && $u['is_active'] && password_verify($password, $u['password_hash']);
        Database::run("INSERT INTO login_attempts (user_id, ip_address, username, success) VALUES (?,?,?,?)",
            [$u['id'] ?? null, $ip, mb_substr($username, 0, 80), $ok ? 1 : 0]);
        if (!$ok) { audit_log('login_failed', 'user', null, 'username=' . $username); return null; }

        // ارتقای هش در صورت تغییر الگوریتم
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            static::update((int)$u['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }
        session_regenerate_id(true);
        $_SESSION['admin_uid'] = (int)$u['id'];
        $_SESSION['admin_role'] = self::roleSlug((int)$u['role_id']);
        $_SESSION['admin_name'] = $u['name'];
        audit_log('login', 'user', (int)$u['id']);
        return $u;
    }

    public static function roleSlug(int $roleId): string {
        return Database::scalar('SELECT slug FROM roles WHERE id=?', [$roleId], 'guest');
    }

    public static function permissionsFor(int $roleId): array {
        return array_column(Database::all(
            'SELECT p.slug FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=?',
            [$roleId]), 'slug');
    }

    public static function current(): ?array {
        if (empty($_SESSION['admin_uid'])) return null;
        return static::find((int)$_SESSION['admin_uid']);
    }

    public static function logout(): void {
        audit_log('logout', 'user', (int)($_SESSION['admin_uid'] ?? 0));
        unset($_SESSION['admin_uid'], $_SESSION['admin_role'], $_SESSION['admin_name']);
        session_regenerate_id(true);
    }
}
