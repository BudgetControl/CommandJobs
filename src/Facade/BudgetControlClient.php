<?php
namespace Budgetcontrol\jobs\Facade;

use Budgetcontrol\Connector\Client\BudgetClient;

final class BudgetControlClient
{
    private $result;

    private function __construct(\Budgetcontrol\Connector\Model\Response $result)
    {
        $this->result = $result->getBody();
    }

    public static function budgetStats(int $wsId): self
    {
        $budgetClient = new BudgetClient();
        return new self($budgetClient->call('/budgets/stats', $wsId));
    }

    /**
     * Get the value of result
     */
    public function getResult()
    {
        return $this->result;
    }
}