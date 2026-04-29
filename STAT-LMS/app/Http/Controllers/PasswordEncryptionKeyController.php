<?php

namespace App\Http\Controllers;

use App\Services\PasswordEncryptionService;
use Illuminate\Http\JsonResponse;

class PasswordEncryptionKeyController extends Controller
{
    public function __invoke(PasswordEncryptionService $service): JsonResponse
    {
        return response()
            ->json(['public_key' => $service->publicKeyPem()])
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600')
            ->header('Vary', 'Accept-Encoding');
    }
}
