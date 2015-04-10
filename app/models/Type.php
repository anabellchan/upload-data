<?php
/**
 * Created by PhpStorm.
 * User: Anabell
 * Date: 2015-04-10
 * Time: 2:25 PM
 */

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class Type extends Eloquent implements UserInterface, RemindableInterface {

    public $timestamps = true;

    protected $fillable = ['name', 'description'];

    // protected $fillable = Schema::getColumnListing('items');   -- MAYBE TRY THIS LINE TO KEEP FILLABLE LIST DYNAMIC --



    use UserTrait, RemindableTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'types';

    public static $messages;

    //protected $hidden = array('password', 'remember_token');

    public static $rules = [
        'name'=>'max:255'
    ];

    public static function isValid($dataToBeValidated)
    {
        $v = Validator::make($dataToBeValidated, static::$rules);

        if($v->passes())
        {
            return true;
        }

        static::$messages = $v->messages();
        return false;
    }

}
