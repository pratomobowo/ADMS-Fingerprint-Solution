<?php

namespace App\Http\Controllers;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class iclockController extends Controller
{

   public function __invoke(Request $request)
   {

   }

    // handshake
public function handshake(Request $request)
{
    $data = [
        'url' => json_encode($request->all()),
        'data' => $request->getContent(),
        'sn' => $request->input('SN'),
        'option' => $request->input('option'),
    ];
    DB::table('device_log')->insert($data);

    // update status device
    DB::table('devices')->updateOrInsert(
        ['no_sn' => $request->input('SN')],
        ['online' => now()]
    );

    $r = "GET OPTION FROM: {$request->input('SN')}\r\n" .
         "Stamp=9999\r\n" .
         "OpStamp=" . time() . "\r\n" .
         "ErrorDelay=60\r\n" .
         "Delay=30\r\n" .
         "ResLogDay=18250\r\n" .
         "ResLogDelCount=10000\r\n" .
         "ResLogCount=50000\r\n" .
         "TransTimes=00:00;14:05\r\n" .
         "TransInterval=1\r\n" .
         "TransFlag=1111000000\r\n" .
        //  "TimeZone=7\r\n" .
         "Realtime=1\r\n" .
         "Encrypt=0";

    return $r;
}
        //$r = "GET OPTION FROM:%s{$request->SN}\nStamp=".strtotime('now')."\nOpStamp=1565089939\nErrorDelay=30\nDelay=10\nTransTimes=00:00;14:05\nTransInterval=1\nTransFlag=1111000000\nTimeZone=7\nRealtime=1\nEncrypt=0\n";
    // implementasi https://docs.nufaza.com/docs/devices/zkteco_attendance/push_protocol/
    // setting timezone
    // request absensi
    public function receiveRecords(Request $request)
    {   
        
        //DB::connection()->enableQueryLog();
        $content['url'] = json_encode($request->all());
        $content['data'] = $request->getContent();;
        DB::table('finger_log')->insert($content);

        // update status device
        if ($request->has('SN')) {
            DB::table('devices')->updateOrInsert(
                ['no_sn' => $request->input('SN')],
                ['online' => now()]
            );
        }

        try {
            // $post_content = $request->getContent();
            //$arr = explode("\n", $post_content);
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', $request->getContent());
            //$tot = count($arr);
            $tot = 0;
            //operation log
            if($request->input('table') == "OPERLOG"){
                // $tot = count($arr) - 1;
                foreach ($arr as $rey) {
                    if(isset($rey)){
                        $tot++;
                    }
                }
                return "OK: ".$tot;
            }
            //attendance
            foreach ($arr as $rey) {
                if(empty(trim($rey))){
                    continue;
                }
                
                // Use preg_split to handle both tabs (\t) and spaces flexibly
                $data = preg_split('/\s+/', trim($rey));
                
                // Ensure we have at least ID and Timestamp
                if (count($data) < 2) {
                    \Log::warning('Malformed attendance record found', [
                        'sn' => $request->input('SN'),
                        'raw_row' => $rey
                    ]);
                    continue;
                }
                
                // Handle both BWN and SPK device formats
                // BWN: employee_id, timestamp, status1, status2, status3, status4, status5 (7 fields)
                // SPK: employee_id, timestamp, status1, status2, status3, status4, status5, status6, status7, status8, status9 (10 fields)
                
                $q['sn'] = $request->input('SN');
                $q['table'] = $request->input('table');
                $q['stamp'] = $request->input('Stamp');
                $q['employee_id'] = $data[0];
                
                // Combining date and time if they were split by space (now $data[1] is Date, $data[2] is Time)
                // attendance format usually: ID[sep]Y-m-d H:i:s[sep]Status...
                // If we split by \s+, the space in Y-m-d H:i:s results in $data[1] = Date and $data[2] = Time.
                $q['timestamp'] = $data[1] . (isset($data[2]) ? ' ' . $data[2] : '');
                
                // Adjust index mapping because timestamp now occupies two slots ($data[1] and $data[2])
                $offset = (strpos($rey, "\t") === false) ? 1 : 0; 
                // Actually, let's just re-index based on standard format: ID, Timestamp, S1, S2, S3, S4, S5
                // ZKteco timestamp is always Y-m-d H:i:s (has a space)
                
                // SPK devices usually have more fields. Let's detect by field count.
                // If split by \s+, SPK (10-11 fields normally) will have more.
                if (count($data) > 8) {
                    // For SPK devices
                    $q['status1'] = $this->validateAndFormatInteger($data[4] ?? null);
                    $q['status2'] = $this->validateAndFormatInteger($data[5] ?? null);
                    $q['status3'] = $this->validateAndFormatInteger($data[6] ?? null);
                    $q['status4'] = $this->validateAndFormatInteger($data[7] ?? null);
                    $q['status5'] = $this->validateAndFormatInteger($data[8] ?? null);
                } else {
                    // For BWN devices
                    $q['status1'] = $this->validateAndFormatInteger($data[3] ?? null);
                    $q['status2'] = $this->validateAndFormatInteger($data[4] ?? null);
                    $q['status3'] = $this->validateAndFormatInteger($data[5] ?? null);
                    $q['status4'] = $this->validateAndFormatInteger($data[6] ?? null);
                    $q['status5'] = $this->validateAndFormatInteger($data[7] ?? null);
                }
                
                $q['created_at'] = now();
                $q['updated_at'] = now();
                
                DB::table('attendances')->insert($q);
                $tot++;
            }
            return "OK: ".$tot;
        } catch (\Throwable $e) {
            $err['data'] = "Error in receiveRecords: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            $err['created_at'] = now();
            $err['updated_at'] = now();
            DB::table('error_log')->insert($err);
            report($e);
            return "ERROR: ".$tot."\n";
        }
    }
    public function getrequest(Request $request)
    {
        // $r = "GET OPTION FROM: ".$request->SN."\nStamp=".strtotime('now')."\nOpStamp=".strtotime('now')."\nErrorDelay=60\nDelay=30\nResLogDay=18250\nResLogDelCount=10000\nResLogCount=50000\nTransTimes=00:00;14:05\nTransInterval=1\nTransFlag=1111000000\nRealtime=1\nEncrypt=0";

        return "OK";
    }
    private function validateAndFormatInteger($value)
    {
        return isset($value) && $value !== '' ? (int)$value : null;
        // return is_numeric($value) ? (int) $value : null;
    }

}
