<?php declare(strict_types=1);
namespace Budgetcontrol\jobs\Service;

use Illuminate\Support\Facades\Log;

class MailerService extends HttpService {

    /**
     * Sends notification when a budget has been exceeded.
     * 
     * @param array $data An associative array containing budget exceeding information
     * @return void
     */
    public function budgetExceeded(array $data): void
    {
        $url = '/notify/email/budget/exceeded';
        $this->invoke('POST', $url, $data);
    }

    public function contact(array $data): void
    {
        $url = '/notify/email/contact';
        $this->invoke('POST', $url, $data);
    }

    public function sharedWorkspace(array $data): void
    {
        $url = '/notify/email/workspace/share';
        $this->invoke('POST', $url, $data);
    }

    public function recoveryPassword(array $data): void
    {
        $url = '/notify/email/auth/recovery-password';
        $this->invoke('POST', $url, $data);
    }

    public function signUp(array $data): void
    {
        $url = '/notify/email/auth/sign-up';
        $this->invoke('POST', $url, $data);
    }

}