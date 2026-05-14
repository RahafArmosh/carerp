<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device attendance API token
    |--------------------------------------------------------------------------
    |
    | Static token for POST /api/attendance/device/checkin and .../checkout.
    | Send in header X-Attendance-Token, Authorization: Bearer <token>, or body "token".
    |
    */
    'device_api_token' => 'sk-test-1234567890abcdef1234567890abcdef',

];
