<?php
/**
 * توس تعمیر - ماژول هوش مصنوعی (تولید پیش‌نویس مقاله)
 * - فقط با API Key واقعی که مدیر در پنل تنظیم می‌کند کار می‌کند.
 * - خروجی هرگز خودکار منتشر نمی‌شود؛ همیشه به‌صورت draft ذخیره می‌گردد.
 * - سرویس پیش‌فرض: OpenAI-compatible chat completions (قابل تغییر به هر endpoint سازگار).
 */
class AiService {

    public static function isConfigured(): bool {
        return setting('ai_enabled', '0') === '1' && trim(setting('ai_api_key', '')) !== '';
    }

    /** @return array{ok:bool,title?:string,body?:string,msg?:string} */
    public static function generateArticleDraft(string $topic, ?string $keywords = null): array {
        if (!self::isConfigured()) {
            return ['ok' => false, 'msg' => 'ماژول هوش مصنوعی غیرفعال است یا کلید API تنظیم نشده.'];
        }
        $apiUrl = rtrim(setting('ai_api_url', 'https://api.openai.com/v1'), '/');
        $model  = setting('ai_model', 'gpt-4o-mini');
        $system = "تو یک نویسنده فارسی متخصص سئو برای کسب‌وکار «" . setting('site_name', 'توس تعمیر') . "» (تعمیر لوازم خانگی و فروش قطعات یدکی در مشهد) هستی.\n"
                . "قوانین:\n"
                . "- فارسی روان، تخصصی و کاربردی بنویس؛ بدون اطلاعات ساختگی.\n"
                . "- از تکرار بی‌هدف کلمات کلیدی پرهیز کن.\n"
                . "- ساختار HTML ساده با تگ‌های h2/h3/p/ul/li تولید کن.\n"
                . "- در انتها سه بولت «نکته ایمنی» مرتبط با موضوع اضافه کن.\n"
                . "- خروجی را دقیقاً در قالب JSON برگردان: {\"title\": \"...\", \"excerpt\": \"...\", \"body\": \"HTML\"}";
        $user = 'موضوع مقاله: ' . mb_substr($topic, 0, 200);
        if ($keywords) $user .= "\nکلمات کلیدی هدف (در صورت انطباق طبیعی استفاده شود): " . mb_substr($keywords, 0, 200);

        $ch = curl_init($apiUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . setting('ai_api_key')],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false || $code < 200 || $code >= 300) {
            error_log('[ai] http=' . $code . ' err=' . $err);
            return ['ok' => false, 'msg' => 'ارتباط با سرویس هوش مصنوعی ناموفق بود (HTTP ' . ($code ?: 'ERR') . ').'];
        }
        $j = json_decode((string)$resp, true);
        $content = $j['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode((string)$content, true);
        if (!is_array($parsed) || empty($parsed['title']) || empty($parsed['body'])) {
            // fallback: تبدیل متن خام
            if (trim((string)$content) === '') return ['ok' => false, 'msg' => 'پاسخ سرویس خالی بود.'];
            $parsed = ['title' => mb_substr($topic, 0, 100), 'excerpt' => '', 'body' => nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'))];
        }
        return ['ok' => true,
                'title' => mb_substr(clean_text((string)$parsed['title']), 0, 220),
                'excerpt' => mb_substr(clean_text((string)($parsed['excerpt'] ?? strip_tags((string)$parsed['body']))), 0, 500),
                'body' => sanitize_html((string)$parsed['body'])];
    }
}
