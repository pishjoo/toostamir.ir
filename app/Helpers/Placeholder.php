<?php
/**
 * توس تعمیر - تولید تصاویر SVG حرفه‌ای برای هر دسته دستگاه (Placeholder اختصاصی)
 * خروجی: تگ <img> با data URI — بدون وابستگی خارجی، سبک و قابل کش.
 */
namespace TT;

class Placeholder
{
    /** نگاشت کلیدواژه‌های slug/icon به تصویر */
    public static function svg(string $label, int $w = 640, int $h = 480): string
    {
        $key = self::detect(mb_strtolower($label));
        [$body, $tint] = self::draw($key);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="' . $w . '" height="' . $h . '">'
             . '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">'
             . '<stop offset="0" stop-color="#F3F8FC"/><stop offset="1" stop-color="#DCEAF5"/></linearGradient>'
             . '<linearGradient id="mt" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#C7D6E4"/></linearGradient>'
             . '</defs>'
             . '<rect width="640" height="480" fill="url(#bg)"/>'
             . '<circle cx="520" cy="90" r="150" fill="#00B8D9" opacity="0.10"/>'
             . '<circle cx="80" cy="420" r="120" fill="#10243A" opacity="0.07"/>'
             . '<g transform="translate(320 235)" stroke="#10243A" stroke-width="7" fill="none" stroke-linecap="round" stroke-linejoin="round">'
             . str_replace('MT', 'url(#mt)', $body)
             . '</g>'
             . '<g transform="translate(320 235)" fill="' . $tint . '" stroke="none">' . self::accents($key) . '</g>'
             . '</svg>';
        return '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" width="' . $w . '" height="' . $h . '" loading="lazy" alt="">';
    }

    private static function detect(string $s): string
    {
        $map = [
            'washer' => ['لباسشویی', 'washing', 'washer'],
            'dish'   => ['ظرفشویی', 'dish'],
            'fridge' => ['یخچال', 'فریزر', 'refrigerator', 'freezer', 'fridge'],
            'boiler' => ['پکیج', 'بویلر', 'boiler', 'heater'],
            'micro'  => ['مایکرو', 'microwave'],
            'vacuum' => ['جارو', 'vacuum'],
            'pcb'    => ['برد', 'electronic', 'pcb', 'board', 'مدار'],
            'cooler' => ['کولر', 'اسپلیت', 'conditioner', 'cooler'],
            'iron'   => ['اتو', 'سشوار', 'تبخچ', 'لوازم شخصی', 'personal', 'iron', 'hair'],
            'part'   => ['قطعه', 'پمپ', 'موتور', 'shower', 'element', 'part', 'filter'],
        ];
        foreach ($map as $key => $words) {
            foreach ($words as $wd) if (str_contains($s, mb_strtolower($wd))) return $key;
        }
        return 'tool';
    }

