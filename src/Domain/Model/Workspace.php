<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $hidden = [
        'created_at',
        'updated_at',
        'id'
    ];

}