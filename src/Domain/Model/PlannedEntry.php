<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class PlannedEntry extends Model
{
    protected $table = 'planned_entries';

    protected $fillable = [
        'id',
        'description',
        'amount',
        'date_time',
        'period',
        'account_id',
        'confirmed',
        'planned',
        'created_at',
        'updated_at',
        'deleted_at',
        'workspace_id'
    ];

    protected $hidden = [
        'id',
    ];

    //relation many to many with planned entry tags
    public function tags()
    {
        return $this->belongsToMany(PlannedEntryTags::class, 'planned_entry_labels', 'planned_entry_id', 'labels_id');
    }
}