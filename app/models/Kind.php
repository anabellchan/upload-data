<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class Kind extends Eloquent implements UserInterface, RemindableInterface {

	public $timestamps = false;

	protected $fillable = ['name', 'description'];

	use UserTrait, RemindableTrait;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'kinds';

	public static $messages;

	//protected $hidden = array('password', 'remember_token');

	public static $rules = [
		'name'=>'required'
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
