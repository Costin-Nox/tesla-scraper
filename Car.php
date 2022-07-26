<?php
/**
 * Car Object
 * 
 * @author Costin Ghiocel <costinghiocel@gmail.com>
 */

require 'HasAttributes.php';
require 'Db.php';

use Akaunting\Money\Currency;
use Akaunting\Money\Money;

class Car implements \JsonSerializable
{
    use HasAttributes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'last_update';

	protected $guarded      = [];
	protected $visible      = [];
	protected $hidden       = [];
	protected $dbData       = [];
	protected $primaryKey   = 'vin';
	protected $timestamps   = true;
	protected $didSync      = false;
	protected $priceChanged = false;

    protected $db;

    public function __construct($data = null)
    {
        $this->db = Db::get();

        if ($data && is_array($data)) 
        {
        	foreach ($data as $key => $value) {
    			$this->$key = $value;
    		}

			$this->dbData  = $data;
			$this->didSync = true;
        } 
        elseif($data) 
        {
            $this->fill($data);
        }

        if ($this->vin && strlen($this->vin) < 22) {
        	$this->url = "https://www.tesla.com/en_CA/mx/order/{$this->vin}";
        } else {
        	$this->url = 'https://tesla.com';
        }

        $this->syncOriginal();
    }

    /**
     * Dynamically retrieve attributes on the respository.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function sync()
    {
    	if (!empty($this->_id) || $this->didSync) { return; }

		$this->didSync = true;
		$obj           = Self::fetch($this->vin);

    	if (!empty($obj->_id)) {
			$this->_id    = $obj->_id;
			$this->dbData = $obj->toArray();

			if ($obj->price != $this->price) {
				$this->price_history[] = array_pop($obj->price_history);
				$this->price           = $obj->price;
				$this->priceChanged    = true;
			}
    	}
    }

    public function isPriceChanged() : bool
    {
    	return $this->price_history;
    }

    public function isInDb() : bool
    {
    	$this->sync();

    	return !empty($this->_id);
    }


    public function fill(\stdClass $description) 
    {
		$this->vin           = $description->VIN;
		$this->price         = $description->InventoryPrice;
		$this->odometer      = $description->Odometer;
		$this->model         = $description->Model;
		$this->trim          = $description->TrimName;
		$this->color         = array_pop($description->PAINT);
		$this->year          = $description->Year;
		$this->built         = $description->ActualGAInDate;
		$this->type          = $description->TitleStatus;
		$this->status        = 'active';
		$this->sold_on       = null;
		$this->price_history = [['price' => $description->InventoryPrice, 'date' => \Carbon\Carbon::now()]];
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function relationLoaded($key) : bool
    {
        return !empty($this->relations[$key]);
    }

    public function usesTimestamps() : bool
    {
        return empty($this->timestamps) ? false : true;
    }

    public function getVisible() : array
    {
        return $this->visible;
    }

    public function getHidden() : array
    {
        return $this->hidden;
    }

    /**
     * Dynamically set attributes on the respository.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function jsonSerialize()
    {
        return $this->attributesToArray();
    }

    public function toArray()
    {
        return $this->attributesToArray();
    }

    public function delete() : bool
    {
        
    }

    public function save() : Self
    {
    	$this->sync();

    	if ($this->_id && $this->isDirty()) {
			$this->last_update = \Carbon\Carbon::now();
			$result            = $this->db->update($this->toArray());
			$this->syncOriginal();
    	} else {
    		//create
    		//dd($this);
			$this->last_update = \Carbon\Carbon::now();
			$this->created_at  = \Carbon\Carbon::now();
			$result            = $this->db->insert($this->toArray());
			$this->_id         = $result['_id'];
			$this->syncOriginal();
    	}

    	return $this;
    }

    public static function fetch(string $vin) : Self
    {
		$fromDb = Db::get()->findBy(['vin', '=', $vin]);
    	
    	if (count($fromDb)) {
			$data = array_pop($fromDb);
			$car  = new Self($data);

			return $car;
    	}

    	return new Self();
    }

    public static function all() 
    {
    	$objects = Db::get()->findAll();
    	$result = [];

    	foreach($objects as $car) {
    		$result[] = new Self($car);
    	}

    	$result = collect($result);

    	return $result;
    }

    public function getKey() 
    {
        return $this->attributes[$this->primaryKey];
    }

    public function __toString()
    {
    	$string = '';

    	foreach($this->attributes as $key => $val) {
    		if ("price_history" == $key) {
    			$string .= "\t{$key}\n";
    			
    			foreach ($val as $ph) 
    			{
    				$p = new Money($ph['price'], new Currency('CAD'), true);
    				$string .= "\t\t{$p} on {$ph['date']}\n";
    			}
    		}
    		elseif('price' == $key) 
    		{
    			$p = new Money($val, new Currency('CAD'), true);
    			$string .= "\t{$key}:\t\t{$p}\n";
    		} 
    		elseif('_id' == $key) 
    		{

    		} 
    		else 
    		{

    			$tabs = (7 < strlen($key)) ? "\t" : "\t\t";
    			$string .= "\t{$key}:{$tabs}{$val}\n";
    		}
    	}

    	return $string."\t--------------------------\n\n";
    }
}
