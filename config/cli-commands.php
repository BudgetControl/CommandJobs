<?php



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

/**
 * Add the TestMail command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\TestMail());

/**
 * Add the Demo data command to the appliation.
 */
$application->add(new \Budgetcontrol\jobs\Cli\InstallDemoData());

/**
 *  Clear database command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\ClearDatabase());

/**
 *  Install the base data for the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\PrepareDatabase());

/**
 *  Extract keywords from entries command to the application.
 */
$application->add(new \Budgetcontrol\jobs\Cli\ExtractKeywordFromEntries());
