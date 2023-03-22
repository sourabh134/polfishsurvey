<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\monitor;
use App\Models\monitor_attr;
use App\Models\demographic_history;
use App\Models\survey_history;
use App\Models\reconciliation;
use DB;

class Api extends Controller
{
    //send success response
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];
        return response()->json($response, 200);
    }
    //send error response
    public function sendError($error, $errorMessages = [], $code = 200)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];


        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }

    //Get survey data from pollfish
    public function survey(Request $request){         
        //validation
        if($request->monitor_id==null)
        {
            return $this->sendError('Please provide moniter_id.','');
        }
        if($request->gender==null)
        {
            return $this->sendError('Please provide gender like Male, Female, and Other.','');
        }
        if($request->dob==null)
        {
           return $this->sendError('Please provide dob format(Y-m-d).','');
        }
        if($request->media_id==null)
        {
            return $this->sendError('Please provide media_id.','');
        }
        if($request->os==null)
        {
            $os = 3;
        }else if($request->os>3){
            return $this->sendError('Please enter valid os 0,1,2,and 3.','');
        }else{
            $os=$request->os;       
        }
        $gender = strtolower($request->gender);
        if($gender=="male"){
            $genderval=1;
        }else if($gender=="female"){
            $genderval=2;
        }else if($gender=="other"){
            $genderval=3;
        }else{
            return $this->sendError('Please provide gender like Male, Female, and Other.','');
        } 
        //validation end
        $time_out = env("TIMEOUT"); //execution time in second   
        $time_start = microtime(true);
        $moniter_id = $request->monitor_id;        
        $dob = date("Y-m-d",strtotime($request->dob)); //date format Y-m-d
        $media_id = $request->media_id; 
        $recordarray = array();
        $api_key = env("API_KEY"); //API Key
        $debug_pollfish = env("DEBUG_POLLFISH"); // Debug
        if($debug_pollfish==1){
            $debug="True";
        }else{
            $debug="False";
        }
        $device_id=$moniter_id;
        $timestamp=Carbon::now()->timestamp;
        $encryption="NONE";
        $version=env("VERSION"); //Version        
        $dobyear = explode("-",$dob);
        $year_of_birth = current($dobyear);
        $request_uuid= $this->random_string(23);
        $click_id= $this->random_string(20);
        $pollfishurl=env("POLLFISHURL"); //Pollfish api url                
        //check monitor exist and insert
        $check_exist = monitor::where('monitor_id',$moniter_id)->count();
        if($check_exist==0){
            $monitor = new monitor;
            $monitor->monitor_id = $request->monitor_id;
            $monitor->gender = $genderval;
            $monitor->dob = $dob;
            $monitor->postal_code = "";
            $monitor->media_id = $request->media_id;
            $monitor->note = $request->note;
            $monitor->save();
        }else{
            $monitorarray= array(
                'gender'=>$genderval,
                'dob'=>$dob,                
                'media_id'=>$request->media_id,
                'note'=>$request->note,
                'updated_at'=>date('Y-m-d H:i:s')
            );
            monitor::where('monitor_id',$request->monitor_id)->update($monitorarray);
        }
        //sig
        $reward_conversion="1.1";
        $reward_name="Diamonds";
        $secret_key=env("SECRETKEY");
        $sig=base64_encode(hash_hmac("sha1" , "$reward_conversion$reward_name$click_id", $secret_key, true));
        //check monitor attr data and generate url on the behalf of data
        $check_attr_data = monitor_attr::where('monitor_id',$moniter_id)->count();
        if($check_attr_data!=0){
            $monitor_attr = monitor_attr::where('monitor_id',$moniter_id)->get();
            $monitorattrArray=array();
            foreach($monitor_attr as $monitor_value){
                $monitorattr='"'.$monitor_value->name.'":"'.$monitor_value->value.'"';
                array_push($monitorattrArray,$monitorattr);                
            }
            $output = array_slice($monitorattrArray, 3);
            $urlData=implode(",",$output);
            $url = $pollfishurl.'?json={"api_key":"'.$api_key.'","device_id":"'.$device_id.'","timestamp":"'.$timestamp.'","encryption":"'.$encryption.'","offerwall":"true","version":"'.$version.'","os":"'.$os.'","year_of_birth":"'.$year_of_birth.'","gender":"'.$genderval.'",'.$urlData.',"debug":"'.$debug.'","content_type":"json","request_uuid":"'.$request_uuid.'","click_id":"'.$click_id.'"}&dontencrypt=true&sig='.$sig.'';
        }else{
            //default url
            $url = $pollfishurl.'?json={"api_key":"'.$api_key.'","device_id":"'.$device_id.'","timestamp":"'.$timestamp.'","encryption":"'.$encryption.'","offerwall":"true","version":"'.$version.'","os":"'.$os.'","year_of_birth":"'.$year_of_birth.'","gender":"'.$genderval.'","debug":"'.$debug.'","content_type":"json","request_uuid":"'.$request_uuid.'","click_id":"'.$click_id.'"}&dontencrypt=true&sig='.$sig.'';
        }
        // echo $url;
        // die;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $resultArray=json_decode($response);
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        if($execution_time<$time_out){
            if(!empty($resultArray)){
                foreach($resultArray->surveys as $value){                    
                    $data['survey_id'] = 'PF'.$value->survey_id;
                    $data['survey_cpa'] = $value->survey_cpa;
                    $data['survey_class'] = $value->survey_class;
                    $data['survey_ir'] = $value->survey_ir;
                    $data['survey_loi'] = $value->survey_loi;
                    $data['survey_lang'] = $value->survey_lang;
                    $data['reward_name'] = $value->reward_name;
                    $data['reward_value'] = $value->reward_value;
                    $data['survey_link'] = url('/survey-start?id='.base64_encode($value->survey_id).'&monitor_id='.base64_encode($moniter_id).'&url='.base64_encode($value->survey_link).'&json='.base64_encode(json_encode($value)).'&request_uuid='.base64_encode($request_uuid).'&click_id='.base64_encode($click_id));
                    //$data['survey_links'] = url('/surveyList?id='.base64_encode($value->survey_id).'&monitor_id='.base64_encode($moniter_id).'&url='.base64_encode($value->survey_link).'&json='.base64_encode(json_encode($value)).'&request_uuid='.base64_encode($request_uuid).'&click_id='.base64_encode($click_id));
                    $data['remaining_completes'] = $value->remaining_completes;
                    $data['ordering'] = $value->ordering;
                    array_push($recordarray,$data);
                } 
                $rec_data['execution_time']=$execution_time;                
                $rec_data['record']=$recordarray;
                //Create log
                $log=array('url'=>$url,'Result'=>$recordarray);
                \Log::channel('survey')->info('Survey List',$log);                
                return $this->sendResponse($rec_data, '');                 
            }else{
                //Create log
                $log=array('message'=>'NO survey found');
                \Log::channel('survey')->info('Survey List',$log); 
                return $this->sendError('NO survey found','');
            }
        }else{
            $log=array('message'=>'NO survey found');
            \Log::channel('survey')->info('Survey List',$log);
            return $this->sendError('NO survey found','');
        }
    }
    //genrate random number and string
    function random_string($length)
    {
        $string = "";
        $chars = "abcdefghijklmanopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[rand(0, $size - 1)];
        }
        return $string; 
    }
    //Sales Progress
    function salesProgress(Request $request){
        $fromDate=date('Y-m-d',strtotime($request->fromDate));
        $toDate=date('Y-m-d',strtotime($request->toDate));
        if($fromDate==null)
        {
            return $this->sendError('Please provide fromDate in (Y-m-d).','');
        }
        if($toDate==null)
        {
            return $this->sendError('Please provide toDate in (Y-m-d).','');
        }
        if($toDate<$fromDate)
        {
            return $this->sendError('Please provide toDate grater then fromDate.','');
        }
              
        $sql = survey_history::where('start_datetime','>=',$fromDate)->where('start_datetime','<=',$toDate);
        $data['Total No of Monitors']=$sql->distinct()->count();
        $data['Total Surveys_attempted']=$sql->count();
        $data['Total Surveys_Completed']=$sql->where('survey_status',1)->count();
        $data['Total Reward Value(completed)']=$sql->where('survey_status',1)->sum('reward_value');
        $data['Total Points to be granted']=array(
            'Failure points'=>$sql->where('survey_status',1)->whereIn('given_point_status',[-1,0,2])->sum('reward_value'),
            'Success Points'=>$sql->where('survey_status',1)->where('given_point_status',1)->sum('reward_value')
        );
        return $this->sendResponse($data, '');
        //Create log
        $log=array('From Date'=>$fromDate,'To Date'=>$toDate,'Result'=>$data);
        \Log::channel('survey')->info('Sales Progress',$log); 
    }
    
    
    //demographic data
    public function demographic_data(Request $request){       
        $json = $request->getContent(); 
        if(empty($json))
        {
            $file = fopen("testdata/newfileweb.txt","r");
            echo fgets($file);
            fclose($file);
        }
        else
        {
            $resultArray= json_decode($json,True);
            $jsoncount= count($resultArray);
            //demographic_history data save
            $demographic_history = new demographic_history;
            $demographic_history->monitor_id=$resultArray['device_id'];
            $demographic_history->data=$json;
            $demographic_history->job_status=1;
            $demographic_history->save();
            $lastid=$demographic_history->id;
            //save monittor attr data            
            foreach($resultArray as $key=>$val){
                //check user and key name exist
                $check_user_exist = monitor_attr::where('monitor_id', '=', $resultArray['device_id'])->where('name',$key)->count();
                if($check_user_exist==0){
                    if(is_array($val)){
                        $value=implode(",",$val);                        
                    }else{
                        $value=$val;                        
                    }
                    $monitor_attr = new monitor_attr;
                    $monitor_attr->monitor_id = $resultArray['device_id'];
                    $monitor_attr->name = $key;
                    $monitor_attr->value = $value;
                    $monitor_attr->save();                    
                }else{
                    if(is_array($val)){
                        $value=implode(",",$val);                        
                    }else{
                        $value=$val;                        
                    }
                    monitor_attr::where('monitor_id', '=', $resultArray['device_id'])->where('name',$key)
                        ->update([ 
                            'value' => $value,
                            'updated_at'=>date('Y-m-d H:i:s')
                        ]);                    
                } 
                //update demographic history after  monitor_attr insert or update
                //sleep for 3 seconds
                //sleep(3);
                demographic_history::where('id', '=', $lastid)
                    ->update([ 
                        'job_status' => 2,
                        'job_finished_at'=>date('Y-m-d H:i:s'),
                        'updated_at'=>date('Y-m-d H:i:s')
                    ]);                         
            }                  
            
        }
        $data['success'] = true;
        echo json_encode($data);
        //Create log
        $log=array('Json'=>$json,'Result'=>$data);
        \Log::channel('survey')->info('Demographic data',$log);                 
    }
    
    //reconciliation API
    public function reconciliation(Request $request){
        $device_id = $request->device_id;
        $survey_cpa = $request->cpa;
        $request_uuid = $request->request_uuid;
        $timestamp = $request->timestamp;
        $tx_id = $request->tx_id;
        $signature = $request->signature;
        $click_id = $request->click_id;
        $action = $request->action;
        $reconciliation = new reconciliation;
        $reconciliation->monitor_id=$device_id;
        $reconciliation->survey_cpa=$survey_cpa;
        $reconciliation->request_uuid=$request_uuid;
        $reconciliation->timestamp=$timestamp;
        $reconciliation->tx_id=$tx_id;
        $reconciliation->signature=$signature;
        $reconciliation->click_id=$click_id;
        $reconciliation->action=$action;
        $reconciliation->save();
        $data['success'] = true;
        echo json_encode($data);
        //Create log
        $log=array('Device id'=>$device_id,'survey cpa'=>$survey_cpa,'request uuid'=>$request_uuid,'timestamp'=>$timestamp,'tx id'=>$tx_id,'signature'=>$signature,'click id'=>$click_id,'action'=>$action,'Result'=>$data);
        \Log::channel('survey')->info('Reconciliation',$log);

    }

    
}
