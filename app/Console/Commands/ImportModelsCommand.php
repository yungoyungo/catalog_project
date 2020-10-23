<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\CarBrand;
use App\CarModel;
use App\CarGrade;

class ImportModelsCommand extends Command
{
    const COUNT = 100; // 一回のリクエストで取得する件数 10~100
    const INT_TIME = 1; // リクエストの送信間隔(秒)

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:models
    {--b|--brandCode= : 開始ブランドをコードで指定}
    {--s|--start=1 : 開始ポイントを指定}';

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
        $carBrands = CarBrand::all();

        // 引数にブランドとミッションと開始点があった場合そこまでスキップ
        $hasBrandSkipped = false;
        $hasNumberSkipped = false;

        foreach($carBrands as $carBrand){
            if (!$hasBrandSkipped && $this->option('brandCode') !== null && $carBrand->code !== $this->option('brandCode')) {
                echo("skip: {$carBrand->name}\n");
                continue;
            }
            $hasBrandSkipped = true;

            $start = 1;
            $results = $this->requestModels($carBrand, $start);
            $available = $results['results_available'];
            echo("\n".$carBrand['carsensor_brand_code']." : ".$carBrand['name']." available : ".$available."\n");

            while ($start < $available) {
                if (($start+100) < $available) {
                    $end = $start + 99;
                } else {
                    $end = $available;
                }
                echo("\n".$start."-".$end." :requesting...\n");
                $results = $this->requestModels($carBrand, $start);

                foreach($results['catalog'] as $result){
                    $carModel = $this->firstOrCreateCarModel($result, $carBrand);
                    if($carModel->wasRecentlyCreated){
                        echo("new model : ".$carModel->name."\n");
                    }

                    $carGrade = $this->firstOrCreateCarGrades($result, $carModel);
                    if($carGrade->wasRecentlyCreated){
                        echo("new grade : ".$carModel->name." / ".$carGrade->name."\n");
                    }
                }
                sleep(self::INT_TIME);
                $start += self::COUNT;
            }
        }
    }

    /**
     * カタログ情報のリクエスト
     *
     * @param CarBrand $carBrand
     * @param Int $start
     *
     * @return objects
     */
    private function requestModels(CarBrand $carBrand, Int $start)
    {
        $client = new Client([
            'base_uri' => 'http://webservice.recruit.co.jp',
        ]);
        $apiKey = env('CARSENSOR_API_KEY');

        $response = $client->request(
            'GET',
            "/carsensor/catalog/v1/?key={$apiKey}&brand={$carBrand->code}&count=".self::COUNT."&start={$start}&format=json"
        );
        $results = json_decode($response->getBody(), true)['results'];

        return $results;
    }

    /**
     * 車種マスタの更新
     *
     * @param Object $result
     * @param \App\CarMake $carBrand
     *
     * @return \App\CarModel $carModel
     */
    private function firstOrCreateCarModel($result, $carBrand)
    {
        $carModel = CarModel::firstOrCreate([
            'name'        => $result['model'],
            'car_brand_id' => $carBrand['id'],
        ], [
            'count'       => 0,
        ]);

        return $carModel;
    }

    /**
     * グレードマスタの更新
     *
     * @param Object $result
     * @param \App\CarModel $carModel
     *
     * @return \App\CarGrade $carGrade
     */
    private function firstOrCreateCarGrades($result, $carModel)
    {
        $period = $result['period'];
        preg_match("/([0-9]{6})-([0-9]{6})/", $period, $periodAry);
        if ((int)$periodAry[1] !== 999999) {
            $start_at = $this->dateTimeFormatFunc($periodAry[1]);
        } else {
            $start_at = null;
        }
        if ((int)$periodAry[2] !== 999999) {
            $end_at = $this->dateTimeFormatFunc($periodAry[2]);
        } else {
            $end_at = null;
        }

        // NOTE: 画像カラムが存在しない場合の暫定的なエラー処理
        // HACK: よくする
        if(!isset($result['photo']['front'])){
            $photo_front_url = null;
            $photo_front_caption = null;
        } else {
            $photo_front_url = $result['photo']['front']['l'];
            $photo_front_caption = $result['photo']['front']['caption'];
        }
        if(!isset($result['photo']['rear'])){
            $photo_rear_url = null;
            $photo_rear_caption = null;
        } else {
            $photo_rear_url = $result['photo']['rear']['l'];
            $photo_rear_caption = $result['photo']['rear']['caption'];
        }
        if(!isset($result['photo']['inpane'])){
            $photo_dashboard_url = null;
            $photo_dashboard_caption = null;
        } else {
            $photo_dashboard_url = $result['photo']['inpane']['l'];
            $photo_dashboard_caption = $result['photo']['inpane']['caption'];
        }

        // OPTIMIZE: めちゃくちゃ比較してるのでデータ増える程遅くなります
        $carGrade = CarGrade::firstOrCreate([
            'car_model_id'  => $carModel['id'],
            'code' => $result['series'],
            'name'    => $result['grade'],
            'capacity' => $result['person'],
            'length' => $result['length'],
            'width' => $result['width'],
            'height' => $result['height'],
            'price' => $result['price'],
            'start_at' => $start_at,
            'end_at' => $end_at,
            'body_type' => $result['body']['name'],
            'description' => $result['desc'],
        ], [
            'photo_front_url' => $photo_front_url,
            'photo_front_caption' => $photo_front_caption,
            'photo_rear_url' => $photo_rear_url,
            'photo_rear_caption' => $photo_rear_caption,
            'photo_dashboard_url' => $photo_dashboard_url,
            'photo_dashboard_caption' => $photo_dashboard_caption,
            'url' => $result['urls']['pc'],
        ]);

        return $carGrade;
    }

    /**
     * "201910"等の6文字を"2019-10-01 00:00:00"の型式でdatetimeクラスに変換します
     * また、"999999"に対しNULLを返します
     *
     * @param string $YearMonthStr
     *
     * @return DateTime
     * @return NULL
     */
    private function dateTimeFormatFunc($YearMonthStr)
    {
        if ((int)$YearMonthStr !== 999999) {
            preg_match("/([0-9]{4})([0-9]{2})/", $YearMonthStr, $YearMonthAry);
            $dateTime = new \DateTime();
            $dateTime->setDate($YearMonthAry[1], $YearMonthAry[2], 1)->setTime(0, 0, 0);

            return $dateTime;
        } else {
            return null;
        }
    }
}
