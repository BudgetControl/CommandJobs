<?php
namespace Budgetcontrol\jobs\Facade;

use Illuminate\Support\Facades\Facade;
use Budgetcontrol\SdkMailer\Service\Mail as BaseMail;

/**
 * Class Mail
 *
 * This class is a facade for the Mail class.
 * @see \Budgetcontrol\jobs\Service\CacheService
 *
 * @method static void clear()
 * @method static void invokeInvalidation(string $workspaceUuid, string $pattern)
 */

class Cache extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'cache-http';
    }
}