<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Budgetcontrol\jobs\Domain\Model\PlannedEntry;
use Illuminate\Database\Capsule\Manager as DB;

class PlannedEntryRepository extends Repository {

    public function plannedEntriesFromDateTime(string $date)
    {
        $query = "SELECT * FROM planned_entries WHERE DATE(date_time) = '$date' AND deleted_at IS NULL";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        foreach($results as $result) {
            $result->tags = self::tags($result->id);
        }

        return $results;
    
    }

    /**
     * Retrieves the planned entry of the month.
     *
     * @return mixed The planned entry of the month.
     */
    public function plannedEntryOfTheMonth()
    {
        $query = "SELECT * FROM planned_entries WHERE MONTH(date_time) = MONTH(CURRENT_DATE()) AND deleted_at IS NULL";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        foreach($results as $result) {
            $result->tags = self::tags($result->id);
        }

        return $results;
    }
}