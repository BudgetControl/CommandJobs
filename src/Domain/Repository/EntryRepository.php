<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class EntryRepository {

    /**
     * Retrieves the entry of the current time.
     *
     * @return Entry|null The entry of the current time, or null if not found.
     */
    public static function entryOfCurrentTime()
    {
        $date = Carbon::now()->toAtomString();
        $query = "SELECT id FROM entries WHERE DATE(date_time) = '$date' AND deleted_at IS NULL";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        return $results;
    
    }

}