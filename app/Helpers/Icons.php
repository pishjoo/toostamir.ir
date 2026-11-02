<?php
/**
 * توس تعمیر - آیکون‌های SVG خدمات (inline، بدون کتابخانه خارجی)
 */
namespace TT;

class Icons
{
    public static function service(string $icon, string $fallbackName = ''): string
    {
        $key = self::detect($icon, $fallbackName);
        return self::ICONS[$key] ?? self::ICONS['wrench'];
    }

    private static function detect(string $icon, string $name): string
    {
        $s = mb_strtolower($icon . ' ' . $name);
        foreach (self::ALIASES as $key => $words) {
            foreach ($words as $w) if (str_contains($s, $w)) return $key;
        }
        return 'wrench';
    }

    private const ALIASES = [
        'washer'   => ['washer', 'لباسشویی'],
        'dish'     => ['dishwasher', 'ظرفشویی'],
        'fridge'   => ['fridge', 'refrigerator', 'یخچال', 'فریزر', 'freezer'],
        'boiler'   => ['boiler', 'پکیج', 'buchi', 'گرمکن'],
        'micro'    => ['microwave', 'مایکرو'],
        'vacuum'   => ['vacuum', 'جارو'],
        'pcb'      => ['pcb', 'board', 'برد', 'electronic'],
        'cooler'   => ['cooler', 'ac', 'conditioner', 'کولر', 'اسپلیت'],
        'personal' => ['personal', 'اتو', 'سشوار', 'مو'],
        'part'     => ['part', 'قطعه', 'پمپ', 'موتور'],
    ];

    private const ICONS = [
        'washer' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="5"/><path d="M9 13c1.5-2 3-2 4.5 0S15 15 16.5 13"/><circle cx="7.5" cy="5" r=".8" fill="currentColor"/><circle cx="10" cy="5" r=".8" fill="currentColor"/></svg>',
        'dish' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M6 8h12M6 12h12M6 16h12"/><circle cx="8" cy="5" r="1"/></svg>',
        'fridge' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M6 10h12M9 6v2M9 12v3"/></svg>',
        'boiler' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="4" width="14" height="14" rx="2"/><path d="M9 21v-3M15 21v-3M8 8h4M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
        'micro' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="5" width="20" height="14" rx="2"/><rect x="4" y="7" width="12" height="10" rx="1"/><path d="M19 9v6"/></svg>',
        'vacuum' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="15" r="6"/><path d="M9 9V5c0-1 1-2 3-2h3"/><circle cx="6" cy="21" r="1.4"/><circle cx="13" cy="21" r="1.4"/></svg>',
        'pcb' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="8" y="8" width="8" height="8" rx="1"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M7 7l1.5 1.5M17 7l-1.5 1.5"/></svg>',
        'cooler' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="4" width="20" height="8" rx="2"/><path d="M6 16c0 2 1 2 1 4M12 16c0 2 1 2 1 4M18 16c0 2 1 2 1 4"/></svg>',
        'personal' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 18h18L17 8H7z"/><path d="M8 8c1-3 6-3 8 0"/></svg>',
        'part' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/></svg>',
        'wrench' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14.5 6.5a4 4 0 0 0-5.4 5L3 17.6V21h3.4l6.1-6.1a4 4 0 0 0 5-5.4l-2.9 2.9-2.1-2.1z"/></svg>',
    ];
}
