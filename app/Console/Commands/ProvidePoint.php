<?php

namespace App\Console\Commands;

use App\Clients\ClientMock;
use App\Models\ImportFileHistory;
use App\Notifications\SlackNotification;
use App\Services\ProvidePointService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\Notifiable;
use League\Csv\Reader;
use League\Csv\Writer;

class ProvidePoint extends Command
{

    use Notifiable;
    use DatabaseTransactions;
    
    protected $filePath;

    protected $fileName;

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

    const CSV_HEADER_POINTS = 'amount';

    const CSV_HEADERS = [self::CSV_HEADER_USER_ID, self::CSV_HEADER_TOTAL_AMOUNT, self::CSV_HEADER_POINTS];



    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \League\Csv\Exception
     * @throws \TypeError
     * @return void
     */
    public function handle()
    {
        $this->setFilePath();
        $this->setFileName();

        $service = new ProvidePointService(new ClientMock());

        // 重複ファイルは取り込まない
        if(ImportFileHistory::where('name', $this->fileName)->exists()) {
            echo json_encode(['result' => 'file already imported']);
            return;
        }

        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();
        
        $provideResult = $service->chargePointByRecords($records);
        
        $successResult = $this->outputCsv($provideResult['successes'], 'point/success/success_'.$this->fileName);
        $failureResult = $this->outputCsv($provideResult['failures'], 'point/failure/failure_'.$this->fileName);

        ImportFileHistory::create([
            'name' => $this->fileName
        ]);

        $this->notify(new SlackNotification($this->createContent(count($provideResult['successes']), count($provideResult['failures']))));

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

    /**
     * create Clack Content
     * @param $successCount
     * @param $failureCount
     * @return string
     */
    protected function createContent($successCount, $failureCount) {
        return 
            $this->fileName.'の取り込みが完了しました。
            成功件数: '.$successCount.'件
            失敗件数: '.$failureCount.'件';
    }

    /**
     * set filePathProperty
     */
    protected function setFilePath() {
        $this->filePath = $this->option('filePath') ?: storage_path('app/monthly_point_'. Carbon::now()->subMonth()->format('Y-m').'.csv');
    }

    /**
     * set fileNameProperty
     */
    protected function setFileName() {
        $fileArray = explode('/', $this->filePath);
        $this->fileName = array_pop($fileArray);
    }

    /**
     * @param $notification
     * @return mixed
     */
    public function routeNotificationForSlack($notification)
    {
        return env('SLACK_URL');
    }
}
