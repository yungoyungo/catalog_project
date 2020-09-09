<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Country;
use App\CarBrand;
use App\CarModel;
use App\CarGrade;

class ImportBrandCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportBrands';

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
        $results = $this->requestCarsensor();
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
     * カタログ情報のリクエスト
     *
     * @param \App\CarBrand $brand
     * @param int $start
     *
     * @return objects
     */
    private function requestCarsensor($start = 1)
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
