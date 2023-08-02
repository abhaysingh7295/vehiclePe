<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\VendorSubscriptions; 
use App\Models\BlockVehicles; 
use App\Models\WantedVehicles; 
use App\Models\VehicleBooking;
use App\Models\SensitiveVehicle;

use App\Models\VehicleMissing;
use App\Models\Vendor;
use App\Models\PaUsers;
use App\Models\PaymentHistory;
use App\Models\VehiclePreBooking;
use App\Models\FareInfo;



use App\Mail\MissingVehicleNotification;
use App\Mail\Smsgetways;

use Illuminate\Support\Facades\Mail;

// use Illuminate\Support\Facades\Redirect;
// use Illuminate\Support\Facades\URL;

function GetVendorActivatedPlan($vendor_id) {
    $activate_subscriptions_plans = VendorSubscriptions::where("vendor_id",$vendor_id)->where("status",1)->first();
    
    // $active_plans_row_row = sizeof($activate_subscriptions_plans);

    if ($activate_subscriptions_plans) {
        $active_plans_row = $activate_subscriptions_plans->toArray();
        
        $plan_end = date('d-m-Y', $active_plans_row['subscription_end_date']);

        $t = time();
        $today = date('d-m-Y', $t);
        if ($t < $active_plans_row['subscription_end_date']) {
            return $active_plans_row;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function GetVendorsBlockedVechiles($vendor_id) {
    $Array_block_vechiles = array();
    $active_plans_row = GetVendorActivatedPlan($vendor_id);
    if (isset($active_plans_row['block_vehicle']) && $active_plans_row['block_vehicle']== 1) {
        $Array_block_vechiles = BlockVehicles::where("vendor_id", $vendor_id)->pluck('vehicle_number')->toArray();
    }
    return $Array_block_vechiles;
}

function GetVendorsWantedVechiles($vendorId, $vehicleNumber, $bookingId)
{
    $activePlansRow = GetVendorActivatedPlan($vendorId);

    if (isset($activePlansRow['wanted_vehicle']) && $activePlansRow['wanted_vehicle'] == 1 || 1==1) {
       
        $wantedVehicles = WantedVehicles::where('vendor_id', $vendorId)->where('vehicle_number', $vehicleNumber)->get();
        $numRowsVehicle = count($wantedVehicles);

        if ($numRowsVehicle > 0) {
            
            $selectVehicle = VehicleBooking::with(['vendor' => function ($query) {
                $query->select('user_email as vendor_email', 'mobile_number as vendor_mobile')
                    ->selectRaw("CONCAT_WS(' ', first_name, last_name) as vendor_name");
                }])
                ->where('id', $bookingId)
                ->where('vendor_id', $vendorId)
                ->first();
        
            if ($selectVehicle) {
                $vendor = $selectVehicle->vendor;
                // Access the vendor details like vendor name, email, mobile number
                $selectVehicle->vendor_name = $vendor->vendor_name;
                $selectVehicle->vendor_email = $vendor->user_email;
                $selectVehicle->mobile_number = $vendor->mobile_number;

                $message = view('emails.wanted_vehicle',$selectVehicle)->render();
                $to = $selectVehicle->vendor_email;
                $subject = 'Wanted Vehicle Found';

                // Send email notification
                SendEmailNotification($to, $subject, $message);
            }    
            
        }
    }
}





function SensitiveVechilesNotify($vendorId, $vehicleNumber, $bookingId)
{
    $sensitiveVehicles = SensitiveVehicle::leftJoin('police_stations', 'police_stations.id', '=', 'sensitive_vehicle.polic_station')
        ->leftJoin('login', 'login.id', '=', 'sensitive_vehicle.admin_id')
        ->where('sensitive_vehicle.vehicle_number', $vehicleNumber)
        ->where('sensitive_vehicle.status', 0)
        ->select('sensitive_vehicle.*', 'police_stations.mobile_number as police_stations_mobile', 'login.mobileno as admin_mobile')
        ->get();

    $numRowsVehicle = $sensitiveVehicles->count();
    
    if ($numRowsVehicle > 0) {
        $rowSensitive = $sensitiveVehicles->first();
        // $bookingId = 1367316950;
        // $vendorId = 71229487;
        $selectVehicle = VehicleBooking::join('pa_users as v', 'vehicle_booking.vendor_id', '=', 'v.id')
            ->where('vehicle_booking.id', $bookingId)
            ->where('vehicle_booking.vendor_id', $vendorId)
            ->select('vehicle_booking.*', DB::raw("CONCAT_WS(' ', v.first_name, v.last_name) as vendor_name"), 'v.user_email as vendor_email', 'v.mobile_number as vendor_mobile', 'v.parking_name', DB::raw("CONCAT_WS(' ', v.address, v.city, v.state) as vendor_address"))
            ->first();
        
        if ($selectVehicle) {
            $vehicleStatus = $selectVehicle->vehicle_status;
            $vehicleNumber = $selectVehicle->vehicle_number;
            $mobileNumber = $selectVehicle->mobile_number;
            $vehicleInDateTime = $selectVehicle->vehicle_in_date_time;
            $vehicleOutDateTime = $selectVehicle->vehicle_out_date_time;
            $vendorName = $selectVehicle->vendor_name;
            $vendorMobile = $selectVehicle->vendor_mobile;
            $vendorEmail = $selectVehicle->vendor_email;
            $vendorAddress = $selectVehicle->vendor_address;
            $parkingName = $selectVehicle->parking_name;

            $view = view('emails.sensitive_vehicle',$selectVehicle)->render();

            $data = [
                'founded_vendor_id' => $vendorId,
                'find' => '1',
            ];
            SensitiveVehicle::where('id', $rowSensitive->id)->update($data);

            $to = ['support@thedigitalparking.com', 'monujangid161990@gmail.com'];
            $subject = 'Sensitive Vehicle Found';

            $datas['parameters'] = array(array('name' => 'parking_name', 'value' => $parkingName), array('name' => 'vehicle', 'value' => $vehicleNumber), array('name' => 'date', 'value' => date('d/m/Y h:i A', $vehicleInDateTime)), array('name' => 'status', 'value' => $vehicleStatus), array('name' => 'vendor_name', 'value' => $vendorName), array('name' => 'mobile', 'value' => $vendorMobile), array('name' => 'email', 'value' => $vendorEmail), array('name' => 'address', 'value' => $vendorAddress));
            $datas['template_name'] = 'sensitive_vehicle';
            $datas['broadcast_name'] = 'sensitive vehicle';
            //whatsAppMessage('9694449191', $datas);
            whatsAppMessage($rowSensitive['police_stations_mobile'], $datas);
            whatsAppMessage($rowSensitive['admin_mobile'], $datas);
            SendEmailNotification($to, $subject, $view);
        }
    }
}

function GetMissingVehicleNumber($vendorId, $vehicleNumber, $bookingId)
{
    $selectVehicle = VehicleBooking::where('vehicle_number', $vehicleNumber)
        ->where('vehicle_status', 'In')
        ->get();
        
    if ($selectVehicle->count() > 1) {
        
        $selectVehicleMissing = VehicleMissing::where('vehicle_number', $vehicleNumber)->get();

        if ($selectVehicleMissing->count() == 0) {
            $insertVehicleMissing = new VehicleMissing(['vehicle_number' => $vehicleNumber]);
            $insertVehicleMissing->save();
        } else {
            $updateVehicleMissing = VehicleMissing::where('vehicle_number', $vehicleNumber)
                ->update(['vehicle_number' => $vehicleNumber]);
        }
       
        $selectBookVehicle = VehicleBooking::join('pa_users as v', 'vehicle_booking.vendor_id', '=', 'v.id')
            ->select('vehicle_booking.*', 'v.first_name', 'v.last_name', 'v.user_email', 'v.mobile_number', 'v.parking_name', 'v.address', 'v.city', 'v.state')
            ->where('vehicle_booking.id', $bookingId)
            ->where('vehicle_booking.vendor_id', $vendorId)
            ->first();

        if ($selectBookVehicle) {
            $selectBookVehicle->vehicle_in_date_time = date('d/m/Y h:i A', strtotime($selectBookVehicle->vehicle_in_date_time));
            $selectBookVehicle->vendor_name = $selectBookVehicle->first_name . ' ' . $selectBookVehicle->last_name;
            $selectBookVehicle->vendor_mobile = $selectBookVehicle->mobile_number;
            $selectBookVehicle->vendor_email = $selectBookVehicle->user_email;
            $selectBookVehicle->vendor_address = $selectBookVehicle->address . ' ' . $selectBookVehicle->city . ' ' . $selectBookVehicle->state;
           
            $to = "maonujangid161990@gmail.com";
            $subject = $selectBookVehicle->vehicle_number . ' Missing Vechile Found';
            $view = view('emails.missing_vehicle', $selectBookVehicle)->render();
           
            SendEmailNotification($to, $subject, $view);
        }
    }
}


function whatsAppMessage($mobile, $paramter) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://live-server-748.wati.io/api/v1/sendTemplateMessage?whatsappNumber=91" . $mobile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($paramter),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI4YWExYjUzMi1jNWJkLTQ5OTktYThiNy04ZDBmYTI1YjZlMjIiLCJ1bmlxdWVfbmFtZSI6ImFiaGF5c2luZ2g3Mjk1QGdtYWlsLmNvbSIsIm5hbWVpZCI6ImFiaGF5c2luZ2g3Mjk1QGdtYWlsLmNvbSIsImVtYWlsIjoiYWJoYXlzaW5naDcyOTVAZ21haWwuY29tIiwiYXV0aF90aW1lIjoiMDIvMDUvMjAyMiAwOTo1Njo1MCIsImRiX25hbWUiOiJ3YXRpTGl2ZTc0OCIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6WyJCUk9BRENBU1RfTUFOQUdFUiIsIlRFTVBMQVRFX01BTkFHRVIiLCJERVZFTE9QRVIiXSwiZXhwIjoyNTM0MDIzMDA4MDAsImlzcyI6IkNsYXJlX0FJIiwiYXVkIjoiQ2xhcmVfQUkifQ.HCxVGKATwmlhRRPZOxPYU0SjcFc1qqGRNPK5vlJW568",
            "Content-Type: application/json-patch+json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        //echo $response;
    }
}

function SendEmailNotification($to, $subject, $message) {
    $to = $to;
    $subject = $subject;
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: <support@thedigitalparking.com>' . "\r\n";
    mail($to, $subject, $message, $headers);
}

function smsBalanceVendor($vendor_id) {
    $select_sms = Smsgetways::where('vendorid', $vendor_id)->first();
    $apikey = $select_sms->apikey;

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://sms.thedigitalparking.com/app/miscapi/" . $apikey . "/getBalance/true/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    return json_decode($response, true);
}

function SendSMSNotification($mobileNumber, $message) {

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2?authorization=4bEMwD52ysRlSvPtW8X1zUKdJVN7IurmeFoaOkjcxT9AL0qQhflaq9Ef08WUCncQ7KOwTM1pb6HxPVsN&route=dlt&sender_id=PREKIN&message=121424&variables_values=" . urlencode($message) . "=&flash=0&numbers=" . $mobileNumber,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    return $response;
    
}

function SendSMSNotificationActiveVendorwise($mobileNumber, $message, $vendor_id, $smstype) {
    $select_sms = Smsgetways::where('vendorid', $vendor_id)->first();
    $apikey = $select_sms->apikey;
    $campaign = $select_sms->campaign;
    $type = $select_sms->type;
    $routeid = $select_sms->routeid;
    $senderid = $select_sms->senderid;

    if ($smstype == 'In') {
        $template_id = $select_sms->template_id;
    } elseif ($smstype == 'Out') {
        $template_id = $select_sms->template_id2;
    } elseif ($smstype == 'Monthly_pass') {
        $template_id = $select_sms->template_id3;
    } else {
        $template_id = $select_sms->template_id4;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://sms.thedigitalparking.com/app/smsapi/index.php?key=" . $apikey . "&campaign=0&routeid=" . $routeid . "&type=" . $type . "&contacts=" . $mobileNumber . "&senderid=" . $senderid . "&msg=" . urlencode($message) . "&template_id=" . $template_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
   
    return $response;
}


function vehicleOut($paramter) {
    $sessionid = getSessionId();
    
    $response = Http::withHeaders([
        'sessionId' => $sessionid,
        'Content-Type' => 'application/json',
    ])->post('http://43.241.62.27:8060/client/vehicleOut', $paramter);

    if ($response->failed()) {
        // Handle the error if needed
        // $response->status(); to get the status code
        // $response->body(); to get the response body
    } else {
        // Handle the successful response if needed
        // $response->body(); to get the response body
    }
}

function getSessionId() {
    $response = Http::get('http://43.241.62.27:8060/client/authenticate', [
        'username' => '1010',
        'password' => '1010',
    ]);

    if ($response->failed()) {
        // Handle the error if needed
        // $response->status(); to get the status code
        // $response->body(); to get the response body
    } else {
        return $response->body();
    }
}


function OutPreBookingVechile($vendor_id, $vehicle_number) {
    VehiclePreBooking::where('vendor_id', $vendor_id)
        ->where('vehicle_number', $vehicle_number)
        ->where('status', 'In')
        ->update(['status' => 'Out']);
}


function CalculateFareAmount($vendor_id, $vehicle_number, $vehicle_type)
{
    $return = false;
    $select_vehicle = VehicleBooking::where('vendor_id', $vendor_id)
        ->where('vehicle_number', $vehicle_number)
        ->where('vehicle_type', $vehicle_type)
        ->where('vehicle_status', 'In')
        ->first();

    if ($select_vehicle) {
        $id = $select_vehicle->id;
        $staff_vehicle_type = $select_vehicle->staff_vehicle_type;

        if ($staff_vehicle_type == 'yes') {
            $vehicle_type = 'Staff';
        }

        $total_paid = DB::table('payment_history')
            ->where('booking_id', $id)
            ->sum('amount');

        $currentTime = time();
        $diff = abs($currentTime - $select_vehicle->vehicle_in_date_time);
        $fullDays = floor($diff / (60 * 60 * 24));
        $fullHours = floor(($diff - ($fullDays * 60 * 60 * 24)) / (60 * 60));
        $fullMinutes = floor(($diff - ($fullDays * 60 * 60 * 24) - ($fullHours * 60 * 60)) / 60);

        if ($fullDays < 1) {
            if ($fullMinutes >= 0) {
                $newHrs = $fullHours + 1;
            } else {
                $newHrs = $fullHours;
            }

            $farepricesql = DB::table('fare_info')
                ->where('veh_type', $vehicle_type)
                ->where('user_id', $vendor_id)
                ->where('hr_status', '!=', 'max')
                ->orderBy('hr_status')
                ->get();

            if ($farepricesql->count() > 0) {
                $return = true;
                $price_array = [];

                foreach ($farepricesql as $select_price) {
                    $amount = $select_price->amount;
                    $initial_hr = $select_price->initial_hr;
                    $ending_hr = $select_price->ending_hr;
                    $arrayString = $initial_hr . '-' . $ending_hr . '-' . $newHrs;

                    if ($select_price->hr_status == 'bs_fare') {
                        if ($initial_hr < $newHrs && $ending_hr >= $newHrs) {
                            $price_array[$arrayString] = $amount;
                        } else if ($ending_hr <= $newHrs) {
                            $price_array[$arrayString] = $amount;
                        }
                    } else if ($select_price->hr_status == 'per_hr') {
                        if ($initial_hr < $newHrs && $ending_hr >= $newHrs) {
                            $hrcal = $newHrs - $initial_hr;
                            $price_array[$arrayString] = $hrcal * $amount;
                        } else if ($ending_hr <= $newHrs) {
                            $hrcal = $ending_hr - $initial_hr;
                            $price_array[$arrayString] = $hrcal * $amount;
                        }
                    }
                }

                $totalPrice_Calculate = array_sum($price_array);
                $total_due_Amount = $totalPrice_Calculate - $total_paid;

                $parking_price = $total_due_Amount + $total_paid;
            } else {
                $return = true;
                $parking_price = 0;
                $total_due_Amount = $parking_price - $total_paid;
            }
        } else if ($fullDays >= 1) {
            if ($fullHours >= 1) {
                $newDays = $fullDays + 1;
            } else {
                if ($fullMinutes > 0) {
                    $newDays = $fullDays + 1;
                } else {
                    $newDays = $fullDays;
                }
            }

            $farepricesql = FareInfo::where('veh_type', $vehicle_type)
                ->where('user_id', $vendor_id)
                ->where('hr_status', 'max')
                ->first();

            if ($farepricesql) {
                $return = true;
                $amount = $farepricesql->amount;
                $parking_price = $newDays * $amount;
                $total_due_Amount = $parking_price - $total_paid;
            } else {
                $return = true;
                $parking_price = 0;
                $total_due_Amount = $parking_price - $total_paid;
            }
        }
    }

    if ($return) {
        $array['total_parking_price'] = $parking_price;
        $array['due_parking_price'] = $total_due_Amount;
    } else {
        $array['total_parking_price'] = 0;
        $array['due_parking_price'] = 0;
    }

    return $array;
}
