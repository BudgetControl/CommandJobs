<?php

require_once __DIR__ . '/../bootstrap/app.php';

/**
 * Create a new instance of the Symfony Console Application.
 */
$application = new \Symfony\Component\Console\Application();

/**
 * Add the ActivatePlannedEntry command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\ActivatePlannedEntry());

/**
 * Add the AddPlannedEntry command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\AddPlannedEntry());

/**
 * Add the AlertBudget command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\AlertBudget());

/**
 * Add the BudgetPeriodChange command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\BudgetPeriodChange());

/**
 * Add the ManageCreditCardsWallet command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\ManageCreditCardsWallet());

$application->run();