<?php
namespace Budgetcontrol\jobs\Domain\Repository;

use Budgetcontrol\jobs\Domain\Model\PlannedEntry;
use Illuminate\Database\Capsule\Manager as DB;

class PlannedEntryRepository {

    public static function plannedEntriesFromDateTime(string $date)
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
    public static function plannedEntryOfTheMonth()
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

    /**
     * Retrieves the tags associated with a specific entry.
     *
     * @param int $entryId The ID of the entry.
     * @return array The array of tags associated with the entry.
     */
    protected static function tags(int $entryId): array
    {
        $query = "select tags.*, pe_tags.planned_entry_id as entry_id from labels as tags
        right join planned_entry_labels as pe_tags on tags.id = pe_tags.labels_id
        where pe_tags.planned_entry_id = $entryId AND tags.deleted_at IS NULL;";
        $results = DB::select($query);

        if(empty($results)) {
            return [];
        }

        return $results;
    }
}