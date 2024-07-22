<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Budgetcontrol\Library\Entity\Budget as BudgetDefinition;

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
        $this->attributes['configuration'] = json_encode($value);
    }

    public function setNotificationAttribute($value)
    {
        $this->attributes['notification'] = (bool) $value;
    }

    public function setConfigurationEmails($value)
    {
        $this->attributes['emails'] = explode(',',$value);
    }

    public function map(): BudgetDefinition
    {
        return BudgetDefinition::map($this);
    }
    

}