    /** خطوط اصلی شکل (stroke) */
    private static function draw(string $k): array
    {
        return match ($k) {
            'washer' => ['<rect x="-110" y="-150" width="220" height="300" rx="22" fill="MT"/>
                <rect x="-88" y="-128" width="176" height="44" rx="10"/>
                <circle cx="0" cy="35" r="82"/><circle cx="0" cy="35" r="52"/>
                <path d="M-38 35c12-22 26-22 38 0s26 22 38 0"/>
                <circle cx="-62" cy="-106" r="8"/><circle cx="-34" cy="-106" r="8"/>', '#10243A'],
            'dish' => ['<rect x="-110" y="-150" width="220" height="300" rx="22" fill="MT"/>
                <rect x="-88" y="-128" width="176" height="40" rx="10"/>
                <rect x="-80" y="-60" width="160" height="190" rx="14"/>
                <path d="M-52 -28h104M-52 4h104M-52 36h104M-52 68h104"/>
                <circle cx="-62" cy="-108" r="8"/>', '#10243A'],
            'fridge' => ['<rect x="-95" y="-170" width="190" height="340" rx="20" fill="MT"/>
                <path d="M-95 -60h190"/>
                <path d="M-62 -140v50M-62 -30v70"/>
                <path d="M-70 90h140M-70 120h140" stroke-dasharray="4 10"/>', '#10243A'],
            'boiler' => ['<rect x="-100" y="-120" width="200" height="190" rx="16" fill="MT"/>
                <rect x="-78" y="-98" width="70" height="46" rx="8"/>
                <circle cx="52" cy="-74" r="16"/>
                <path d="M-40 70v40M40 70v40"/>
                <path d="M-70 -140h140" stroke-width="9"/>
                <path d="M-14 -140v20M14 -140v20"/>', '#10243A'],
            'micro' => ['<rect x="-140" y="-90" width="280" height="180" rx="16" fill="MT"/>
                <rect x="-118" y="-68" width="160" height="136" rx="10"/>
                <circle cx="-38" cy="0" r="34" stroke-dasharray="6 10"/>
                <path d="M66 -44v88M66 -20h28M66 8h28"/>', '#10243A'],
            'vacuum' => ['<circle cx="-40" cy="60" r="80" fill="MT"/><circle cx="-40" cy="60" r="30"/>
                <path d="M-40 -20c0-60 60-90 120-80"/>
                <path d="M80 -100l46 14-24 40-46-14z" fill="MT"/>
                <circle cx="-96" cy="112" r="16"/><circle cx="16" cy="112" r="16"/>', '#10243A'],
            'pcb' => ['<rect x="-140" y="-110" width="280" height="220" rx="14" fill="#0E3B2E" stroke="#0E3B2E"/>
                <g stroke="#00B8D9" stroke-width="5">
                  <path d="M-110 -70h70v50h60M-110 -20h40M-20 -80v40M40 -70h70M60 -30v70M-90 40h60v40M20 60h90"/>
                </g>
                <g stroke="#10243A" stroke-width="6" fill="MT">
                  <rect x="-100" y="-95" width="46" height="30" rx="4"/><rect x="30" y="-95" width="70" height="44" rx="6"/>
                  <rect x="-60" y="30" width="56" height="40" rx="5"/>
                </g>
                <g fill="#00B8D9" stroke="none"><circle cx="-110" cy="-70" r="6"/><circle cx="20" cy="-20" r="6"/><circle cx="40" cy="-70" r="6"/><circle cx="60" cy="40" r="6"/><circle cx="-30" cy="80" r="6"/><circle cx="110" cy="60" r="6"/></g>', '#0E3B2E'],
            'cooler' => ['<rect x="-150" y="-90" width="300" height="90" rx="18" fill="MT"/>
                <path d="M-120 -28h240"/>
                <path d="M-90 20c0 26 20 26 20 52M0 20c0 26 20 26 20 52M90 20c0 26 20 26 20 52" stroke="#00B8D9"/>', '#10243A'],
            'iron' => ['<path d="M-120 60h240c-10-60-70-90-140-90z" fill="MT"/>
                <path d="M-120 60h240"/>
                <path d="M-60 -30c20-40 90-40 110 0" />
                <path d="M-90 90h180" stroke="#00B8D9"/>', '#10243A'],
            'part' => ['<circle cx="0" cy="0" r="90" fill="MT"/><circle cx="0" cy="0" r="34"/>
                <path d="M0 -90v-30M0 120v-30M-90 0h-30M120 0h-30" stroke-width="10"/>
                <circle cx="0" cy="-60" r="7"/><circle cx="52" cy="30" r="7"/><circle cx="-52" cy="30" r="7"/>', '#10243A'],
            default => ['<path d="M-110 90l70-70m0 0a52 52 0 0 1 74-74l-26 26 24 24 26-26a52 52 0 0 1-74 74z" fill="MT"/>
                <path d="M-130 110h120" stroke="#00B8D9"/>', '#10243A'],
        };
    }

    /** نقاط تأکیدی cyan */
    private static function accents(string $k): string
    {
        return match ($k) {
            'washer' => '<circle cx="-62" cy="-106" r="6"/><circle cx="-34" cy="-106" r="6"/><rect x="30" y="-114" width="46" height="14" rx="7"/>',
            'dish'   => '<rect x="30" y="-116" width="46" height="14" rx="7"/>',
            'fridge' => '<rect x="-66" y="-142" width="8" height="54" rx="4"/><rect x="-66" y="-32" width="8" height="74" rx="4"/>',
            'boiler' => '<rect x="-72" y="-92" width="58" height="34" rx="6" opacity=".85"/><circle cx="52" cy="-74" r="9"/>',
            'micro'  => '<rect x="60" y="-48" width="12" height="96" rx="6"/>',
            'vacuum' => '<circle cx="-40" cy="60" r="12"/>',
            'pcb'    => '',
            'cooler' => '<rect x="-140" y="-80" width="280" height="10" rx="5" opacity=".7"/>',
            'iron'   => '<circle cx="10" cy="-52" r="8"/>',
            'part'   => '<circle cx="0" cy="0" r="14"/>',
            default  => '<circle cx="-118" cy="110" r="9"/>',
        };
    }
}
