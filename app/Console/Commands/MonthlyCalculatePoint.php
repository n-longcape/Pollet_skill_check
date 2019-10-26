<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class MonthlyCalculatePoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:MonthlyCalculatePoint {--date= : 日付 Y-m}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    const CSV_HEADERS = ['user_id', 'total_amount', 'amount'];
    
    const DEFAULT_POINT_PERCENTAGE = 0.05;

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
     *
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $year = $this->option('date') ? Carbon::createFromFormat('Y-m', $this->option('date'))->format('Y'): Carbon::now()->subMonth()->format('Y');
        $month = $this->option('date') ? Carbon::createFromFormat('Y-m', $this->option('date'))->format('m'): Carbon::now()->subMonth()->format('m');
        $scores = Payment::select(DB::raw("user_id, sum(amount) as `total_amount`"))->whereYear('created_at', '=', $year)->whereMonth('created_at', '=', $month)->groupBy('user_id')->get();

        $records = [];
        foreach ($scores as $score) {
            $row = [
                $score->user_id,
                $score->total_amount,
                (integer) floor($score->total_amount * self::DEFAULT_POINT_PERCENTAGE) //必要だったらSQLとこの行をメソッド化する。
            ];
            $records[] = $row;
        }

        $result = $this->outputCsv($records);
        echo json_encode(['success' => !empty($result)]);
        return;

    }

    protected function outputCsv($records) {

        $yearMonth = $this->option('date') ?: Carbon::now()->subMonth()->format('Y-m');

        $fileName = 'monthly_point_'. $yearMonth;
        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        $csv->insertOne(self::CSV_HEADERS);

        $csv->insertAll($records);

        return file_put_contents(storage_path('app/'. $fileName.'.csv'), $csv);
    }

}
