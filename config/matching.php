<?php

use App\Services\Matching\Rules\ActivityRule;
use App\Services\Matching\Rules\AreaRule;
use App\Services\Matching\Rules\CompanyNameRule;
use App\Services\Matching\Rules\LocationRule;

return [

    /*
    |--------------------------------------------------------------------------
    | Scoring rules and weights
    |--------------------------------------------------------------------------
    |
    | Each rule scores a candidate pair 0–1, or returns no score when either
    | side lacks the data. The total is the weighted average over the rules
    | that scored. Add or remove a rule here; screens and workflow adapt.
    |
    */

    'rules' => [
        CompanyNameRule::class => 0.35,
        ActivityRule::class => 0.25,
        LocationRule::class => 0.20,
        AreaRule::class => 0.20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Candidate suggestions
    |--------------------------------------------------------------------------
    |
    | text_threshold: minimum pg_trgm similarity for a name/activity to pull a
    | record into the candidate pool (other records come in by sharing a
    | province). pool_size caps how many records are scored per request.
    |
    */

    'candidates' => [
        'limit' => 10,
        'text_threshold' => 0.3,
        'pool_size' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Location rule
    |--------------------------------------------------------------------------
    */

    'location' => [
        'province' => 0.5,
        'district' => 0.3,
        'village' => 0.2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Area rule
    |--------------------------------------------------------------------------
    |
    | Relative difference |a − b| / max(a, b): at or below full_score_within
    | the score is 1; it falls linearly to 0 at zero_score_from.
    |
    */

    'area' => [
        'full_score_within' => 0.05,
        'zero_score_from' => 0.50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Text normalisation
    |--------------------------------------------------------------------------
    |
    | Removed before comparing names and activities. Lao terms are removed
    | wherever they occur (Lao has no spaces between words); English terms
    | only as whole words. Lao spellings are unified first, so ຈໍາກັດ and
    | ຈຳກັດ are both caught by ຈຳກັດ.
    |
    */

    'strip_terms' => [
        'lao' => [
            'ສະຫະບໍລິສັດ', 'ບໍລິສັດ', 'ບໍລີສັດ', 'ຈຳກັດຜູ້ດຽວ', 'ຈຳກັດ', 'ຜູ້ດຽວ',
            'ລັດວິສາຫະກິດ', 'ວິສາຫະກິດ', 'ທ່ານ', 'ໂຄງການ', 'ສັນຍາ',
            // Sector words shared by most hydropower names; left in, they make different dams look alike.
            'ເຂື່ອນ', 'ໄຟຟ້າ', 'ນ້ຳຕົກ', 'ນ້ຳ', 'ພາວເວີ', 'ພະລັງງານ',
            // Filler and process words shared by unrelated activities (an iron ore plant vs a sawmill).
            // Words that tell the kind of work apart (ປູກ, ລ້ຽງ, ຂຸດຄົ້ນ, ສຳຫຼວດ) are kept.
            'ໂຮງງານ', 'ຜະລິດ', 'ປຸງແຕ່ງ', 'ແລະ', 'ພັດທະນາ', 'ເພື່ອ', 'ກໍ່ສ້າງ', 'ສ້າງ', 'ຈຳໜ່າຍ',
        ],
        'english' => [
            'company', 'co', 'ltd', 'limited', 'sole', 'inc', 'corporation', 'corp', 'enterprise', 'project', 'contract', 'the',
            'hydropower', 'hydroelectric', 'hydro', 'power', 'electric', 'electricity', 'dam',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Project numbers
    |--------------------------------------------------------------------------
    |
    | Numbers such as the "2" in ນ້ຳງື່ມ 2 or "2C" in ນ້ຳງຽບ 2C tell projects of
    | the same name apart. When both texts carry numbers and none are shared,
    | their similarity is multiplied by this factor.
    |
    */

    'number_mismatch_factor' => 0.3,

    /*
    |--------------------------------------------------------------------------
    | Score caps
    |--------------------------------------------------------------------------
    |
    | A pair cannot score above total_at_most when the activities are less
    | similar than activity_below (same company, different project), or when
    | no province matches (only where LCIS has the real land location, not
    | just the responsible office). Capped pairs stay listed, just lower.
    |
    */

    'caps' => [
        'total_at_most' => 0.4,
        'activity_below' => 0.2,
    ],

    /*
    |--------------------------------------------------------------------------
    | AMIS area units
    |--------------------------------------------------------------------------
    |
    | Factor that converts one unit to hectares. AMIS stores the unit as free
    | (mostly Lao) text. Keys are compared after lowercasing and removing
    | spaces and zero-width characters. A unit not listed here counts as "no
    | area data" and is reported on the sync status page so it can be added.
    |
    */

    'area_units' => [
        'ເຮັກຕາ' => 1,
        'ha' => 1,
        'hectare' => 1,
        'hectares' => 1,
        'ຕາແມັດ' => 0.0001,
        'ຕາລາງແມັດ' => 0.0001,
        'm2' => 0.0001,
        'sqm' => 0.0001,
        'ກິໂລແມັດມົນທົນ' => 100,
        'ກິໂລແມັດຕາລາງ' => 100,
        'km2' => 100,
    ],

];
