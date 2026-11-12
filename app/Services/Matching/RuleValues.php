<?php

namespace App\Services\Matching;

/**
 * The values a rule compares, as each side holds them, for showing side by side on the compare
 * screen. A note says where a value comes from (e.g. "Partner") when that is not obvious.
 */
final readonly class RuleValues
{
    /**
     * @param  list<array{text: string, note: ?string}>  $lcis
     * @param  list<array{text: string, note: ?string}>  $amis
     */
    public function __construct(
        public array $lcis,
        public array $amis,
    ) {}

    /**
     * @param  iterable<array{0: ?string, 1?: ?string}>  $values  [text, note] pairs; blank and repeated texts are dropped
     * @return list<array{text: string, note: ?string}>
     */
    public static function list(iterable $values): array
    {
        $list = [];

        foreach ($values as $value) {
            $text = trim((string) ($value[0] ?? ''));

            if ($text !== '' && ! isset($list[$text])) {
                $list[$text] = ['text' => $text, 'note' => $value[1] ?? null];
            }
        }

        return array_values($list);
    }
}
