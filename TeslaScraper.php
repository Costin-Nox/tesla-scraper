<?php
/**
 * Amazon order history parser.
 * 
 * @author  Costin Ghiocel <me@costingcl.ca>
 */

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Tightenco\Collect\Support\Collection;

Class TeslaScraper
{
	public function __construct() {
		$this->client  = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://www.tesla.com/inventory/api/v1/',
            // pages take a while sometimes....
            'timeout'  => 10.0,
        ]);
	}

	protected $searches = [
		'newModelS'  => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22ms%22%2C%22condition%22%3A%22new%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModelS' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22ms%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModel3' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22m3%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D",
		'usedModelX' => "inventory-results?query=%7B%22query%22%3A%7B%22model%22%3A%22mx%22%2C%22condition%22%3A%22used%22%2C%22options%22%3A%7B%7D%2C%22arrangeby%22%3A%22Price%22%2C%22order%22%3A%22asc%22%2C%22market%22%3A%22CA%22%2C%22language%22%3A%22en%22%2C%22super_region%22%3A%22north%20america%22%2C%22lng%22%3A-122.9184626%2C%22lat%22%3A49.231272%2C%22zip%22%3A%22V3N%201S3%22%2C%22range%22%3A0%2C%22region%22%3A%22BC%22%7D%2C%22offset%22%3A0%2C%22count%22%3A50%2C%22outsideOffset%22%3A0%2C%22outsideSearch%22%3Afalse%7D"
		
	];

	protected $query = 
		[
			"options" => [],
			"arrangeby" => "Price",
			"order" => "asc",
			"market" => "CA",
			"language" => "en",
			"super_region" => "north america",
			"lng" => -122.9184626,
			"lat" => 49.231272,
			"zip" => "V3N 1S3",
			"range" => 200,
			"region" => "BC"
		];


	public function scrape() {
		$result = [];
		foreach ($this->searches as $name => $search) 
		{
			$result[$name] = [];

			_log('Fetching results for ' . $name);
			$response = $this->client->request('GET', $search);
			$details  = json_decode($response->getBody());
			$matches  = (int)$details->total_matches_found;

			_log("{$name} has {$matches} matches");

			if ($matches == 0) { continue; }

			foreach ($details->results as $car => $description) {
				$result[$name][] = [
					'price' => $description->InventoryPrice,
					'odometer' => $description->Odometer,
					'trim' => $description->TrimName,
					'year' => $description->Year,
				];
			}
		}

		dd($result);
	}

}

?>