<?php

declare(strict_types=1);

namespace App\Controller\ActivityPub;

use Symfony\Component\HttpFoundation\JsonResponse;

class HostMetaController
{
    public function __construct()
    {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse();
    }
}
