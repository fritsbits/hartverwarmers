<?php

return [
    'thresholds' => [
        'memory_percent' => ['warning' => 70, 'critical' => env('HEALTH_MEMORY_THRESHOLD', 85)],
        'disk_percent' => ['warning' => 80, 'critical' => env('HEALTH_DISK_THRESHOLD', 90)],
        'load_average' => ['warning' => 1.0, 'critical' => env('HEALTH_LOAD_THRESHOLD', 2.0)],
    ],
    'alert_cooldown_minutes' => env('HEALTH_ALERT_COOLDOWN', 60),
];
