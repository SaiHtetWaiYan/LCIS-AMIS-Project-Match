<?php

return [

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
