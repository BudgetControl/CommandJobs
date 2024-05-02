<?php

require_once __DIR__ . '/../bootstrap/app.php';

$application = new \Symfony\Component\Console\Application();
$application->add(new \Budgetcontrol\jobs\Cli\ActivatePlannedEntry());
$application->add(new \Budgetcontrol\jobs\Cli\AddPlannedEntry());
$application->run();