<?php
/** مدل‌های ساده مرجع (Catalog) */
class Category extends BaseModel {
    protected static string $table = 'categories';
    protected static array $fillable = ['parent_id','name','slug','description','image_path','sort_order','is_active','seo_title','seo_description'];
    public static function activeTree(): array { return static::all('is_active=1', [], 'sort_order ASC, id ASC'); }
    public static function bySlug(string $slug): ?array { return static::findBy('slug', $slug); }
}
class Brand extends BaseModel {
    protected static string $table = 'brands';
    protected static array $fillable = ['name','slug','logo_path','description','is_active','sort_order'];
    public static function activeList(): array { return static::all('is_active=1', [], 'sort_order ASC, name ASC'); }
}
class Service extends BaseModel {
    protected static string $table = 'services';
    protected static array $fillable = ['parent_id','name','slug','icon','excerpt','body','common_problems','symptoms','causes','diagnostic_steps','image_path','is_board_service','is_published','sort_order','seo_title','seo_description','seo_keywords','canonical_url','robots_meta','og_image','schema_type','alt_text'];
    public static function published(): array { return static::all('is_published=1', [], 'sort_order ASC, id ASC'); }
    public static function bySlug(string $slug): ?array { return static::findBy('slug', $slug); }
}
class ArticleCategory extends BaseModel {
    protected static string $table = 'article_categories';
    protected static array $fillable = ['name','slug','description','seo_title','seo_description','sort_order'];
}
class Faq extends BaseModel {
    protected static string $table = 'faqs';
    protected static array $fillable = ['category','question','answer','target_type','target_id','sort_order','is_active'];
    public static function forTarget(?string $type = null, ?int $id = null): array {
        if ($type === null) return static::all('is_active=1', [], 'sort_order ASC');
        return static::all("is_active=1 AND target_type=? AND (target_id=? OR target_id IS NULL)", [$type, (string)$id], 'sort_order ASC');
    }
    /** سوالات عمومی صفحه اصلی/صفحه سؤالات متداول */
    public static function general(int $limit = 0): array {
        $sql = "SELECT * FROM faqs WHERE is_active=1 AND target_type='general' ORDER BY sort_order ASC";
        if ($limit > 0) $sql .= ' LIMIT ' . max(1, $limit);
        return Database::all($sql);
    }
    public static function categories(): array {
        return array_column(Database::all('SELECT DISTINCT category FROM faqs WHERE is_active=1 ORDER BY category'), 'category');
    }
}
class Page extends BaseModel {
    protected static string $table = 'pages';
    protected static array $fillable = ['title','slug','body','is_published','show_in_footer','seo_title','seo_description','canonical_url','robots_meta','schema_type'];
    public static function bySlug(string $slug): ?array { return static::findBy('slug', $slug); }
}
class DeviceCategory extends BaseModel {
    protected static string $table = 'device_categories';
    protected static array $fillable = ['name','slug','icon','image_path','description','service_id','sort_order','is_active'];
    public static function activeList(): array { return static::all('is_active=1', [], 'sort_order ASC, id ASC'); }
}
class ArticleTag extends BaseModel {
    protected static string $table = 'article_tags';
    public static function sync(int $articleId, array $names): void {
        Database::run('DELETE FROM article_tag_relations WHERE article_id=?', [$articleId]);
        foreach (array_slice($names, 0, 15) as $raw) {
            $name = mb_substr(clean_text((string)$raw), 0, 80);
            if ($name === '') continue;
            $t = Database::one('SELECT id FROM article_tags WHERE name=? LIMIT 1', [$name]);
            $tid = $t ? (int)$t['id'] : self::insert(['name' => $name, 'slug' => unique_slug('article_tags', 'slug', slugify($name))]);
            if ($tid) Database::run('INSERT IGNORE INTO article_tag_relations (article_id, tag_id) VALUES (?,?)', [$articleId, $tid]);
        }
    }
    public static function forArticle(int $articleId): array {
        return Database::all('SELECT t.* FROM article_tags t JOIN article_tag_relations r ON r.tag_id=t.id WHERE r.article_id=? ORDER BY t.name', [$articleId]);
    }
}
class Media extends BaseModel {
    protected static string $table = 'media';
    public static function listing(int $limit = 200): array {
        return Database::all('SELECT * FROM media ORDER BY id DESC LIMIT ' . max(1, min(500, $limit)));
    }
}
