<?php
/**
 * Bootstrap and run.
 * 
 * @author Costin Ghiocel <costinghiocel@gmail.com>
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
$tesla         = new TeslaScraper();
$carsAvailable = $tesla->scrape();
$changes       = $tesla->proccessData($carsAvailable);
$sold          = $tesla->getSold($carsAvailable);

/*
$html = "<br><br><h2>Cars Available</h2><br><br>";
foreach($carsAvailable as $c) {
	$html .= "<pre>" . $c->
}*/
foreach ($carsAvailable as $c) {
	echo $c;
}
_log('Changes:');
dump($changes);
_log('Cars Sold:');
dump($sold);




?>