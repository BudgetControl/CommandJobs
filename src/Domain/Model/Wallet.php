<?php
namespace Budgetcontrol\jobs\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'wallets';

    protected $hidden = [
        'id',
    ];
}