<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

(new Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__.'/../.env');

$kernel = new App\Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
