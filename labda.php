<?php
// Inizializza il logger
require_once __DIR__ . '/bootstrap/app.php';

// Definiamo la funzione handler che Bref invocherà
return function ($event) use ($logger) {
    $command = env('COMMAND');
    $logger->info('Executing command: ' . $command);

    $input = new Symfony\Component\Console\Input\StringInput($command);
    $output = new Symfony\Component\Console\Output\BufferedOutput();

    if(!$command) {
        $output->writeln('<error>No command specified. Please set the COMMAND environment variable.</error>');
        $logger->error('No command specified');
        return ['status' => 'error', 'message' => 'No command specified'];
    }

    /**
     * Create a new instance of the Symfony Console Application.
     */
    $application = new \Symfony\Component\Console\Application('BudgetControl CLI', '1.0.0');

    /**
     * Register all commands
     */
    require_once __DIR__."/config/cli-commands.php";

    $application->setAutoExit(false);

    try {
        $exitCode = $application->run($input, $output);
        $logger->info('Command executed with exit code: ' . $exitCode);
        return [
            'status' => $exitCode === 0 ? 'success' : 'error',
            'output' => $output->fetch(),
            'exitCode' => $exitCode
        ];
    } catch (\Exception $e) {
        $logger->error('Error executing command: ' . $e->getMessage());
        $output->writeln('<error>' . $e->getMessage() . '</error>');
        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'output' => $output->fetch()
        ];
    }
};