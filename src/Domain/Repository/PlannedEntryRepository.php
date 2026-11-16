<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Budgetcontrol\jobs\Domain\Model\PlannedEntry;
use Budgetcontrol\Library\Model\PlannedEntry as ModelPlannedEntry;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class PlannedEntryRepository extends Repository {

    public function plannedEntriesFromDateTime(string $date)
    {
         $query = "SELECT * FROM planned_entries WHERE date_time::date = CURRENT_DATE AND deleted_at IS NULL;";
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
        $plannedEntry = ModelPlannedEntry::with('labels')
            ->where('date_time', '<=', Carbon::now()->endOfMonth())
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('end_date_time')
                    ->orWhereRaw('DATE(date_time) <= DATE(end_date_time)');
            })
            ->get();

        if ($plannedEntry->isEmpty()) {
            return null;
        }

        return $plannedEntry;
    }
}
