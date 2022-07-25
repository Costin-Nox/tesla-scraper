<?php
/**
 * Bootstrap and run.
 * 
 * @author  Costin Ghiocel <me@costingcl.ca>
 */

require 'vendor/autoload.php';
require 'Colors.php';
require 'TeslaScraper.php';

/**
 * This is needed in other objects too.
 * @param  string    $msg     [description]
 * @param  bool|null $isError [description]
 * @return [type]             [description]
 */
function _log(string $msg, ?bool $isError = null) {
    if ($isError)
        echo Colors::getColoredString("[ERROR] {$msg} \n", 'red');
    else
        echo Colors::getColoredString("[INFO] {$msg} \n", 'green');
}

/**
 * dump and die
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
function dd($data) {
	dump($data);
	die;
}


/**
 * Run
 */
$tesla = new TeslaScraper();
$tesla->scrape();




?>