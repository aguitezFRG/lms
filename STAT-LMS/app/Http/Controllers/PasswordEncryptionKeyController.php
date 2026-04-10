<?php

namespace App\Http\Controllers;

use App\Services\PasswordEncryptionService;
use Illuminate\Http\JsonResponse;

class PasswordEncryptionKeyController extends Controller
{
    public function __invoke(PasswordEncryptionService $service): JsonResponse
    {
        return response()->json(['public_key' => $service->publicKeyPem()]);
    }
}
