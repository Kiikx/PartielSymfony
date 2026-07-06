<?php

namespace App\Service\Dashboard;

final readonly class DashboardAlert
{
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_DANGER = 'danger';

    public function __construct(
        public string $level,
        public string $message,
    ) {
    }
}
