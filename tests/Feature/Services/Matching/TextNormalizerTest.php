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
        $this->assertSame('conval energy ລາວ', (new TextNormalizer)->normalize('ບໍລິສັດ Conval Energy - ລາວ ພະລັງງານ'));
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

    public function test_ignores_hydropower_sector_words_so_only_the_distinctive_name_is_compared(): void
    {
        $normalizer = new TextNormalizer;

        $this->assertSame('ໂມ້ 2', $normalizer->normalize('ບໍລິສັດ ໄຟຟ້ານໍ້າໂມ້ 2 ຈຳກັດ'));
        $this->assertSame('ໂມ້ 2', $normalizer->normalize('ໂຄງການເຂື່ອນໄຟຟ້ານ້ຳໂມ້ 2'));
        $this->assertSame('nam mo 2', $normalizer->normalize('Nam Mo 2 Hydropower Co., Ltd'));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function projectNumbers(): array
    {
        return [
            'single number' => ['ເຂື່ອນໄຟຟ້ານ້ຳກົງ 1', ['1']],
            'number with letter' => ['ບໍລິສັດ ໄຟຟ້ານໍ້າງຽບ 2C ຈໍາກັດຜູ້ດຽວ', ['2c']],
            'list of numbers' => ['ເຂື່ອນໄຟຟ້ານໍ້າຕົກ ນໍ້າບີ 1, 2 ແລະ 3', ['1', '2', '3']],
            'Lao digits' => ['ເຂື່ອນໄຟຟ້ານ້ຳງື່ມ ໒', ['2']],
            'amendment number is not a project number' => ['ສັນຍາດັດແກ້ (ຄັ້ງທີ 1) ໂຄງການເຂື່ອນໄຟຟ້າເຊປຽນເຊນ້ຳນ້ອຍ', []],
            'years and areas are ignored' => ['ປູກຢາງພາລາ 500 ເຮັກຕາ ປີ 2015', []],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('projectNumbers')]
    public function test_finds_project_numbers(string $text, array $expected): void
    {
        $normalizer = new TextNormalizer;

        $this->assertSame($expected, $normalizer->projectNumbers($normalizer->normalize($text)));
    }

    public function test_numbers_conflict_only_when_both_sides_have_numbers_and_none_are_shared(): void
    {
        $normalizer = new TextNormalizer;
        $conflict = fn (string $a, string $b): bool => $normalizer->numbersConflict($normalizer->normalize($a), $normalizer->normalize($b));

        $this->assertTrue($conflict('ນ້ຳກົງ 3', 'ນ້ຳກົງ 1'));
        $this->assertTrue($conflict('ນ້ຳງຽບ 2C', 'ນ້ຳງຽບ 2'));
        $this->assertFalse($conflict('ນ້ຳບີ 1, 2 ແລະ 3', 'ນ້ຳບີ 1,2,3'));
        $this->assertFalse($conflict('ນ້ຳກົງ 1', 'ນ້ຳກົງ'));
    }
}
