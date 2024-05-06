<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'uuid',
        'budget',
        'configuration',
        'notification',
        'workspace_id',
        'emails'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'id'
    ];

    public function setConfigurationAttribute($value)
    {
        $this->attributes['configuration'] = json_decode($value);
    }

    public function setConfigurationNotification($value)
    {
        $this->attributes['notification'] = (bool) $value;
    }

    public function setConfigurationEmails($value)
    {
        $this->attributes['emails'] = explode(',',$value);
    }
    

}