<?php

use App\Enums\ActivityDimension;
use App\Enums\Guidance;

return [
    'guidance' => [
        Guidance::ACTIVE_INDEPENDENT->value => [
            'title'       => 'Actief onafhankelijk',
            'description' => 'Kunnen hun dag zelf invulling geven',
        ],
        Guidance::ACTIVE_PARTICIPATOR->value => [
            'title'       => 'Actief deelnemend',
            'description' => 'Nemen deel aan activiteiten. Kiezen zelf of ze willen deelnemen aan bepaalde activiteiten. Kunnen zelf hun dag invullen',
        ],
        Guidance::ACTIVE_PARTICIPATOR_DEPENDENT->value => [
            'title'       => 'Actief mits ondersteuning',
            'description' => 'Hebben een ondersteunende structuur nodig. Kiezen zelf of ze deelnemen aan activiteiten. Kunnen zelf geen invulling geven aan hun dag',
        ],
        Guidance::PASSIVE_PARTICIPATOR->value => [
            'title'       => 'Belevingsgroep',
            'description' => 'Hebben cognitieve / fysieke / sociale / psychische problemen (bv. dementie). Hebben een beperkt concentratievermogen. Kunnen zelf geen invulling geven aan hun dag',
        ],
        Guidance::PASSIVE_DEPENDENT->value => [
            'title'       => 'Zorggroep',
            'description' => 'Hebben individuele benadering nodig. Zijn weinig mobiel (liggen of zitten een groot deel van de tijd). Hebben ernstige fysieke en/of cognitieve problemen',
        ],
    ],

    'activitydimension' => [
        ActivityDimension::PERSONAL->value => [
            'title' => 'Persoonlijk',
        ],
        ActivityDimension::SOCIAL->value => [
            'title' => 'Sociaal',
        ],
        ActivityDimension::COMMUNAL->value => [
            'title' => 'Maatschappelijk',
        ],
    ],
];
