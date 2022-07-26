<?php
/**
 * tesla scraper
 * 
 * @author Costin Ghiocel <costinghiocel@gmail.com>
 */
require 'Car.php';

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Tightenco\Collect\Support\Collection;

Class TeslaScraper
{
    protected $client;
    protected $dbCars;

    public function __construct() {
        $this->client  = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://www.tesla.com/inventory/api/v1/',
            // pages take a while sometimes....
            'timeout'  => 10.0,
        ]);
    }

    public function getDbCars() 
    {
        if (!empty($this->dbCars)) { return $this->dbCars; }

        $this->dbCars = Car::all();

        return $this->dbCars;
    }

    public function scrape() : array
    {
        $searches = [
            //'newModelS'  => getenv('NEW_MS'),
            'usedModelS' => getenv('USED_MS'),
            'newModel3'  => getenv('NEW_M3'),
            'usedModel3' => getenv('USED_M3'),
            'newModelX'  => getenv('NEW_MX'),
            'usedModelX' => getenv('USED_MX'),
            'newModelY'  => getenv('NEW_MY'),
            'usedModelY' => getenv('USED_MY')
        ];

        $result = [];
        foreach ($searches as $name => $search) 
        {
            _log('Fetching results for ' . $name);
            $response = $this->client->request('GET', $search);
            $details  = json_decode($response->getBody());
            $matches  = (int)$details->total_matches_found;

            _log("{$name} has {$matches} matches");

            if ($matches == 0) { continue; }

            foreach ($details->results as $car => $description) 
            {
                //if ($description->TitleStatus == 'USED') {dd($description);}
                $result[] = new Car($description);
            }

        }

        return $result;
    }

    public function proccessData(array $data) : array
    {
        $result = ['new' => [], 'price_change' => [], 'sold' => []];

        foreach($data as $type => $car) 
        {
            if(!$car->isInDb()) {
                $car->save();
                $result['new'][] = $car;
            } else {
                if($car->isPriceChanged()) {
                    $result['price_change'][]  = $car;
                }
            }
        }


        return $result;
    }

    public function getSold(array $data) : array
    {
        $fromDb   = $this->getDbCars();
        $soldList = [];
        $vins     = [];

        foreach($data as $type => $car) 
        {
            $vins[] = $car->vin;
        }

        $sold = $fromDb->whereNotIn('vin', $vins);

        foreach($sold as $car) 
        {
            if ($car->status != 'sold') 
            {
                $car->sold_on    = \Carbon\Carbon::now('America/Vancouver');
                $car->status     = 'sold';
                $car->save();

                $soldList[] = $car;
            }
        }

        return $soldList;       
    }

    public function getHistory() : array
    {
        $fromDb   = $this->getDbCars();
        $history = $fromDb->where('status', 'sold')->where('type', 'USED');

        return $history->toArray();
    }

}

?>