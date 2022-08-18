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
 * Load env
 * @var [type]
 */
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

/**
 * Run
 */
$tesla              = new TeslaScraper();
$carsAvailable      = $tesla->scrape();
$changes            = $tesla->proccessData($carsAvailable);
$changes['sold']    = $tesla->getSold($carsAvailable);
$changes['history'] = $tesla->getHistory();
$hasChanges         = false;


/**
 * Gen HTML
 * @var string
 */
$now    = \Carbon\Carbon::now('America/Vancouver');
$html = "<br><br><h2>Updates " . $now->toDayDateTimeString() . "</h2><br><br>";
foreach ($changes as $type => $cars) 
{
    if (count($cars)) {
        $hasChanges = true;
        $html .= "<br><h3>{$type}</h3><br>";
        $html .= "<table>";
        foreach($cars as $c) {
            $html .= "<tr><td><img src='{$c->imageUrl}'></img><td><td>";
            $html .= "<pre>";
            $html .= $c;
            $html .= "</pre></td></tr>";
        }
        $html .= "</table><br>";
    }
    
}
$html .= "<br><br><h2>Cars Available</h2><br><br>";
$html .= "<table>";
foreach($carsAvailable as $c) {
    $html .= "<tr><td><img src='{$c->imageUrl}'></img><td><td>";
    $html .= "<pre>";
    $html .= $c;
    $html .= "</pre></td></tr>";
}
$html .= "</table><br>";

/**
 * Send E-mail
 */
if ($hasChanges)
{
    $sendTo = explode(',',getenv('EMAIL_TO'));
    $email  = new \SendGrid\Mail\Mail();

    $email->setFrom(getenv('EMAIL_FROM'), "Tesla Scraper");
    $email->setSubject("Tesla Store Updates");
    foreach($sendTo as $sendAddr) {
        $email->addTo($sendAddr, "User");
    }
    $email->addContent(
        "text/html", $html
    );
    $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
    try {
        $response = $sendgrid->send($email);
        print $response->statusCode() . "\n";
        print_r($response->headers());
        print $response->body() . "\n";
    } catch (Exception $e) {
        echo 'Caught exception: '. $e->getMessage() ."\n";
    }
} else {
    _log("No changes!");
}

?>