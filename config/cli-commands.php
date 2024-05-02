<?php

$application = new \Symfony\Component\Console\Application();
$application->add(new \Budgetcontrol\jobs\Cli\ActivatePlannedEntry());
$application->add(new \Budgetcontrol\jobs\Cli\AddPlannedEntry());
$application->run();