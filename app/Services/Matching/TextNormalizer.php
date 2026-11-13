<?php

namespace App\Services\Matching;

/**
 * Normalises company names and activity descriptions before trigram comparison.
 *
 * Lao text in LCIS and AMIS is typed inconsistently: invisible zero-width spaces, the vowel
 * ຳ typed as ໍ + າ, tone marks in either order, doubled marks, ຫຼ vs ຫລ, and random spacing
 * between Lao words. All of these are unified here, then legal-form and generic words are removed.
 */
class TextNormalizer
{
    private const LAO = '\x{0E80}-\x{0EFF}';

    private const LAO_DIGITS = ['໐' => '0', '໑' => '1', '໒' => '2', '໓' => '3', '໔' => '4', '໕' => '5', '໖' => '6', '໗' => '7', '໘' => '8', '໙' => '9'];

    /**
     * The project numbers in a normalised text: the "2" of ນ້ຳງື່ມ 2, the "2c" of ນ້ຳງຽບ 2C.
     *
     * Only one- and two-digit numbers count, so years and areas are ignored, and so is the
     * amendment number of a contract (ຄັ້ງທີ 1).
     *
     * @return list<string>
     */
    public function projectNumbers(string $normalized): array
    {
        $text = preg_replace('/ຄັ້ງທີ\s*\d+/u', ' ', $normalized) ?? $normalized;
        preg_match_all('/(?<![0-9a-z])\d{1,2}[a-z]?(?![0-9a-z])/u', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Whether both texts carry project numbers and none of them are shared.
     */
    public function numbersConflict(string $normalizedA, string $normalizedB): bool
    {
        $a = $this->projectNumbers($normalizedA);
        $b = $this->projectNumbers($normalizedB);

        return $a !== [] && $b !== [] && array_intersect($a, $b) === [];
    }

    public function normalize(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = strtr(mb_strtolower($text), self::LAO_DIGITS);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;
        $text = $this->unifyLaoSpelling($text);
        $text = $this->stripLaoTerms($text);

        // Punctuation and symbols become spaces, so "co.,ltd" splits into removable words.
        $text = preg_replace('/[\p{P}\p{S}]+/u', ' ', $text) ?? $text;
        $text = $this->stripEnglishWords($text);

        // Lao is written without spaces between words; spacing in the data is arbitrary.
        $text = preg_replace('/(?<=['.self::LAO.'])\s+(?=['.self::LAO.'])/u', '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function unifyLaoSpelling(string $text): string
    {
        // ໍ + tone mark + າ (ນໍ້າ) → tone mark + ຳ (ນ້ຳ); then any remaining ໍ + າ → ຳ.
        $text = preg_replace('/\x{0ECD}([\x{0EC8}-\x{0ECB}])\x{0EB2}/u', "$1\u{0EB3}", $text) ?? $text;
        $text = str_replace("\u{0ECD}\u{0EB2}", "\u{0EB3}", $text);

        // A mark typed twice in a row (ຂຸດຄົ້້ນ).
        $text = preg_replace('/([\x{0EB1}\x{0EB4}-\x{0EBC}\x{0EC8}-\x{0ECD}])\1+/u', '$1', $text) ?? $text;

        // Ligature letters → their two-letter spelling: ຫຼ → ຫລ, ໜ → ຫນ, ໝ → ຫມ.
        return strtr($text, ["\u{0EBC}" => 'ລ', "\u{0EDC}" => 'ຫນ', "\u{0EDD}" => 'ຫມ']);
    }

    private function stripLaoTerms(string $text): string
    {
        $terms = array_map(fn (string $term): string => $this->unifyLaoSpelling($term), config('matching.strip_terms.lao'));
        usort($terms, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return str_replace($terms, ' ', $text);
    }

    private function stripEnglishWords(string $text): string
    {
        $words = array_map(fn (string $word): string => preg_quote($word, '/'), config('matching.strip_terms.english'));

        return preg_replace('/\b(?:'.implode('|', $words).')\b/u', ' ', $text) ?? $text;
    }
}
