<?php

namespace App\Services;


use App\Clients\ClientInterface;
use App\Models\ChargeHistory;
use League\Csv\Reader;


/**
 * ポイント付与処理のサービスクラス
 * Class ProvidePointService
 * @package App\Services
 */
class ProvidePointService
{
    protected $client;

    protected $successList = [];

    protected $failureList = [];


    /**
     * ProvidePointService constructor.
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;

    }
    
    /**
     * レコードに対しポイント付与をする
     * @param array|Reader $records
     * @return array
     */
    public function chargePointByRecords($records) {
        $successList = [];
        $failureList = [];
        foreach($records as $record) {
            try {
                $result = $this->client->provide($record['user_id'], $record['amount']);

                if($result->code !== 200) {
                    $failureList[] = $record;
                    continue;
                }
            } catch (\Exception $exception) {
                $failureList[] = $record;
                continue;
            }
            ChargeHistory::create([
                'user_id' => $record['user_id'],
                'amount' => $record['amount'],
                'status' => 'completed',
            ]);

            $successList[] = $record;
        }
        return ['successes' => $successList, 'failures' => $failureList];
    }
}