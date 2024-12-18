<?php

namespace Budgetcontrol\jobs\Cli;

use Throwable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Budgetcontrol\jobs\Facade\Mail;
use BudgetcontrolLibs\Mailer\View\BudgetExceededView;
use BudgetcontrolLibs\Mailer\View\RecoveryPasswordView;
use BudgetcontrolLibs\Mailer\View\SignUpView;

/**
 * Class ActivatePlannedEntry
 *
 * This class extends the ActivatePlannedEntryJob class and is responsible for activating a planned entry.
 */
class TestMail extends JobCommand
{
    protected string $command = 'test:mail';

    const ALLOWED_TEMPLATE = [
        'recovery-password',
        'signup',
        'budget-exeded',
    ];

    public function configure()
    {
        $this->setName($this->command)
            ->setDescription('Test template mail')
            ->addArgument('template', InputArgument::REQUIRED, 'Template name of the mail')
            ->addArgument('mail', InputArgument::REQUIRED, 'Email address to send the mail')
            ->setHelp("This command allows you to test a template mail");
    }

    /**
     * Executes the command to activate a planned entry.
     *
     * @param InputInterface $input The input interface.
     * @param OutputInterface $output The output interface.
     * @return int The exit code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $template = $input->getArgument('template');
        $this->output = $output;

        if(!in_array($template, self::ALLOWED_TEMPLATE)) {
            $this->fail('Template not allowed: ' . $template . '. Allowed templates: ' . implode(', ', self::ALLOWED_TEMPLATE));
            return Command::FAILURE;
        }

        $mail = $input->getArgument('mail');

        Log::info('Testing mail template: ' . $template . ' to ' . $mail);
        
        switch ($template) {
            case 'recovery-password':
                $view = $this->dummyRecoveryPasswordDataMail();
                break;
            case 'signup':
                $view = $this->dymmySignUpDataMail();
                break;
            case 'budget-exeded':
                $view = $this->dummyBudgetExededDataMail();
                break;
        }

        try {
            Mail::send($mail, $template, $view);
            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return Command::FAILURE;
        }

    }

    /**
     * Sends a dummy budget exceeded data mail.
     *
     * @return BudgetExceededView The budget exceeded view.
     */
    private function dummyBudgetExededDataMail(): BudgetExceededView
    {
        $view = new BudgetExceededView();
        $view->setMessage('Budget exceeded');
        $view->setTotalSPent('$ 100,00');
        $view->setSpentPercentage('100%');
        $view->setPercentage('100%');
        $view->setClassName('bg-red-600');
        $view->setUserName("John Doe");
        $view->setUserEmail('foo@bar.com');

        return $view;
    }

    /**
     * Generates dummy sign-up data mail.
     *
     * @return SignUpView The generated sign-up data mail.
     */
    private function dymmySignUpDataMail(): SignUpView
    {
        $view = new SignUpView();
        $view->setUserName("John Doe");
        $view->setUserEmail('foo@bar.com');
        $view->setConfirmLink('http://localhost:8000/confirm/123456');

        return $view;
    }

    /**
     * Generates dummy recovery password data mail.
     *
     * @return RecoveryPasswordView The recovery password view.
     */
    private function dummyRecoveryPasswordDataMail(): RecoveryPasswordView
    {
        $view = new RecoveryPasswordView();
        $view->setUserName("John Doe");
        $view->setUserEmail('foo@bar.com');
        $view->setLink('http://localhost:8000/recovery/123456');

        return $view;
    }

}