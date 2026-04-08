<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Overdue Fee Per Day
    |--------------------------------------------------------------------------
    | The fee charged per day for overdue borrowed materials.
    | Set OVERDUE_FEE_PER_DAY in your .env file to configure this value.
    | Default: 10 (PHP 10.00 per day)
    */
    'overdue_fee_per_day' => (float) env('OVERDUE_FEE_PER_DAY', 10),
];
