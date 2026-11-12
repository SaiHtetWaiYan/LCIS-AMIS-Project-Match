<?php

namespace Tests\Feature\Services\Matching;

use App\Services\Matching\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TextNormalizerTest extends TestCase
{
    /**
     * Pairs that must normalise to the same text; examples come from real LCIS/AMIS records.
     *
     * @return array<string, array{string, string}>
     */
    public static function equivalentSpellings(): array
    {
        return [
            'zero-width spaces and legal form' => ["ບໍ\u{200B}ລິ\u{200B}ສັດ ຮ່ວມ\u{200B}ທຶນ ສຳ\u{200B}ພັນ\u{200B}ໄຊ ຈຳ\u{200B}ກັດ", 'ຮ່ວມທຶນ ສຳພັນໄຊ'],
            'ໍ + າ typed instead of ຳ' => ['ບໍລິສັດ ຄູນຄໍາ ຈໍາກັດຜູ້ດຽວ', 'ຄູນຄຳ'],
            'tone mark before or after ໍ' => ['ເຂື່ອນໄຟຟ້ານໍ້າປັງ', 'ເຂື່ອນໄຟຟ້ານ້ຳປັງ'],
            'doubled tone mark' => ['ຂຸດຄົ້້ນຫີນປູນ', 'ຂຸດຄົ້ນຫີນປູນ'],
            'ຫຼ ligature' => ['ແຮ່ເຫຼັກ', 'ແຮ່ເຫລັກ'],
            'ໝ ligature' => ['ຄຳໝັ້ນ', 'ຄຳຫມັ້ນ'],
            'misspelt ບໍລີສັດ' => ['ບໍລີສັດ ຄຳຫມັ້ນ', 'ຄຳໝັ້ນ'],
            'generic words ໂຄງການ and ສັນຍາ' => ['ໂຄງການ ປູກກາເຟ', 'ສັນຍາ ປູກກາເຟ'],
            'English legal forms and punctuation' => ['Samphanxay Mining Co., Ltd.', 'SAMPHANXAY MINING SOLE CO.,LTD'],
        ];
    }

    #[DataProvider('equivalentSpellings')]
    public function test_normalises_equivalent_spellings_to_the_same_text(string $a, string $b): void
    {
        $normalizer = new TextNormalizer;

        $this->assertSame($normalizer->normalize($b), $normalizer->normalize($a));
        $this->assertNotSame('', $normalizer->normalize($a));
    }

    public function test_keeps_spaces_between_latin_words_and_removes_them_between_lao_words(): void
    {
        $this->assertSame('conval energy ລາວພະລັງງານ', (new TextNormalizer)->normalize('ບໍລິສັດ Conval Energy - ລາວ ພະລັງງານ'));
    }

    public function test_keeps_english_words_that_only_contain_a_legal_form(): void
    {
        $this->assertSame('cobalt mining', (new TextNormalizer)->normalize('Cobalt Mining Ltd'));
    }

    public function test_uses_strip_terms_from_the_config(): void
    {
        config(['matching.strip_terms.english' => [...config('matching.strip_terms.english'), 'mining']]);

        $this->assertSame('samphanxay', (new TextNormalizer)->normalize('Samphanxay Mining Co., Ltd'));
    }
}
