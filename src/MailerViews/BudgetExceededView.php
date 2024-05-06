<?php

namespace Budgetcontrol\jobs\MailerViews;

use Budgetcontrol\SdkMailer\View\Mail;

/**
 * Class BudgetExceededView
 * Represents a view for a budget exceeded email.
 */
class BudgetExceededView extends Mail
{
    private string $message = '';
    private string $simpleMessage = '';

    public function view() :string
    {
        return $this->render([
            'message' => $this->message,
            'simple_message' => $this->simpleMessage
        ]);
    }

    /**
     * Sets the message for the budget exceeded email.
     *
     * @param string $message The message to set.
     * @return void
     */
    public function setMessage(string $budgetName): void
    {
        $this->message = "";
    }

    /**
     * Sets the simple message for the budget exceeded email.
     *
     * @param string $simpleMessage The simple message to set.
     * @return void
     */
    public function setSimpleMessage(string $budgetName): void
    {
        $this->simpleMessage = "Your budget $budgetName, has been exceeded. Please review your expenses and make necessary adjustments.";
    }
}
