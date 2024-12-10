<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class EntryRepository extends Repository {

    /**
     * Retrieves the entry of the current time.
     *
     * @return Entry|null The entry of the current time, or null if not found.
     */
    public function entryOfCurrentTime()
    {
        $query = "SELECT id 
            FROM entries 
            WHERE DATE(date_time) = CURRENT_DATE 
            AND deleted_at IS NULL 
            AND planned = true;
            ;";
        $results = DB::select($query);

        if(empty($results)) {
            return null;
        }

        return $results;
    
    }

}
