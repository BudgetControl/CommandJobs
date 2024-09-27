<?php
namespace Budgetcontrol\jobs\Facade;

use Illuminate\Support\Facades\Facade;
/**
 * Crypt Facade
 * @method static string encrypt(string $data)
 * @method static string decrypt(string $data)
 * 
 * @see BudgetcontrolLibs\Crypt\Service\CryptableService;
 */

class Crypt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'crypt';
    }
}