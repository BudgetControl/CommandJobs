<?php
declare(strict_types=1);

namespace Budgetcontrol\jobs\Facade;

use Budgetcontrol\Connector\Client\BudgetClient;
use Illuminate\Support\Facades\Log;

final class BudgetControlClient
{
    private array $result;

    private function __construct(\Budgetcontrol\Connector\Model\Response $result)
    {
        if($result->getStatusCode() !== 200 || $result->getStatusCode() !== 201 ) {
            Log::error('Error calling BudgetControlClient');
        }

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
    public function getResult(): array
    {
        return $this->result;
    }
}