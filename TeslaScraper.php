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

	public function __construct() {
		$this->client  = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://www.tesla.com/inventory/api/v1/',
            // pages take a while sometimes....
            'timeout'  => 10.0,
        ]);
	}

	protected $searches = [
		'newModelS'  => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22ms%22%2C%22condition%22%3A%22new%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A200%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModelS' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22ms%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'newModel3'  => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22m3%22%2C%22condition%22%3A%22new%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A200%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModel3' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22m3%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'newModelX'  => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22mx%22%2C%22condition%22%3A%22new%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A200%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModelX' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22mx%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'newModelY'  => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22my%22%2C%22condition%22%3A%22new%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A200%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModelY' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22my%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D"
		
	];

	protected $query = 
		[
			"options"      => [],
			"arrangeby"    => "Price",
			"order"        => "asc",
			"market"       => "CA",
			"language"     => "en",
			"super_region" => "north america",
			"lng"          => -122.9184626,
			"lat"          => 49.231272,
			"zip"          => "V3N 1S3",
			"range"        => 200,
			"region"       => "BC"
		];


	public function scrape() : array
	{
		$result = [];
		foreach ($this->searches as $name => $search) 
		{
			_log('Fetching results for ' . $name);
			$response = $this->client->request('GET', $search);
			$details  = json_decode($response->getBody());
			$matches  = (int)$details->total_matches_found;

			_log("{$name} has {$matches} matches");

			if ($matches == 0) { continue; }

			foreach ($details->results as $car => $description) 
			{
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
		$fromDb   = Car::all();
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
				$car->sold_on    = \Carbon\Carbon::now();
				$car->status     = 'sold';
				$car->save();

				$soldList[] = $car;
			}
		}

		return $soldList;		
	}

}

?>