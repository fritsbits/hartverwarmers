<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Icon Allowlist
    |--------------------------------------------------------------------------
    |
    | Curated subset of Lucide icon names relevant to elderly care activities.
    | The AI agent picks from this list only — prevents inappropriate icons.
    |
    */

    'allowlist' => [
        // Music & performance
        'music', 'mic', 'guitar', 'piano', 'headphones', 'radio', 'disc-3',

        // Nature & outdoors
        'flower-2', 'trees', 'sun', 'cloud-sun', 'bird', 'leaf', 'sprout', 'mountain',

        // Food & cooking
        'cooking-pot', 'utensils', 'cake', 'apple', 'salad', 'coffee', 'wine', 'ice-cream-cone',

        // Arts & crafts
        'palette', 'scissors', 'paintbrush', 'pen-tool', 'brush', 'stamp',

        // Games & puzzles
        'puzzle', 'dice-5', 'trophy', 'target', 'gamepad-2', 'spade',

        // Health & movement
        'heart-pulse', 'footprints', 'bike', 'dumbbell', 'smile', 'hand',

        // Social & celebration
        'party-popper', 'gift', 'heart', 'users', 'handshake', 'message-circle',

        // Learning & memory
        'brain', 'book-open', 'lightbulb', 'graduation-cap', 'newspaper', 'file-question',

        // Seasons & holidays
        'snowflake', 'egg', 'star', 'flame', 'umbrella', 'candy-cane',

        // Animals
        'dog', 'cat', 'fish', 'rabbit', 'squirrel',

        // Home & domestic
        'home', 'armchair', 'lamp', 'shirt', 'bath',

        // Travel & places
        'camera', 'map', 'globe', 'compass', 'train', 'car',

        // Time & calendar
        'clock', 'calendar', 'hourglass',

        // General
        'flag', 'sparkles', 'wand-2', 'megaphone', 'tv', 'clapperboard',
        'drama', 'church', 'crown', 'gem', 'feather', 'ribbon',

        // Fallback
        'file-text',
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Palette
    |--------------------------------------------------------------------------
    |
    | 6 deterministic colors for icon discs, assigned by fiche ID % 6.
    | Colors 0-3 match the user avatar palette. 4-5 are additions.
    |
    */

    'colors' => [
        ['bg' => '#FDF3EE', 'text' => '#E8764B'],  // 0: orange
        ['bg' => '#E8F6F8', 'text' => '#3A9BA8'],  // 1: teal
        ['bg' => '#FEF6E0', 'text' => '#B08A22'],  // 2: yellow
        ['bg' => '#F3E8F3', 'text' => '#9A5E98'],  // 3: purple
        ['bg' => '#E8F5E9', 'text' => '#4A8C5C'],  // 4: green
        ['bg' => '#FDE8EC', 'text' => '#C0506A'],  // 5: rose
    ],
];
