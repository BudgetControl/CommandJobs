<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class PlannedEntryTags extends Model
{
    protected $table = 'planned_entries_labels';
    
    protected $hidden = [
        'id',
    ];
}