<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Country;
use App\CarBrand;

class ImportBrandsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:brands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $results = $this->requestBrands();
        foreach($results['brand'] as $result){
            $country = Country::firstOrCreate(
                [
                    'name' => $result['country']['name'],
                ], [
                    'code' => $result['country']['code'],
                    'area' => 'world',
                ]
            );
            $brand = CarBrand::firstOrCreate(
                [
                    'name' => $result['name'],
                ], [
                    'country_id' => $country['id'],
                    'code' => $result['code'],
                ]
            );
        }
    }

    /**
     * ブランド情報のリクエスト
     *
     * @return objects
     */
    private function requestBrands()
    {
        $client = new Client([
            'base_uri' => 'http://webservice.recruit.co.jp',
        ]);
        $apiKey = env('CARSENSOR_API_KEY');

        $response = $client->request(
            'GET',
            "/carsensor/brand/v1/?key={$apiKey}&format=json"
        );
        $results = json_decode($response->getBody(), true)['results'];

        return $results;
    }
}
