<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class BudgetRepository {

    public static function findExceededBudget()
    {
        $query = "SELECT * FROM budgets where notification = 1 and deleted_at is null;";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        return $results;
    }

}