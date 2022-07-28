<?php
/**
 * Car Object -- Repository-ish
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
        //bind singleton db instance to obj (turns this into a repository-ish)
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

        if ($this->vin) {
        	$this->url = "https://www.tesla.com/en_CA/{$this->model}/order/{$this->vin}";
        }

        if ($this->optionCodes) {
            $view = ($this->type == 'USED') ? 'STUD_3QTR_V2' : 'FRONT34';
            $this->imageUrl = 'https://static-assets.tesla.com/configurator/compositor?&bkba_opt=2&view='.$view.'&size=450&model='.$this->model.'&options='.$this->optionCodes.'&crop=1400,850,300,130&scalemode=centered';
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

    /**
     * Things change, we're not using a relational DB, need to do this.
     * 
     * @return [type] [description]
     */
    public function sync()
    {
    	if (!empty($this->_id) || $this->didSync) { return; }

		$this->didSync = true;
		$obj           = Self::fetch($this->vin);

        //did we have a hit in the db?
    	if (!empty($obj->_id)) {
			$this->_id    = $obj->_id;
			$this->dbData = $obj->toArray();

            //did the price change? we only care if it went down, their api is cached and it will fluctuate randomly for a while..
			if ($obj->price > $this->price) 
            {
                $this->price_history = array_merge($obj->price_history, $this->price_history);
                $this->priceChanged  = true;
                $this->save(true);		
			} 
            //make sure we dont override the db history..
            else 
            {
                $this->price_history = $obj->price_history;
            }
    	}
    }

    public function isPriceChanged() : bool
    {
    	return $this->priceChanged;
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
        $this->optionCodes   = $description->OptionCodeList;
		$this->sold_on       = null;
		$this->price_history = [['price' => $description->InventoryPrice, 'date' => \Carbon\Carbon::now('America/Vancouver')]];
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

    public function save(?bool $nosync = null) : Self
    {
    	if (!$nosync)
    	{
    		$this->sync();
    	}

    	if ($this->_id && $this->isDirty()) {
			$this->last_update = \Carbon\Carbon::now('America/Vancouver');
			$result            = $this->db->update($this->toArray());
			$this->syncOriginal();
    	} else {
    		//create
    		//dd($this);
			$this->last_update = \Carbon\Carbon::now('America/Vancouver');
			$this->created_at  = \Carbon\Carbon::now('America/Vancouver');
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

    /**
     * Pretty output when cast to string.
     *
     * @return string [description]
     */
    public function __toString()
    {
    	$string = '';

    	foreach($this->attributes as $key => $val) 
    	{
            $tabs = (7 < strlen($key)) ? "\t" : "\t\t";

    		switch ($key) {
    			case 'price_history':
    				$string .= "\t{$key}\n";
    			
	    			foreach ($val as $ph) 
	    			{
                        $p      = new Money($ph['price'], new Currency('CAD'), true);
                        $date   = new \Carbon\Carbon($ph['date']);
                        $date   = $date->toFormattedDateString();
                        $string .= "\t\t{$p} on {$date}\n";
	    			}
    				break;
    			case 'price':
    				$p = new Money($val, new Currency('CAD'), true);
    				$string .= "\t{$key}:\t\t{$p}\n";

    				break;
    			case 'odometer':
	    			$string .= "\t{$key}:{$tabs}{$val} km\n";
	    			break;
    			case 'trim':
    			case 'color':
    			case 'year':
    			case 'type':
    			case 'status':
    			case 'sold_on':
    			case 'url':
    				$string .= "\t{$key}:{$tabs}{$val}\n";
    				break;
    		}
    	}

    	return $string."\t__________________________________________________________\n\n";
    }
}
