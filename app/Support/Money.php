<?php

namespace App\Support;

/**
 * Conversões de dinheiro (ADR-0009).
 *
 * No banco e no código, dinheiro é SEMPRE um inteiro em centavos:
 * R$ 459,90 é guardado como 45990. Assim evitamos o erro clássico de
 * ponto flutuante (em PHP, 0.1 + 0.2 == 0.3 é false).
 */
final class Money
{
    /** 45990 → "R$ 459,90" */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        $reais = intdiv($cents, 100);
        $centavos = $cents % 100;

        return $sign.'R$ '.number_format($reais, 0, ',', '.').','.str_pad((string) $centavos, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Texto digitado pelo usuário → centavos. Devolve null se não entender.
     *
     * Aceita "459,90", "1.459,90", "1459.90", "R$ 459" e "459".
     * Note que não usamos float em nenhum momento: separamos a parte
     * inteira e a decimal como texto e montamos o inteiro.
     */
    public static function parse(?string $input): ?int
    {
        if ($input === null) {
            return null;
        }

        $value = str_replace(['R$', ' ', "\u{00A0}"], '', trim($input));

        if (str_contains($value, ',')) {
            // Formato brasileiro: ponto é milhar, vírgula é decimal.
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (! preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', $value, $m)) {
            return null;
        }

        $decimals = str_pad($m[2] ?? '0', 2, '0');

        return (int) $m[1] * 100 + (int) $decimals;
    }

    /** 45990 → "459,90", para preencher campos de formulário. */
    public static function toInput(?int $cents): string
    {
        if ($cents === null) {
            return '';
        }

        return intdiv($cents, 100).','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
