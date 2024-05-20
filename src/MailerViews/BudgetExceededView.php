<?php

namespace Budgetcontrol\jobs\MailerViews;

use MLAB\SdkMailer\View\Mail;

/**
 * Class BudgetExceededView
 * Represents a view for a budget exceeded email.
 */
class BudgetExceededView extends Mail
{
    private string $message;
    private string $name;
    private string $totalSpent;
    private string $budgetName;
    private string $spentPercentage;
    private string $percentage;
    private string $className = 'bg-red-200';

    public function __construct()
    {
        parent::__construct(__DIR__.'/../../resources/template/');
    }

    public function view() :string
    {
        return $this->render([
            'message' => $this->message,
            'name' => $this->name,
            'totalSpent' => $this->totalSpent,
            'budgetName' => $this->budgetName,
            'spentPercentage' => $this->spentPercentage,
            'percentage' => $this->percentage,
            'className' => $this->className
        ]);
    }

    /**
     * Sets the message for the budget exceeded email.
     *
     * @param string $message The message to set.
     * @return self
     */
    public function setMessage(string $budgetName): self
    {   
        $this->budgetName = $budgetName;
        $this->message = "Your budget $budgetName, has been exceeded. Please review your expenses and make necessary adjustments.";

        return $this;
    }

    /**
     * Set the value of name
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the value of totalSPent
     *
     * @param string $totalSPent
     *
     * @return self
     */
    public function setTotalSpent(string $totalSpent): self
    {
        $this->totalSpent = $totalSpent;

        return $this;
    }


    /**
     * Set the value of spentPercentage
     *
     * @param string $spentPercentage
     *
     * @return self
     */
    public function setSpentPercentage(string $spentPercentage): self
    {
        $this->spentPercentage = $spentPercentage;

        return $this;
    }


    /**
     * Set the value of percentage
     *
     * @param string $percentage
     *
     * @return self
     */
    public function setPercentage(string $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }


    /**
     * Set the value of className
     *
     * @param string $className
     *
     * @return self
     */
    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }
}
