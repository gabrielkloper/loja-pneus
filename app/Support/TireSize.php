<?php

namespace App\Support;

/**
 * Entende a medida de pneu do jeito que as pessoas digitam.
 *
 *   TireSize::parse('205/55R16')   → ['width' => 205, 'aspect_ratio' => 55, 'rim' => '16.0']
 *   TireSize::parse('205 55 16')   → mesma coisa
 *   TireSize::parse('295/80 R22.5') → ['width' => 295, 'aspect_ratio' => 80, 'rim' => '22.5']
 *   TireSize::parse('qualquer')    → null
 */
final class TireSize
{
    /**
     * @return array{width: int, aspect_ratio: int, rim: string}|null
     */
    public static function parse(?string $input): ?array
    {
        if ($input === null) {
            return null;
        }

        $pattern = '/^\s*(\d{3})\s*[\/\s-]?\s*(\d{2})\s*(?:Z?R|-)?\s*(\d{2}(?:[.,]5)?)\s*$/i';

        if (! preg_match($pattern, $input, $m)) {
            return null;
        }

        return [
            'width' => (int) $m[1],
            'aspect_ratio' => (int) $m[2],
            'rim' => number_format((float) str_replace(',', '.', $m[3]), 1, '.', ''),
        ];
    }
}
