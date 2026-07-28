<?php

return [
    'level_1_access_roles' => explode(',', (string) env('LEVEL_1_ACCESS_ROLES', '')),
    'level_2_access_roles' => explode(',', (string) env('LEVEL_2_ACCESS_ROLES', '')),
    'level_3_access_roles' => explode(',', (string) env('LEVEL_3_ACCESS_ROLES', '')),
];
