<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Entry extends Model
{
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
        return $this->belongsToMany(Tags::class, 'entry_labels', 'entry_id', 'labels_id');
    }
}