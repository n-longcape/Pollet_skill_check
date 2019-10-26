<?php

namespace App\Console\Commands;

use App\Clients\ClientMock;
use App\Models\ChargeHistory;
use App\Models\ImportFileHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use League\Csv\Reader;
use League\Csv\Writer;

class ProvidePoint extends Command
{

    use DatabaseTransactions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ProvidePoint {--filePath=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provide Point By using CSV';

    const CSV_HEADER_USER_ID = 'user_id';

    const CSV_HEADER_TOTAL_AMOUNT = 'total_amount';

    const CSV_HEADER_POINTS = 'points';

    const CSV_HEADERS = [self::CSV_HEADER_USER_ID, self::CSV_HEADER_TOTAL_AMOUNT, self::CSV_HEADER_POINTS];



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
     * @return void
     */
    public function handle(ClientMock $client)
    {
        $yearMonth = Carbon::now()->subMonth()->format('Y-m');
        $filePath = $this->option('filePath') ?: storage_path('app/monthly_point_'. $yearMonth.'.csv');

        $fileArray = explode('/', $filePath);
        $fileName = array_pop($fileArray);

        // 重複ファイルは取り込まない
        if(ImportFileHistory::where('name', $fileName)->exists()) {
            echo json_encode(['result' => 'file already imported']);
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        $successes = [];
        $failures = [];
        
        foreach($records as $record) {
            try {
                $result = $client->provide($record[self::CSV_HEADER_USER_ID], $record[self::CSV_HEADER_POINTS]);

                if($result->code !== 200) {
                    $failures[] = $record;
                    continue;
                }
            } catch (\Exception $exception) {
                $failures[] = $record;
                continue;
            }
            ChargeHistory::create([
                'user_id' => $record[self::CSV_HEADER_USER_ID], 
                'amount' => $record[self::CSV_HEADER_POINTS],
                'status' => 'completed',
                ]);

            $successes[] = $record;
        }

        $successResult  = $this->outputCsv($successes, 'point/success/success_'. $yearMonth. '.csv');
        $failureResult = $this->outputCsv($failures, 'point/failure/failure'. $yearMonth. '.csv');

        ImportFileHistory::create([
            'name' => $fileName
        ]);

        echo json_encode(['result' => 'success', 'successCsvOutput' => !empty($successResult), 'failureCsvOutput' => !empty($failureResult)]);
        return;
    }

    /**
     * put CSV in storage path
     * @param $records
     * @param $fileName
     * @return int
     */
    protected function outputCsv($records, $fileName) {

        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        $csv->insertOne(self::CSV_HEADERS);

        $csv->insertAll($records);

        return file_put_contents(storage_path('app/'. $fileName), $csv);
        
    }
}
