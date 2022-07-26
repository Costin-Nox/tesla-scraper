<?php
/**
 * Db Singleton
 * 
 * @author Costin Ghiocel <costinghiocel@gmail.com>
 */


class Db
{
	private $db;

    protected static $instance = null;

	public function __construct()
	{
		$databaseDirectory = __DIR__ . "/teslaDb";
		$this->db          = new \SleekDB\Store("tesla", $databaseDirectory);
		//singleton class
        self::$instance = $this;
	}


    /**
     * Static initializer for singleton.
     *
     * @return this
     */
    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __call($name, $args) 
    {
        if(method_exists($this, $name)) {
            return self::$name(...$args);
        } else { 
            return $this->db->$name(...$args);
        }
    }
	
}