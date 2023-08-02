<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\MonthlyPass; 
use App\Models\VehiclePreBooking; 
use App\Models\VehicleBooking; 
use App\Models\StaffDetails; 
use App\Models\PaUsers; 
use App\Models\FareInfo; 
use App\Models\PaymentHistory; 
use App\Models\Smsgetways; 


use Auth;

use DB;
class VehicleController extends Controller
{
    
    public function vehicle_in(Request $request)
    {

        // $useData = $request->attributes->get('userData');
        
        $regi =  (object)$request->input();
        // print_r($regi);exit;
        if($regi){
            //echo "<pre>";print_r($_FILES);die();
            
            $staff_id               = $regi->staff_id;
            $vendor_id              = $regi->vendor_id;
            $vehicle_number         = $regi->vehicle_number;
            $mobile_number          = $regi->mobile_number;
            $amount                 = $regi->amount;
            $vehicle_type           = $regi->vehicle_type;
            $vehicle_in_date_time   = $regi->vehicle_in_time;
            $latitude               = $regi->in_latitude;
            $longitude              = $regi->in_longitude;
            $qr_type                = $regi->qr_type;
            $payment_type			= $regi->payment_type;
            $transaction_id			= $regi->transaction_id;
                       
            $sr_no                  = !empty($regi->sr_no)?$regi->sr_no:'';
            $tagId                  = !empty($regi->tagId)?$regi->tagId:'';
            if($regi->staff_type=='yes'){
                $staff_vehicle_type = 'yes';  
            } else {
                $staff_vehicle_type = 'no'; 
            }
            $parking_type='Direct';
            if(isset($regi->payment_type) && $regi->payment_type!=""){
                $payment_type= $regi->payment_type;
            }else{
                $payment_type="Cash";  
            }
            $vehicle_number     = str_replace(' ', '', $vehicle_number); //remove all the space
            $vehicle_number     = str_replace(' ', '-', $vehicle_number); // Replaces all spaces with hyphens.
            $vehicle_number     = preg_replace('/[^A-Za-z0-9\-]/', '', $vehicle_number); //remove all the special charactor
            $vehicle_number     = strtoupper($vehicle_number); //convert char to upper case
            $passid = 0;

            $select_monthly_pass = MonthlyPass::select('id')->where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('status', 1)
                ->whereDate('end_date', '>=', DB::raw('CURDATE()'))
                ->limit(1)->get();
            if ($select_monthly_pass->count() == 1) {
                $amount = 0.0;
                $parking_type = 'Vehicle Pass';
                $val_vehicle_passid = $select_monthly_pass->first();
                $passid = $val_vehicle_passid->id;
            }

            $select_monthly_pass = VehiclePreBooking::select('id')
                ->where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('status', 'Booked')
                ->where('vehicle_type', $vehicle_type)
                ->limit(1)
                ->get();
            if ($select_monthly_pass->count() == 1) {
                $amount = 0.0;
                $parking_type = 'Vehicle Pass';
            }
        
            $vehicle_status = 'In';
            $active_plans_row = GetVendorActivatedPlan($vendor_id);
            if($mobile_number){

                $select_user_name = PaUsers::where('mobile_number', $mobile_number)->where('user_role', 'customer')->first();
                if ($select_user_name) {
                    $customer_id = $select_user_name->id;
                } else {
                   
                    $reflength = 10;
                    $refchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    $referral_code = substr( str_shuffle( $refchars ), 0, $reflength );
                    
                    $insert_data = [
                        'mobile_number' => $mobile_number,
                        'user_role' => 'customer',
                        'date_of_birth' => isset($date_of_birth)?$date_of_birth:"",
                        'os' => 'android',
                        'social_type' => 'simple',
                        'user_status' => 1,
                        'register_date' => date('Y-m-d h:i:s'),
                        'referral_code' => $referral_code,
                    ];
                    // print_r($insert_data);exit;
                    // $customer_id = "900001";
                    $customer_id = PaUsers::insertGetId($insert_data);
                }
            } else {
                $customer_id = 0;
            }

            $select_vehicle = VehicleBooking::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('vehicle_status', 'In')
                ->get();
                                    
            $vehicleImage           = $regi->vehicle_image; 
            $vehicleImage_filename  = isset($_FILES['vehicle_image']['name'])?$_FILES['vehicle_image']['name']:"";
            $vehicleImage_path      = "../../uploads/" .$vehicleImage_filename;
            $NewFileName            = '';
            $num_rows_c=$select_vehicle->count();

            if ($request->hasFile('vehicle_image')) {
                $vehicleImage = $request->file('vehicle_image');
                if ($vehicleImage->isValid()) {
                    $allowedTypes = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];
                    if (in_array($vehicleImage->getMimeType(), $allowedTypes)) {
                        $vehicleImage_path = $vehicleImage->store('images'); // Specify the storage directory according to your needs
                        $NewFileName = $vehicleImage->getClientOriginalName();
                        $num_rows_c = 0;
                    }
                }
            }
            if($num_rows_c==0) {
                $serial_number_res = VehicleBooking::where('vendor_id', $vendor_id)
                ->select(DB::raw('COALESCE(MAX(serial_number), 0) + 1 AS serial_number'))->first();
              
                // $insert_vehicle = "INSERT INTO vehicle_booking(vendor_id,customer_id,serial_number,vehicle_number,mobile_number,vehicle_type,vehicle_in_date_time,vehicle_status,latitude,longitude,qr_type,staff_vehicle_type,staff_in,vehicle_image,parking_type,in_pay_type,in_amount,tagid,sr_no,pass_id) VALUES('$vendor_id','$customer_id',$serial_number,'$vehicle_number','$mobile_number','$vehicle_type','$vehicle_in_date_time','$vehicle_status','$latitude','$longitude','$qr_type','$staff_vehicle_type','$staff_id','$NewFileName','$parking_type','$payment_type','$amount','$tagId','$sr_no','$passid')";
                $insert_data = [
                    'vendor_id' => $vendor_id,
                    'customer_id' => $customer_id,
                    'serial_number' => $serial_number_res->serial_number,
                    'vehicle_number' => $vehicle_number,
                    'mobile_number' => $mobile_number,
                    'vehicle_type' => $vehicle_type,
                    'vehicle_in_date_time' => $vehicle_in_date_time,
                    'vehicle_status' => $vehicle_status,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'qr_type' => $qr_type,
                    'staff_vehicle_type' => $staff_vehicle_type,
                    'staff_in' => $staff_id,
                    'vehicle_image' => $NewFileName,
                    'parking_type' => $parking_type,
                    'in_pay_type' => $payment_type,
                    'in_amount' => $amount,
                    'tagid' => $tagId,
                    'sr_no' => $sr_no,
                    'pass_id' => $passid,
                ];
                
                $booking_id = VehicleBooking::insertGetId($insert_data);
                
                if ($booking_id) {
                    
                    if($regi->services){
                        foreach($regi->services as $services){
                            BookingServices::insert([
                                'bookingid' => $booking_id,
                                'categoryid' => $services->cat_id,
                                'subcategoryid' => $services->subcat_id,
                                'amount' => $services->amount,
                            ]);
                        }
                        
                    }
                        GetVendorsWantedVechiles($vendor_id,$vehicle_number,$booking_id);
                        SensitiveVechilesNotify($vendor_id,$vehicle_number,$booking_id);
                        GetMissingVehicleNumber($vendor_id,$vehicle_number,$booking_id);

                        $vendor = PaUsers::select(DB::raw("CONCAT_WS(' ', first_name, last_name) as vendor_name"), 'parking_name', 'note')
                            ->where('id', $vendor_id)
                            ->first();
                        $vendor_ow = $vendor->toArray();

                        if(($mobile_number) && ($active_plans_row['sms_notification']==1)){
                        
                            $customer_name = '';
                            if($customer_id!=0){
                            $customer_name = $val_user['first_name'].' '.$val_user['last_name'].' ';
                            }
                            $sendmessage = "Hello, {$customer_name}! Your vehicle {$vehicle_number} {$vehicle_type} has been parked at {$vendor_ow['parking_name']} on " . date('Y-m-d h:i A', $vehicle_in_date_time) . ", Rs. {$amount}, View More " . url("/booking_invoice.php?id=" . base64_encode($booking_id));                            
                            $message = urlencode($sendmessage);
                            $smsTotal=smsBalanceVendor($vendor_id);
                            $BALANCE=$smsTotal[0]['BALANCE'];
                            if($BALANCE==0){
                                SendSMSNotification($mobile_number, $message);
                                
                            }else{
                                SendSMSNotificationActiveVendorwise($mobile_number, $sendmessage,$vendor_id,'In'); 
                            }
                        
                        }

                // if(($amount) && ($amount > 0)){
                        //$payment_type='Cash';
                
                    // PaymentHistory::insert([
                    //     'booking_id' => $booking_id,
                    //     'amount' => $amount,
                    //     'payment_type' => $payment_type,
                    //     'payment_date_time' => $vehicle_in_date_time,
                    //     'staff_id' => $staff_id,
                    //     'transaction_id' => $transaction_id
                    // ]);
                    
                    VehicleBooking::where('id', $booking_id)
                        ->update(['total_amount' => $amount]);
                    
                    $select_vehicles = VehicleBooking::select('serial_number')->where('id', $booking_id)->first();
                    
                    $select_vehicles_ow = $select_vehicles->toArray();

                    $array['error_code'] = 200;
                    $array['booking_id'] = $booking_id;
                    $array['serial_number'] = $select_vehicles_ow['serial_number'];
                    $array['note']=$vendor_ow['note'];
                    $array['message'] = 'Vehicle Park In Successfully';
                } else {
                    $array['error_code'] = 400;
                    $array['message'] = 'Some Datebase Error';
                }

            } else {
                $array['error_code'] = 400;
                $array['message'] = 'Vehicle Already in Parking';
            }

        } else {
            $array['error_code'] = 400;
            $array['message'] = 'Please provide request parameter';
        }
        $finalarray['response'] = $array;
        echo json_encode($finalarray);
    }

    public function vehicle_out(Request $request)
    {

        $regi =  (object)$request->input();
        if($regi){
            $staff_id = $regi->staff_id;
            $vendor_id = $regi->vendor_id;
            $vehicle_number = $regi->vehicle_number;
            $mobile_number = $regi->mobile_number;
            $amount = $regi->amount;
            $transaction_id = $regi->transaction_id;
            $payment_type=$regi->payment_type;
            $vehicle_type = $regi->vehicle_type;
            $vehicle_out_date_time = $regi->vehicle_out_time;
            $vehicle_status = 'Out';
            $active_plans_row = GetVendorActivatedPlan($vendor_id);
            $ct_amount = $regi->ct_amount;
            if(isset($regi->payment_type) && $regi->payment_type!=""){
                $payment_type= $regi->payment_type;
            }else{
              $payment_type="Cash";  
            }
            $passid = 0;
           
            $select_monthly_pass = MonthlyPass::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('status', 1)
                ->whereDate('end_date', '>=', date('Y-m-d H:i:s'))
                ->limit(1)
                ->get();

            if ($select_monthly_pass->count() == 1) {
                $amount = 0.0;
                $payment_type = "Monthly Pass";
                $val_vehicle_passid = $select_monthly_pass->first();
                $passid = $val_vehicle_passid->id;
            }

        
        
            $select_monthly_pass = VehiclePreBooking::where('vendor_id', $vendor_id)
            ->where('vehicle_number', $vehicle_number)
            ->where('status', 'Booked')
            ->where('vehicle_type', $vehicle_type)
            ->limit(1)
            ->get();
        
            if ($select_monthly_pass->count() == 1) {
                $amount = 0.0;
                $payment_type = "Monthly Pass";
            }
            
            $select_vehicle = VehicleBooking::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('vehicle_status', 'In')
                ->first();

            

            if ($select_vehicle) {
                $booking_id = $select_vehicle->id;		
                $vehicleBooking = VehicleBooking::find($booking_id);

                if ($vehicleBooking) {
                    $vehicleBooking->out_amount = $amount;
                    $vehicleBooking->out_pay_type = $payment_type;
                    $vehicleBooking->vehicle_out_date_time = $vehicle_out_date_time;
                    $vehicleBooking->staff_out = $staff_id;
                    $vehicleBooking->vehicle_status = $vehicle_status;
                    $vehicleBooking->pass_id = $passid;
                    $vehicleBooking->save();

                    if (!empty($val_vehicle_id['tagid'])) {
                        $para['tagId'] = $val_vehicle_id['tagid'];
                        $para['outReason'] = $payment_type;
                        // vehicleOut($para);
                    }

                    SensitiveVechilesNotify($vendor_id, $vehicle_number, $booking_id);
                    OutPreBookingVechile($vendor_id, $vehicle_number);
                
                    $vendor = PaUsers::select(DB::raw("CONCAT_WS(' ', first_name, last_name) as vendor_name, parking_name, note"))
                            ->where('id', $vendor_id)
                            ->first();
                        
                        $vendor_ow = (array) $vendor;
                        
                        if ($mobile_number && $active_plans_row['sms_notification'] == 1) {
                            $customer_id = $val_vehicle_id['customer_id'];
                            $customer_name = '';
                        
                            if ($customer_id != 0) {
                                $customer = PaUsers::select(DB::raw("CONCAT_WS(' ', first_name, last_name) as customer_name"))
                                    ->where('id', $customer_id)
                                    ->first();
                        
                                $customer_row = (array) $customer;
                                $customer_name = $customer_row['customer_name'] . ' ';
                            }
                
                            $sms_row = Smsgetways::where('vendorid', $vendor_id)->first();

                            $parking_name = $vendor_ow['parking_name'];
                            $vehicle_in_date_time = date('Y-m-d h:i A', $vehicle_out_date_time);
                            $message = '';

                            if ($vendor_id == '201') {
                                $message = 'Hello, Your vehicle ' . $vehicle_number . ' is parked on ' . date('Y-m-d h:i A', $vehicle_in_date_time) . ', Rs.' . $amount . ', Click here ' . $sms_row->vendorurl . ' amazing offers. Have a pleasant experience.';
                                $message .= ' Regards';
                                $message .= ' Pink Square Mall';
                            } else {
                                $message = 'Hello, ' . $customer_name . ' Your vehicle ' . $vehicle_number . ' ' . $vehicle_type . ' successfully out from ' . $vendor_ow['parking_name'] . ' on ' . date('Y-m-d h:i A', $vehicle_out_date_time) . ', Rs. ' . $amount . ', View More https://bit.ly/3iNx8b0';
                            }
                            //$message = urlencode($sendmessage);
                            //SendSMSNotification($mobile_number, $message);
                             $smsTotal=smsBailanceVendor($vendor_id);
                            $BALANCE=$smsTotal[0]['BALANCE'];
                            if($BALANCE==0){
                                SendSMSNotification($mobile_number, $message);
                                
                            }else{
                                SendSMSNotificationActiveVendorwise($mobile_number, $message,$vendor_id,'Out'); 
                            }
                        // $sendmessage = 'Hello, '.$customer_name.'Your vehicle '.$vehicle_number.' '.$vehicle_type.' successfully out from '.$vendor_ow['parking_name'].' on '.date('Y-m-d h:i A',$vehicle_out_date_time).', Rs. '.$amount.', View More https://bit.ly/3iNx8b0';
                        // $message = urlencode($sendmessage);
                        // SendSMSNotification($mobile_number, $message);
                    }
        
                    //if($amount > 0){
                        //$payment_type='Cash';
                       PaymentHistory::insert([
                            'booking_id' => $booking_id,
                            'amount' => $amount,
                            'payment_type' => $payment_type,
                            'payment_date_time' => $vehicle_out_date_time,
                            'staff_id' => $staff_id,
                            'transaction_id' => $transaction_id
                        ]);
                        
                        VehicleBooking::where('id', $booking_id)
                            ->increment('total_amount', $amount);
                    //}
        
                    if ($ct_amount > 0) {
                        $payment_type = 'Cash';
                        $act_amount = '-' . $ct_amount;
                    
                        PaymentHistory::insert([
                            'booking_id' => $booking_id,
                            'amount' => $act_amount,
                            'payment_type' => $payment_type,
                            'payment_date_time' => $vehicle_out_date_time,
                            'staff_id' => $staff_id
                        ]);
                    
                        VehicleBooking::where('id', $booking_id)->increment('total_amount', $act_amount);
                    }
                    
                    $array['error_code'] = 200;
                    $array['note']=isset($vendor_ow['note'])?$vendor_ow['note']:"";
                    $array['message'] = 'Vehicle Out Successfully';
                } else {
                    $array['error_code'] = 400;
                    $array['message'] = 'Some occurred error';
                }
            }
            else {
                $array['error_code'] = 400;
                $array['message'] = 'Vehicle not in Parking';
            }
        } else {
            $array['error_code'] = 400;
            $array['message'] = 'Please provide request parameter';
        }
        $finalarray['response'] = $array;
        echo json_encode($finalarray);
    }

    public function vehicle_history(Request $request)
    {
        $regi =  (object)$request->input();
        $currentdate = date('Y-m-d');
        if($regi){
            $rec_limit = 10;
            $limit='10';
            if( isset($regi->page ) ) {
                    $page = $regi->page;
                    $offset = $rec_limit * $page ;
                    $left_rec = $rec_count - ($page * $rec_limit);
                    $limit="LIMIT $offset, $rec_limit";
                }
            
            $vendor_id = $regi->vendor_id;
            $start_date = $regi->start_date;
            $end_date = $regi->end_date;
            if(isset($regi->vehicle_status) && !empty($regi->vehicle_status)){
                $vehiclestatus="  AND vb.vehicle_status = '".$regi->vehicle_status."'";
            }
            if($start_date && $end_date){
                $getstart = $start_date;
                $getend = $end_date;
            } else {
                $active_plans_row = GetVendorActivatedPlan($vendor_id);
                if($active_plans_row['report_export_capacity'] > 0){
                    $getstart = date('Y-m-d',strtotime('-'.$active_plans_row['report_export_capacity'].' months'));
                    $getend = $currentdate;
                } else {
                    $getstart = $currentdate;
                    $getend = $currentdate;
                }
            }
           
            //echo "SELECT vb.*, sdin.staff_name as in_staff_name, v.parking_name, CONCAT_WS(' ', v.address,v.city,v.state) as parking_address FROM `vehicle_booking` as vb LEFT JOIN pa_users as v ON vb.vendor_id = v.id LEFT JOIN staff_details as sdin ON vb.staff_in = sdin.staff_id Where vb.vendor_id = ".$vendor_id." ".$vehiclestatus." AND (FROM_UNIXTIME(vb.vehicle_in_date_time, '%Y-%m-%d') >= '".$getstart."' AND FROM_UNIXTIME(vb.vehicle_in_date_time, '%Y-%m-%d') <= '".$getend."') ORDER BY vb.id DESC $limit"; die;
            $select_vehicle = VehicleBooking::
             leftJoin('pa_users as v', 'vehicle_booking.vendor_id', '=', 'v.id')
            ->leftJoin('staff_details as sdin', 'vehicle_booking.staff_in', '=', 'sdin.staff_id')
            // ->select('vehicle_booking.id','vehicle_booking.customer_id', 'vehicle_booking.staff_in', 'vehicle_booking.vehicle_status','vehicle_booking.vehicle_number','vehicle_booking.vehicle_type','vehicle_booking.staff_vehicle_type','vehicle_booking.qr_type','vehicle_booking.vehicle_in_date_time','vehicle_booking.vehicle_out_date_time', 'sdin.staff_name as in_staff_name', 'v.parking_name', DB::raw("CONCAT_WS(' ', v.address, v.city, v.state) as parking_address"))
            ->select('vehicle_booking.id','vehicle_booking.customer_id', 'vehicle_booking.staff_in', 'vehicle_booking.vehicle_status','vehicle_booking.vehicle_number','vehicle_booking.vehicle_type','vehicle_booking.staff_vehicle_type','vehicle_booking.qr_type','vehicle_booking.vehicle_in_date_time','vehicle_booking.vehicle_out_date_time', 'sdin.staff_name as in_staff_name', 'v.parking_name', DB::raw("CONCAT_WS(' ', v.address, v.city, v.state) as parking_address"))
            ->where('vehicle_booking.vendor_id', $vendor_id)
            ->whereRaw("FROM_UNIXTIME(vehicle_booking.vehicle_in_date_time, '%Y-%m-%d') >= ?", [$getstart])
            ->whereRaw("FROM_UNIXTIME(vehicle_booking.vehicle_in_date_time, '%Y-%m-%d') <= ?", [$getend])
            ->with('payment_history1')
            // ->with('monthly_pass1')
            ->orderBy('vehicle_booking.id', 'DESC')
            ->limit($limit)
            ->get();
            // echo $select_vehicle->toSql();exit;
            // Core $select_vehicle = $con->query("SELECT vb.*, sdin.staff_name as in_staff_name, v.parking_name, CONCAT_WS(' ', v.address,v.city,v.state) as parking_address FROM `vehicle_booking` as vb LEFT JOIN pa_users as v ON vb.vendor_id = v.id LEFT JOIN staff_details as sdin ON vb.staff_in = sdin.staff_id Where vb.vendor_id = ".$vendor_id." ".$vehiclestatus." AND (FROM_UNIXTIME(vb.vehicle_in_date_time, '%Y-%m-%d') >= '".$getstart."' AND FROM_UNIXTIME(vb.vehicle_in_date_time, '%Y-%m-%d') <= '".$getend."') ORDER BY vb.id DESC $limit");

            //Commented//$select_vehicle = $con->query("SELECT * FROM `vehicle_booking` Where vendor_id=".$vendor_id." AND vehicle_status = 'In' AND (FROM_UNIXTIME(vehicle_in_date_time, '%Y-%m-%d') >= '".$getstart."' AND FROM_UNIXTIME(vehicle_in_date_time, '%Y-%m-%d') <= '".$getend."') ORDER BY id DESC");
            $numrows_vehicle = $select_vehicle->count();
            $select_vehicle = $select_vehicle->toArray();
            $finalArray = array();
            if ($numrows_vehicle > 0) {
                foreach ($select_vehicle as $row) {
                   
                    $array = array();
                    $id = $row['id'];
                    $customer_id = $row['customer_id'];
                    $staff_in = $row['staff_in'];	
                    $vehicle_status = $row['vehicle_status'];
                    $vehicle_number = $row['vehicle_number'];
                    $vehicle_type = $row['vehicle_type'];
                    $staff_vehicle_type = $row['staff_vehicle_type'];
                    $array['id'] = $row['id'];
                    $array['customer_id'] = $customer_id;
                    $array['staff_name'] = $row['in_staff_name'];
                    $array['parking_name'] = $row['parking_name'];
                    $array['parking_address'] = $row['parking_address'];
                    $array['vehicle_status'] = $vehicle_status;
                    $array['vehicle_number'] = $vehicle_number;
                    $array['vehicle_type'] = $row['vehicle_type'];
                    $array['qr_type'] = $row['qr_type'];
                    $array['staff_vehicle_type'] = $staff_vehicle_type;
                    $array['vehicle_in_date_time'] = date('d-m-Y h:i A',$row['vehicle_in_date_time']);
                    if($vehicle_status=='In'){
                        $currentTime = time();
                        $diff = abs($currentTime - $row['vehicle_in_date_time']);	
                    } else {
                        $array['vehicle_out_date_time'] = date('d-m-Y h:i A',$row['vehicle_out_date_time']);
                        $diff = abs($row['vehicle_out_date_time'] - $row['vehicle_in_date_time']);
                    }
                    

                    $fullDays    = floor($diff/(60*60*24));   
                    $fullHours   = floor(($diff-($fullDays*60*60*24))/(60*60));   
                    $fullMinutes = floor(($diff-($fullDays*60*60*24)-($fullHours*60*60))/60);
                    $array['vehicle_in_diff'] = $fullDays.' Day, '. $fullHours.' Hours, '.$fullMinutes.' Minutes';
                    $array['time_in_days'] = $fullDays;
                    $array['time_in_hours'] = $fullHours;
                    $array['time_in_minutes'] = $fullMinutes;
                   

                    if($row['qr_type']=='monthly_pass'){
                        $array['total_parking_price'] = 0;
                        $array['due_parking_price'] = 0;
                    } else {
                        $fare_calculation = CalculateFareAmount($vendor_id,$vehicle_number,$vehicle_type);
                        $array['total_parking_price'] = $fare_calculation['total_parking_price'];
                        $array['due_parking_price'] = $fare_calculation['due_parking_price'];
                    }
                    // $vendor_id = "689";
                    
                    if ($staff_vehicle_type == 'yes') {
                        
                        $select_fare_query = FareInfo::
                            where('user_id', $vendor_id)
                            ->where('veh_type', 'Staff')
                            // ->where('veh_type', '4W')
                            ->where('hr_status', 'bs_fare')
                            ->first();
                    } else {
                        
                        $select_fare_query = FareInfo::
                            where('user_id', $vendor_id)
                            ->where('veh_type', $row['vehicle_type'])
                            ->where('hr_status', 'bs_fare')
                            ->first();
                    }
                    if($select_fare_query){
                        $fare_row = $select_fare_query->toArray();
                        $array['base_fare_amount'] = $fare_row['amount'];
                    }else{
                        $array['base_fare_amount'] = 0;
                    }
                   
                    $finalpayment = [];
                    $select_payment = PaymentHistory::where('booking_id', $id)
                        ->orderBy('id', 'DESC')
                        ->get();
                       
                    if ($select_payment->count() > 0) {
                        $advance_amount = [];
                        foreach ($select_payment as $row_payment) {
                            $payment_array = [
                                'id' => $row_payment->id,
                                'amount' => $row_payment->amount,
                                'payment_type' => $row_payment->payment_type,
                                'transaction_id' => $row_payment->transaction_id,
                                'payment_date_time' => date('d-m-Y h:i A', $row_payment->payment_date_time),
                            ];
                    
                            $finalpayment[] = $payment_array;
                            $advance_amount[] = $row_payment->amount;
                        }
                    
                        $array['advance_amount'] = array_sum($advance_amount);
                        $array['payment_history'] = $finalpayment;
                    } else {
                        $array['advance_amount'] = 0;
                    }
                    
                    $select_fares = [];
                    if ($staff_vehicle_type == 'yes') {
                        $select_fares = FareInfo::where('user_id', $vendor_id)
                            ->where('veh_type', 'Staff')
                            ->get();
                    } else {
                        $select_fares = FareInfo::where('user_id', $vendor_id)
                            ->where('veh_type', $row['vehicle_type'])
                            ->get();
                    }

                    if ($select_fares->count() > 0) {
                        $fares_amount = $select_fares->toArray();
                        $array['fares_info'] = $fares_amount;
                    }

                    $select_monthly_pass = MonthlyPass::where('vendor_id', $vendor_id)
                        ->where('vehicle_number', $vehicle_number)
                        ->where('status', 1)
                        ->first();

                    if ($select_monthly_pass) {
                        $array['pass_status'] = 'Active';
                        $array['monthly_pass'] = $select_monthly_pass;
                    }

                    if ($customer_id != 0) {
                        $select_customer = PaUsers::select('id as customer_id', 'first_name', 'last_name', 'mobile_number', 'address', 'state', 'city')
                            ->where('id', $customer_id)
                            ->first();

                        if ($select_customer) {
                            $array['customer_details'] = $select_customer;
                        }
                    }

                    $VehicleArray[] = $array;
                }
                $finalArray['error_code'] = 200;
                $finalArray['vehicle_history'] = $VehicleArray;
            } else {
                $finalArray['error_code'] = 400;
                $finalArray['message'] = 'No Vehicle Booking found';
            }
        } else {
            $finalArray['error_code'] = 400;
            $finalArray['message'] = 'Please provide request parameter';
        }
        
        
        // $resparray['response'] = $finalArray;
        // echo json_encode($resparray);
        return response()->json($finalArray);

    }
}
