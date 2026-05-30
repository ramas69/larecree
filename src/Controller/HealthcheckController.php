<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthcheckController extends AbstractController
{
    #[Route('/healthcheck', name: 'app_healthcheck', methods: ['GET'])]
    public function check(Connection $connection): JsonResponse
    {
        $checks = [
            'app' => true,
            'db'  => false,
        ];

        try {
            $connection->executeQuery('SELECT 1');
            $checks['db'] = true;
        } catch (\Throwable) {
            // db down → response 503
        }

        $ok = $checks['app'] && $checks['db'];

        return new JsonResponse([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'time'   => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], $ok ? 200 : 503);
    }
}
