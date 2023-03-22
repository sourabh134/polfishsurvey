<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\App;
use Illuminate\Routing\Controller as BaseController;
use App\Models\monitor;
use App\Models\survey_history;
use App\Models\monitor_attr;
use App\Models\demographic_history;
use DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Storage;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    //Page for Survey start
    public function survey_start(Request $request){
        $survey_id = $request->id;
        $monitor_id = $request->monitor_id;
        $json = $request->json;
        $url = base64_decode($request->url);
        $data['survey_id']=$survey_id;
        $data['monitor_id']=$monitor_id;
        $data['url']=$url;
        $data['json']=$json;
        $data['request_uuid'] = $request->request_uuid;
        $data['click_id']=$request->click_id;
        //check survey
        $check_user_given_survey = survey_history:: where("monitor_id",base64_decode($monitor_id))->where("survey_id",base64_decode($survey_id));
        if($check_user_given_survey->count()!=0){
            $survey_status=$check_user_given_survey->first()->survey_status;
            if($survey_status==1){
                $data['check']=1;
            }else{
                $data['check']=2;
            }
            
        }else{
            $data['check']=0;
        }        
        return view('survey_start',$data);
    }

    //Save survey data in to suvey histories table
    public function submit_survey_data(Request $request){        
        $survey_id = base64_decode($request->survey_id);
        $monitor_id = base64_decode($request->monitor_id);
        $json = json_decode(base64_decode($request->json));
        $url = $request->url;
        $request_uuid = base64_decode($request->request_uuid);
        $click_id = base64_decode($request->click_id);    
        $survey_history = new survey_history;        
        $survey_history->monitor_id = $monitor_id;
        $survey_history->survey_id = $survey_id;
        $survey_history->survey_class = $json->survey_class;
        $survey_history->survey_ir = $json->survey_ir;
        $survey_history->survey_loi = $json->survey_loi;
        $survey_history->survey_cpa = $json->survey_cpa;
        $survey_history->reward_name = $json->reward_name;
        $survey_history->reward_value = $json->reward_value;
        $survey_history->survey_link = $json->survey_link;
        $survey_history->survey_lang = $json->survey_lang;
        $survey_history->request_uuid = $request_uuid;
        $survey_history->click_id = $click_id;
        $survey_history->survey_status = 0;        
        $survey_history->given_point_status = 0;
        $survey_history->start_datetime = date('Y-m-d H:i:s');        
        $survey_history->end_datetime = date('Y-m-d H:i:s');         
        $survey_history->save();

        //Create log
        $log=array('Json'=>$json,'Result'=>'Success');
        \Log::channel('survey')->info('submit survey data',$log);
        echo 1;
    }

    // After survey complete this function will run (webhook for callback)
    public function survey_complete(Request $request){
        //check signature is valid        
       $sig=$this->check_signature($request->cpa,$request->device_id,$request->request_uuid,$request->reward_name,$request->reward_value,$request->status,$request->timestamp,$request->tx_id,$request->signature);
        if($sig==0){        
            $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $redirect_url = url('/survey_completed');
            if($request->status=="eligible"){
                $status=1;
            }else{
                $status=2;
            }
            $survey_array = array(
                'reward_name'=>$request->reward_name,
                'reward_value'=>$request->reward_value,
                'signature'=>$request->signature,
                'redirect_url_raw'=>$redirect_url,
                'callback_url_raw'=>$actual_link,
                'survey_status'=>$status,
                'term_reason'=>$request->reason,
                'end_datetime'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s')
            );
            DB::table('survey_histories')->where('click_id',$request->click_id)->where('monitor_id',$request->device_id)->update($survey_array);
            
            //reward Api
            if($status==1){
                $survey_data = survey_history::where('monitor_id',$request->device_id)->where('click_id',$request->click_id)->first();
                $givenPoint=$survey_data->survey_loi.env('GIVENPOINT');            
                $this->reward_apis($request->device_id,$survey_data->survey_id,$request->reward_name,$request->click_id,$givenPoint,$survey_data->id);
            } 
            //Create log
            $log=array('survey_array'=>$survey_array,'Result'=>'Success');
            \Log::channel('survey')->info('Survey Complete',$log);
            echo "True";

        }
    }

    //Check signature
    function check_signature($cpa,$device_id,$request_uuid,$reward_name,$reward_value,$status,$timestamp,$tx_id,$signature){
        $secret_key = env("SECRETKEY");
        $cpa = rawurldecode($cpa);
        $device_id = rawurldecode($device_id);
        $request_uuid = rawurldecode($request_uuid);
        $reward_name = rawurldecode($reward_name);
        $reward_value = rawurldecode($reward_value);
        $status = rawurldecode($status);
        $timestamp = rawurldecode($timestamp);
        $tx_id = rawurldecode($tx_id);
        $url_signature = rawurldecode($signature); 
        $data = $cpa . ":" . $device_id;
        if (!empty($request_uuid)) { // only added when non-empty
            $data = $data . ":" . $request_uuid;
        }
        $data = $data . ":" . $reward_name . ":" . $reward_value . ":" . $status . ":" . $timestamp . ":" . $tx_id;
        $computed_signature = base64_encode(hash_hmac("sha1" , $data, $secret_key, true));
        if($url_signature==$computed_signature){
            $is_valid=1;
        }else{
            $is_valid=0;
        }       
        return $is_valid;
    }

    //reward api 
    function reward_apis($monitorId,$surveyId,$surveyName,$referenceId,$givenPoint,$survey_history_id){
        $str = $givenPoint;
        eval( '$result = (' . $str. ');' );
        $token = env('REWARDTOKEN');
        $referenceIds='pf'.'-'.$monitorId.'-'.$surveyId.'-'.$survey_history_id;
        $postData = [
        'monitors_id' => $monitorId,
        'survey_id' => 'PF'.$surveyId,
        'survey_name' => $surveyName,
        'reference_id' => $referenceIds,
        'point' => $result,
        'point_type' => 0,
        'point_given_date' => date('Y-m-d H:i:s') //
        ];
        $json = json_encode($postData);
        $httpHeader = [
        'X-Authorization : Bearer ' . $token,
        'Content-Type : application/json;charset=UTF-8'
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => env("REWARDAPI"),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $httpHeader,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $resultArray=json_decode($response);
        
        if($resultArray->result_code==1){
            $reward_array = array(
                'actual_rewarded_point'=>$result,
                'given_point_status'=>1,
                'given_point_at'=>date('Y-m-d H:i:s'),
                'note'=>$resultArray->data->message,
                'updated_at'=>date('Y-m-d H:i:s')
            );
            DB::table('survey_histories')->where('click_id',$referenceId)->where('monitor_id',$monitorId)->update($reward_array);            
        }else{
            // if($resultArray->data->message=="reference-id.exists."){
            //     $survey_history=survey_history::find($survey_history_id);
            //     $reward_array = array(
            //         'actual_rewarded_point'=>$survey_history->actual_rewarded_point,
            //         'given_point_status'=>$survey_history->given_point_status,
            //         'given_point_at'=>date('Y-m-d H:i:s'),
            //         'note'=>$survey_history->note,
            //         'updated_at'=>date('Y-m-d H:i:s')
            //     );
            //     DB::table('survey_histories')->where('click_id',$referenceId)->where('monitor_id',$monitorId)->update($reward_array); 

            // }else{
                $reward_array = array(
                    'actual_rewarded_point'=>$result,
                    'given_point_status'=>0,
                    'given_point_at'=>date('Y-m-d H:i:s'),
                    'note'=>$resultArray->data->message,
                    'updated_at'=>date('Y-m-d H:i:s')
                );
                DB::table('survey_histories')->where('click_id',$referenceId)->where('monitor_id',$monitorId)->update($reward_array); 
            // }
            
        }
        //Create log
        $log=array('postData'=>$postData,'Result'=>'Success');
        \Log::channel('survey')->info('Reward API',$log);
               
    }

    //reward batch recheck
    public function reward_recheck(Request $request){
        $survey_data = survey_history::where('survey_status',1)->where('given_point_status',0)->where('note','!=',"success");
        if($survey_data->count()!=0){
            foreach($survey_data->get() as $value){
                $givenPoint=$value->survey_loi.env('GIVENPOINT');
                $this->reward_apis($value->monitor_id,$value->survey_id,$value->reward_name,$value->click_id,$givenPoint,$value->id);
                //Create log
                $log=array('device_id'=>$value->monitor_id,'survey id'=>$value->survey_id,'reward name'=>$value->reward_name,'click_id'=>$value->click_id,'givenPoint'=>$givenPoint,'Result'=>'Success');
                \Log::channel('survey')->info('Reward recheck',$log);
                //echo "true";
            }
        }else{
            //Create log
            $log=array('Result'=>'NO data found for reward grant');
            \Log::channel('survey')->info('Reward recheck',$log);
        }
        

    }

    //after survey complete show success message
    public function survey_completed(Request $request){       
        return view('survey_complete');
    }

    //after survey closed show closed message
    public function survey_closed(Request $request){       
        return view('survey_closed');
    }

    //you are not eligable message
    public function survey_noteligable(Request $request){       
        return view('survey_noteligable');
    }

    //connection check
    public function checkconnection(Request $request){
        $DB_HOST=env('DB_HOST');
        $DB_DATABASE=env('DB_DATABASE');
        $DB_USERNAME=env('DB_USERNAME');
        $DB_PASSWORD=env('DB_PASSWORD');
        if($DB_HOST=='' || $DB_DATABASE=='' || $DB_USERNAME=='' ){
            $data['status']="Failed";
            $data['message']="Could not connect to the database.  Please check your configuration";
        }else{
            try {
                DB::connection()->getPdo();
                $data['status']="Ok";
                $data['message']="";
            } catch (\Exception $e) {
                $data['status']="Failed";
                $data['message']="Could not connect to the database.  Please check your configuration";
                //die("Could not connect to the database.  Please check your configuration. error:" . $e );
            }
            
        }
        //Create log
        $log=array('data'=>$data);
        \Log::channel('survey')->info('Connection check',$log);
        echo json_encode($data); 
    }

    //network check
    function networkCheck(Request $request){
        $url = "https://www.google.com/";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        if ($result !== false) {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);             
            if ($statusCode == 404) {
                $data['status']="Failed";
            }
            else {
                $data['status']="Ok";
            }
        }
        else {
            $data['status']="Failed";
        }
        //Create log
        $log=array('data'=>$data);
        \Log::channel('survey')->info('Network check',$log);
        echo json_encode($data);
    }

    //health check
    function healthcheck(Request $request){
        if (App::environment(['local', 'staging'])) {
            //echo $environment = App::environment();
            $data['status']="Ok";
        }else{
            $data['status']="Failed";
        }
        //Create log
        $log=array('data'=>$data);
        \Log::channel('survey')->info('Health check',$log);
        echo json_encode($data);
    }
    
    //Survey list in our side
    public function surveyList(Request $request){
        $survey_id = $request->id;
        $monitor_id = $request->monitor_id;
        $json = $request->json;
        $url = base64_decode($request->url);
        $data['survey_id']=$survey_id;
        $data['monitor_id']=$monitor_id;
        $data['url']=$url;
        $data['json']=$json;
        $data['request_uuid'] = $request->request_uuid;
        $data['click_id']=$request->click_id;
        $data['APIkey']= env('API_KEY');
        //check survey
        $check_user_given_survey = survey_history:: where("monitor_id",base64_decode($monitor_id))->where("survey_id",base64_decode($survey_id))->where('survey_status',1);
        if($check_user_given_survey->count()!=0){
            $survey_status=$check_user_given_survey->first()->survey_status;
            if($survey_status==1){
                $data['check']=1;
            }else{
                $data['check']=2;
            }
            
        }else{
            $data['check']=0;
        }
        //Create log
        $log=array('data'=>$data);
        \Log::channel('survey')->info('Survey List',$log);
        return view('surveylist',$data);
    }

    //delete previous log file
    function deleteLogFile(Request $request){
        $days=env('TTL_DAYS');
        $tenDaysAgo = Carbon::now()->subDays($days+1);
        $thirtyDaysAgo = Carbon::now()->subDays($days+30);
        $period = CarbonPeriod::create(date('Y-m-d',strtotime($thirtyDaysAgo)), date('Y-m-d',strtotime($tenDaysAgo)));
        // Iterate over the period
        foreach ($period as $date) {
            $filedate= $date->format('Y-m-d');
            if(file_exists(storage_path('/logs/survey'.$filedate.'.log'))){
                unlink(storage_path('/logs/survey'.$filedate.'.log'));               
            }            
        }
        //Create log
        $log=array('between date'=>date('Y-m-d',strtotime($thirtyDaysAgo)).' ~ '.date('Y-m-d',strtotime($tenDaysAgo)));
        \Log::channel('survey')->info('Delete Log File',$log);
       
        echo "success";
    }

   
}
