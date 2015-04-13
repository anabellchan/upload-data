<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;

class Item extends Eloquent implements UserInterface, RemindableInterface {

	public $timestamps = true;

	protected $fillable = ['barcode', 'kind_id', 'category_id', 'owner', 'current_location', 'serial_number',
                            'po_number', 'cfi', 'requisitioner', 'received', 'warranty_until', 'calibration_until',
                            'ip', 'mac', 'boothost', 'machine_name', 'asset_number', 'created_on', 'updated_on',
                            'use', 'status', 'individual_price', 'quantity', 'total_price', 'manufacturer',
                            'slots', 'features', 'user', 'normal_location', 'model', 'description_too', 'color_of_surface'];

    // protected $fillable = Schema::getColumnListing('items');   -- MAYBE TRY THIS LINE TO KEEP FILLABLE LIST DYNAMIC --



	use UserTrait, RemindableTrait;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'items';

	public static $messages;

	//protected $hidden = array('password', 'remember_token');

	public static $rules = [
		'kind_id'=>'required|integer|digits_between:0,10',
		'category_id'=>'required|integer|digits_between:0,10'
    ];


	public function isValid()
	{
//		$v = Validator::make($dataToBeValidated, static::$rules);
        $v = Validator::make($this->attributes, static::$rules);

		if($v->passes())
		{
			return true;
		}

		static::$messages = $v->messages();
		return false;
	}


}
