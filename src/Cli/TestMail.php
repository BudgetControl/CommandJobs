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
            ->addArgument('template', InputArgument::REQUIRED, 'Template name of the mail (recovery-password, signup, budget-exeded, shared-workspace, or any custom template)')
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

        if (!in_array($template, self::ALLOWED_TEMPLATE)) {
            $this->fail('Template not allowed: ' . $template . '. Allowed templates: ' . implode(', ', self::ALLOWED_TEMPLATE));
            return Command::FAILURE;
        }

        $mail = $input->getArgument('mail');

        Log::info('Testing mail template: ' . $template . ' to ' . $mail);

        try {

            switch ($template) {
                case 'recovery-password':
                    Mail::recoveryPassword(
                        [
                            'to' => 'user@example.com',
                            'token' => 'abc123token',
                            'url' => 'https://example.com/reset?token=abc123token',
                            'username' => 'User Test'
                        ]
                    );
                    break;
                case 'signup':
                    Mail::signUp(
                        [
                            'to' => 'newuser@example.com',
                            'token' => 'confirmation123',
                            'url' => 'https://example.com/confirm?token=confirmation123',
                            'username' => 'John Doe'
                        ]
                    );
                    break;
                case 'budget-exeded':
                    Mail::budgetExceeded(
                        [
                            'to' => 'user@example.com',
                            'budget_name' => 'Monthly Budget',
                            'current_amount' => '1200.00',
                            'budget_limit' => '1000.00',
                            'currency' => 'EUR',
                            'username' => 'User Test'
                        ]
                    );
                    break;
                case 'shared-workspace':
                    Mail::sharedWorkspace(
                        [
                            'to' => 'user@example.com',
                            'workspace_name' => 'Team Budget',
                            'shared_by' => 'John Doe',
                            'role' => 'editor',
                            'invitation_url' => 'https://example.com/invite/abc123'
                        ]
                    );
                    break;
                default:
                    $data = [
                        'to' => $mail,
                        'subject' => 'Test Email for ' . $template,
                        'message' => 'This is a test email for template: ' . $template,
                        'user_name' => "Test User",
                        'email' => $mail,
                        'privacy' => true
                    ];
                    Mail::contact($data);
                    break;
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
