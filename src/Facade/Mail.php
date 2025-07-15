<?php
namespace Budgetcontrol\jobs\Facade;

use Illuminate\Support\Facades\Facade;
use Budgetcontrol\SdkMailer\Service\Mail as BaseMail;

/**
 * Class Mail
 *
 * This class is a facade for the Mail class.
 * @see \Budgetcontrol\jobs\Service\MailerService
 * 
 * @method static void budgetExceeded(array $data)
 * @method static void contact(array $data)
 */

class Mail extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return 'mail';
    }
}