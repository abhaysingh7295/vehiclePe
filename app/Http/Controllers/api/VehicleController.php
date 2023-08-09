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
use App\Models\BookingServices;

use Validator;
use Auth;

use DB;

class VehicleController extends Controller
{

    public function vehicle_in(Request $request)
    {
        try {
            $input  = $request->all();
            //validations
            $validator = Validator::make($input, [
                'staff_id' => 'required',
                'vendor_id' => 'required',
                'vehicle_number' => 'required',
                'mobile_number' => 'required',
            ]);
            if ($validator->fails()) {
                $err = (new ApiController)->validator_response($request, $validator);
                return response()->json([
                    'success' => false,
                    'message' => $err,
                ], 200);
            } else {
                //after validation succcess
                $staff_id               =  $input['staff_id'];
                $vendor_id              =  $input['vendor_id'];
                $vehicle_number         =  $input['vehicle_number'];
                $mobile_number          =  $input['mobile_number'];
                $amount                 =  $input['amount'];
                $vehicle_type           =  $input['vehicle_type'];
                $vehicle_in_date_time   =  $input['vehicle_in_time'];
                $latitude               =  $input['in_latitude'];
                $longitude              =  $input['in_longitude'];
                $qr_type                =  $input['qr_type'];
                $payment_type           =  $input['payment_type'];
                $transaction_id         =  $input['transaction_id'];

                $sr_no                  = !empty($input['sr_no']) ? $input['sr_no'] : '';
                $tagId                  = !empty($input['tagId']) ? $input['tagId'] : '';
                if ($input['staff_type'] == 'yes') {
                    $staff_vehicle_type = 'yes';
                } else {
                    $staff_vehicle_type = 'no';
                }
                $parking_type = 'Direct';
                if (isset($input['payment_type']) && $input['payment_type'] != "") {
                    $payment_type =  $input['payment_type'];
                } else {
                    $payment_type = "Cash";
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
                    ->get();
                if (count($select_monthly_pass) == 1) {
                    $amount = 0.0;
                    $parking_type = 'Vehicle Pass';
                    $val_vehicle_passid = $select_monthly_pass->first()->id;
                    $passid = $val_vehicle_passid;
                }

                $check_pre_booking = VehiclePreBooking::select('id')
                    ->where('vendor_id', $vendor_id)
                    ->where('vehicle_number', $vehicle_number)
                    ->where('status', 'Booked')
                    ->where('vehicle_type', $vehicle_type)
                    ->get();

                if (count($check_pre_booking) == 1) {
                    $amount = 0.0;
                    $parking_type = 'Vehicle Pass';
                }

                $vehicle_status = 'In';
                $active_plans_row = GetVendorActivatedPlan($vendor_id);
                if ($mobile_number) {

                    $select_user_name = PaUsers::select('id')->where('mobile_number', $mobile_number)->where('user_role', 'customer')->first();
                    if (!empty($select_user_name->id)) {
                        // $val_user = PaUsers::where('mobile_number', $mobile_number)->where('user_role', 'customer')->first();
                        $customer_id = $select_user_name->id;
                    } else {

                        $reflength = 10;
                        $refchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                        $referral_code = substr(str_shuffle($refchars), 0, $reflength);

                        $insert_data = [
                            'mobile_number' => $mobile_number,
                            'user_role' => 'customer',
                            'date_of_birth' => isset($date_of_birth) ? $date_of_birth : "",
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

                $vehicleImage           =  $input['vehicle_image'];
                $vehicleImage_filename  = isset($_FILES['vehicle_image']['name']) ? $_FILES['vehicle_image']['name'] : "";
                $vehicleImage_path      = "../../uploads/" . $vehicleImage_filename;
                $NewFileName            = '';
                $num_rows_c = $select_vehicle->count();
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
                if ($num_rows_c == 0) {
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

                        if ($input['services']) {
                            foreach ($input['services'] as $services) {
                                BookingServices::insert([
                                    'bookingid' => $booking_id,
                                    'categoryid' => $services->cat_id,
                                    'subcategoryid' => $services->subcat_id,
                                    'amount' => $services->amount,
                                ]);
                            }
                        }
                        GetVendorsWantedVechiles($vendor_id, $vehicle_number, $booking_id);
                        SensitiveVechilesNotify($vendor_id, $vehicle_number, $booking_id);
                        GetMissingVehicleNumber($vendor_id, $vehicle_number, $booking_id);

                        $vendor_ow = PaUsers::select(DB::raw("CONCAT(first_name,' ',last_name) as vendor_name"), 'parking_name', 'note')
                            ->where('id', $vendor_id)
                            ->first();

                        // if (($mobile_number) && ($active_plans_row['sms_notification'] == 1)) {

                        //     $customer_name = '';
                        //     if ($customer_id != 0) {
                        //         $val_user = PaUsers::select(DB::raw("CONCAT(first_name, ' ', last_name) as name"))->where('id', $customer_id)->first();

                        //         $customer_name = $val_user->name;
                        //     }
                        //     $sendmessage = "Hello, {$customer_name}! Your vehicle {$vehicle_number} {$vehicle_type} has been parked at {$vendor_ow->parking_name} on " . date('Y-m-d h:i A', $vehicle_in_date_time) . ", Rs. {$amount}, View More " . url("/booking_invoice.php?id=" . base64_encode($booking_id));
                        //     $message = urlencode($sendmessage);
                        //     $smsTotal = smsBalanceVendor($vendor_id);
                        //     $BALANCE = $smsTotal[0]['BALANCE'];
                        //     if ($BALANCE == 0) {
                        //         SendSMSNotification($mobile_number, $message);
                        //     } else {
                        //         SendSMSNotificationActiveVendorwise($mobile_number, $sendmessage, $vendor_id, 'In');
                        //     }
                        // }

                        // if(($amount) && ($amount > 0)){
                        //$payment_type='Cash';

                        PaymentHistory::insert([
                            'booking_id' => $booking_id,
                            'amount' => $amount,
                            'payment_type' => $payment_type,
                            'payment_date_time' => $vehicle_in_date_time,
                            'staff_id' => $staff_id,
                            'transaction_id' => $transaction_id
                        ]);

                        VehicleBooking::where('id', $booking_id)
                            ->update(['total_amount' => $amount]);

                        $select_vehicles_ow = VehicleBooking::select('serial_number')->where('id', $booking_id)->first();

                        $array['error_code'] = 200;
                        $array['booking_id'] = $booking_id;
                        $array['serial_number'] = $select_vehicles_ow->serial_number;
                        // $array['note'] = $vendor_ow->note;
                        $array['message'] = 'Vehicle Park In Successfully';
                    } else {
                        $array['error_code'] = 400;
                        $array['message'] = 'Some Datebase Error';
                    }
                } else {
                    $array['error_code'] = 400;
                    $array['message'] = 'Vehicle Already in Parking';
                }

                $finalarray['response'] = $array;

                return response()->json($finalarray);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function vehicle_out(Request $request)
    {

        $input =  $request->all();
        try {
        //validations
        $validator = Validator::make($input, [
            'staff_id' => 'required',
            'vendor_id' => 'required',
            'vehicle_number' => 'required',
            'mobile_number' => 'required',
        ]);
        if ($validator->fails()) {
            $err = (new ApiController)->validator_response($request, $validator);
            return response()->json([
                'success' => false,
                'message' => $err,
            ], 200);
        } else {

            $staff_id = $input['staff_id'];
            $vendor_id = $input['vendor_id'];
            $vehicle_number = $input['vehicle_number'];
            $mobile_number = $input['mobile_number'];
            $amount = $input['amount'];
            $transaction_id = $input['transaction_id'];
            $payment_type = $input['payment_type'];
            $vehicle_type = $input['vehicle_type'];
            $vehicle_out_date_time = $input['vehicle_out_time'];
            $vehicle_status = 'Out';
            $active_plans_row = GetVendorActivatedPlan($vendor_id);
            $ct_amount = $input['ct_amount'];
            if (isset($input['payment_type']) && $input['payment_type'] != "") {
                $payment_type = $input['payment_type'];
            } else {
                $payment_type = "Cash";
            }
            $passid = 0;

            $select_monthly_pass = MonthlyPass::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('status', 1)
                ->whereDate('end_date', '>=', date('Y-m-d H:i:s'))
                ->get();

            if (count($select_monthly_pass) == 1) {
                $amount = 0.0;
                $payment_type = "Monthly Pass";
                $val_vehicle_passid = $select_monthly_pass->first()->id;
                $passid = $val_vehicle_passid;
            }

            $select_monthly_pass = VehiclePreBooking::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('status', 'Booked')
                ->where('vehicle_type', $vehicle_type)
                ->get();

            if (count($select_monthly_pass) == 1) {
                $amount = 0.0;
                $payment_type = "Monthly Pass";
            }
            // print_r([$vendor_id,$vehicle_number]);die();
            // DB::enableQueryLog();
            $select_vehicle = VehicleBooking::where('vendor_id', $vendor_id)
                ->where('vehicle_number', $vehicle_number)
                ->where('vehicle_status', 'In')
                ->first();
                // $quries = DB::getQueryLog();
                // dd($quries);

            if (!empty($select_vehicle)) {
                $val_vehicle_id =$select_vehicle;
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

                    if (!empty($val_vehicle_id->tagid)) {
                        $para['tagId'] = $val_vehicle_id->tagid;
                        $para['outReason'] = $payment_type;
                    }

                    SensitiveVechilesNotify($vendor_id, $vehicle_number, $booking_id);
                    OutPreBookingVechile($vendor_id, $vehicle_number);

                    // $vendor = PaUsers::select('parking_name', 'note', DB::raw("CONCAT(first_name, ' ', last_name) as vendor_name"))
                    //     ->where('id', $vendor_id)
                    //     ->first();

                    // if ($mobile_number && $active_plans_row['sms_notification'] == 1) {
                    //     $customer_id = $val_vehicle_id->customer_id;
                    //     $customer_name = '';

                    //     if ($customer_id != 0) {
                    //         $customer = PaUsers::select(DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"))
                    //             ->where('id', $customer_id)
                    //             ->first();
                    //         $customer_name = $customer->customer_name;
                    //     }

                    //     $sms_row = Smsgetways::where('vendorid', $vendor_id)->first();

                    //     $parking_name = $vendor->parking_name;
                    //     $vehicle_in_date_time = date('Y-m-d h:i A', $vehicle_out_date_time);
                    //     $message = '';

                    //     if ($vendor_id == '201') {
                    //         $message = 'Hello, Your vehicle ' . $vehicle_number . ' is parked on ' . date('Y-m-d h:i A', $vehicle_in_date_time) . ', Rs.' . $amount . ', Click here ' . $sms_row->vendorurl . ' amazing offers. Have a pleasant experience.';
                    //         $message .= ' Regards';
                    //         $message .= ' Pink Square Mall';
                    //     } else {
                    //         $message = 'Hello, ' . $customer_name . ' Your vehicle ' . $vehicle_number . ' ' . $vehicle_type . ' successfully out from ' . $vendor->parking_name . ' on ' . date('Y-m-d h:i A', $vehicle_out_date_time) . ', Rs. ' . $amount . ', View More https://bit.ly/3iNx8b0';
                    //     }
                    //     //$message = urlencode($sendmessage);
                    //     //SendSMSNotification($mobile_number, $message);
                    //     $smsTotal = smsBailanceVendor($vendor_id);
                    //     $BALANCE = $smsTotal[0]['BALANCE'];
                    //     if ($BALANCE == 0) {
                    //         SendSMSNotification($mobile_number, $message);
                    //     } else {
                    //         SendSMSNotificationActiveVendorwise($mobile_number, $message, $vendor_id, 'Out');
                    //     }
                    //     // $sendmessage = 'Hello, '.$customer_name.'Your vehicle '.$vehicle_number.' '.$vehicle_type.' successfully out from '.$vendor_ow['parking_name'].' on '.date('Y-m-d h:i A',$vehicle_out_date_time).', Rs. '.$amount.', View More https://bit.ly/3iNx8b0';
                    //     // $message = urlencode($sendmessage);
                    //     // SendSMSNotification($mobile_number, $message);
                    // }

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
                    $array['note'] = isset($vendor->note) ? $vendor->note : "";
                    $array['message'] = 'Vehicle Out Successfully';
                } else {
                    $array['error_code'] = 400;
                    $array['message'] = 'Some occurred error';
                }
            } else {
                $array['error_code'] = 400;
                $array['message'] = 'Vehicle not in Parking';
            }

            $finalarray['response'] = $array;
            echo json_encode($finalarray);
        }
        } catch (\Exception $e) {
            return response()->json([
                'error_code' => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function vehicle_history(Request $request)
    {
        $input = $request->all();
        $rules = [
            // 'page' => 'required',
            'vendor_id'=>'required',
            'start_date'=>'required',
            'end_date'=>'required',

        ];
        // Validation
        $Validation = Validator::make($input, $rules, []);

        if ($Validation->fails()) {
            return Response()->json(['message' => 'The Page field is required.'], 400);
        }
      
        try{
            $limit = 10;
            if (isset($input['page'])) {
                $page = $input['page'];
            } else {
                $page = 1;
            };
            $offset = ($page - 1) * $limit;
            $currentdate = date('Y-m-d');
            $vendor_id = $input['vendor_id'];
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];
            // if (isset($input['vehicle_status']) && !empty($input['vehicle_status'])) {
            //     $vehiclestatus = "  AND vb.vehicle_status = '" . $input['vehicle_status'] . "'";
            // }

            if ($start_date && $end_date) {
                $getstart = $start_date;
                $getend = $end_date;
            } else {
                $active_plans_row = GetVendorActivatedPlan($vendor_id);
                if ($active_plans_row->report_export_capacity > 0) {
                    $getstart = date('Y-m-d', strtotime('-' . $active_plans_row->report_export_capacity . ' months'));
                    $getend = $currentdate;
                } else {
                    $getstart = $currentdate;
                    $getend = $currentdate;
                }
            }


            //  DB::enableQueryLog();
            // $select_vehicle = VehicleBooking::leftJoin('pa_users as v', 'vehicle_booking.vendor_id', '=', 'v.id')
            //     ->leftJoin('staff_details as sdin', 'vehicle_booking.staff_in', '=', 'sdin.staff_id')
            //     ->select('vehicle_booking.id', 'vehicle_booking.customer_id', 'vehicle_booking.staff_in', 'vehicle_booking.vehicle_status', 'vehicle_booking.vehicle_number', 'vehicle_booking.vehicle_type', 'vehicle_booking.staff_vehicle_type', 'vehicle_booking.qr_type', 'vehicle_booking.vehicle_in_date_time', 'vehicle_booking.vehicle_out_date_time', 'sdin.staff_name as in_staff_name', 'v.parking_name', DB::raw("CONCAT_WS(' ', v.address, v.city, v.state) as parking_address"))
            //     ->where('vehicle_booking.vendor_id', $vendor_id)
            //     ->whereRaw("FROM_UNIXTIME(vehicle_booking.vehicle_in_date_time, '%Y-%m-%d') >= ?", [$getstart])
            //     ->whereRaw("FROM_UNIXTIME(vehicle_booking.vehicle_in_date_time, '%Y-%m-%d') <= ?", [$getend])
            //     ->with('payment_history1')
            //     ->orderBy('vehicle_booking.id', 'DESC')
            //     ->skip($offset)->take($limit)
            //     ->get();
                $select_vehicle = DB::table('vehicle_booking as vb')
                ->select('vb.*', 'sdin.staff_name as in_staff_name', 'v.parking_name', DB::raw("CONCAT_WS(' ', v.address, v.city, v.state) as parking_address"))
                ->leftJoin('pa_users as v', 'vb.vendor_id', '=', 'v.id')
                ->leftJoin('staff_details as sdin', 'vb.staff_in', '=', 'sdin.staff_id')
                ->where('vb.vendor_id', $vendor_id)
                // ->where('vb.vehicle_status', $regi->vehicle_status)
                ->whereBetween(DB::raw("FROM_UNIXTIME(vb.vehicle_in_date_time, '%Y-%m-%d')"), [$getstart, $getend])
                ->orderBy('vb.id','DESC')
                ->skip($offset)->take($limit)
                ->get();

            //  $quries = DB::getQueryLog();
            //  dd($select_vehicle);
            $numrows_vehicle = $select_vehicle->count();
            // $select_vehicle = $select_vehicle->toArray();
            $finalArray = array();
            if ($numrows_vehicle > 0) {
                foreach ($select_vehicle as $row) {

                    $array = array();
                    $id = $row->id;
                    $customer_id = $row->customer_id;
                    $staff_in = $row->staff_in;
                    $vehicle_status = $row->vehicle_status;
                    $vehicle_number = $row->vehicle_number;
                    $vehicle_type = $row->vehicle_type;
                    $staff_vehicle_type = $row->staff_vehicle_type;
                    $array['id'] = $row->id;
                    $array['customer_id'] = $customer_id;
                    $array['staff_name'] = $row->in_staff_name;
                    $array['parking_name'] = $row->parking_name;
                    $array['parking_address'] = $row->parking_address;
                    $array['vehicle_status'] = $vehicle_status;
                    $array['vehicle_number'] = $vehicle_number;
                    $array['vehicle_type'] = $row->vehicle_type;
                    $array['qr_type'] = $row->qr_type;
                    $array['staff_vehicle_type'] = $staff_vehicle_type;
                    $array['vehicle_in_date_time'] = date('d-m-Y h:i A', $row->vehicle_in_date_time);
                    if ($vehicle_status == 'In') {
                        $currentTime = time();
                        $diff = abs($currentTime - $row->vehicle_in_date_time);
                    } else {
                        $array['vehicle_out_date_time'] = date('d-m-Y h:i A', $row->vehicle_out_date_time);
                        $diff = abs($row->vehicle_out_date_time - $row->vehicle_in_date_time);
                    }

                    $fullDays    = floor($diff / (60 * 60 * 24));
                    $fullHours   = floor(($diff - ($fullDays * 60 * 60 * 24)) / (60 * 60));
                    $fullMinutes = floor(($diff - ($fullDays * 60 * 60 * 24) - ($fullHours * 60 * 60)) / 60);
                    $array['vehicle_in_diff'] = $fullDays . ' Day, ' . $fullHours . ' Hours, ' . $fullMinutes . ' Minutes';
                    $array['time_in_days'] = $fullDays;
                    $array['time_in_hours'] = $fullHours;
                    $array['time_in_minutes'] = $fullMinutes;


                    if ($row->qr_type == 'monthly_pass') {
                        $array['total_parking_price'] = 0;
                        $array['due_parking_price'] = 0;
                    } else {
                        $fare_calculation = CalculateFareAmount($vendor_id, $vehicle_number, $vehicle_type);
                        $array['total_parking_price'] = $fare_calculation['total_parking_price'];
                        $array['due_parking_price'] = $fare_calculation['due_parking_price'];
                    }
                  
                    if ($staff_vehicle_type == 'yes') {

                        $select_fare_query = FareInfo::where('user_id', $vendor_id)
                            ->where('veh_type', 'Staff')
                            // ->where('veh_type', '4W')
                            ->where('hr_status', 'bs_fare')
                            ->first();
                    } else {

                        $select_fare_query = FareInfo::where('user_id', $vendor_id)
                            ->where('veh_type', $row->vehicle_type)
                            ->where('hr_status', 'bs_fare')
                            ->first();
                    }
                    if ($select_fare_query) {
                        $fare_row = $select_fare_query->toArray();
                        $array['base_fare_amount'] = $fare_row['amount'];
                    } else {
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
                            ->where('veh_type', $row->vehicle_type)
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
        return response()->json($finalArray);

        } catch (\Exception $e) {
            return response()->json([
                'error_code' => 400,
                'message' => $e->getMessage(),
            ], 200);
        }
    }
}
