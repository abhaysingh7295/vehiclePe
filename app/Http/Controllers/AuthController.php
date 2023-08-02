<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\StaffDetails; 
use App\Models\PaUsers; 
use App\Models\FareInfo; 

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user_login = $request->input('user_login');
        
        $select_user_name = StaffDetails::where('staff_mobile_number', $user_login)->orWhere('staff_email',$user_login)->first();    
        
        // $numrows_username = $select_user_name->count();
        if($select_user_name) {
            $val_user = $select_user_name->toArray();
            if($val_user['password']==$request->password){
                if ($val_user['active_status'] != 'unactive') 
                {
                    
                        $vendor_id = $val_user['vendor_id'];
                        $active_plans_row = GetVendorActivatedPlan($vendor_id);
                        $block_vechiles = GetVendorsBlockedVechiles($vendor_id);
        
                        $numrows_fare = 0;
                        $select_vendor = PaUsers::where("id",$vendor_id)->where("user_role",'vandor')->first();
                        
                        if ($select_vendor!=null) {
                            $row_vendor = $select_vendor->toArray();
                            if($row_vendor['parking_logo']!=''){
                                // $row_vendor['parking_logo'] = VENDOR_URL.$row_vendor['parking_logo'];
                                $row_vendor['parking_logo'] = $row_vendor['parking_logo'];
                            } else {
                                $row_vendor['parking_logo'] = '';
                            }
                            $val_user['vendor_details'] = $row_vendor;
                        }

                        if(isset($active_plans_row['fare_info']) && $active_plans_row['fare_info'] > 0){
                            $fare_final_array = array();
                            
                            $select_fare_query = FareInfo::where('user_id', $vendor_id)->orderBy('initial_hr', 'ASC');
                           
                            $numrows_fare = $select_fare_query->count();
                           
                            $fare_rows = $select_fare_query->get()->toArray();
                            foreach ($fare_rows as $fare_row) {
                             
                                $veh_type = $fare_row['veh_type'];
                                $initial_hr = $fare_row['initial_hr'];
                                $ending_hr = $fare_row['ending_hr'];
                                $amount = $fare_row['amount'];
                                $hr_status = $fare_row['hr_status'];
                                $fare_array['initial_hr'] = $initial_hr;
                                $fare_array['ending_hr'] = $ending_hr;
                                $fare_array['amount'] = $amount;
                                $fare_array['hr_status'] = $hr_status;
                                $fare_final_array[$veh_type][] = $fare_array;
                            }
                            $val_user['fare_info_price'] = $fare_final_array;
                        }
                        
                        if(isset($active_plans_row['fare_info'])){
                            $val_user['vendor_active_plan'] = $active_plans_row;
                        }
        
                        if(sizeof($block_vechiles) > 0){
                            $val_user['block_vehicles'] = $block_vechiles;
                        }
        
                        if($numrows_fare > 0){
                            StaffDetails::where('staff_id', $val_user['staff_id'])->update(['login_status' => 1,'last_login' => time()]);
                            $array['error_code'] = 200;
                            
                            $payload = [
                                'email' => $val_user['staff_email'],
                                'id' => $val_user['staff_id'],
                            ];
                           
                            $val_user['token'] = JWTAuth::fromUser($select_user_name, $payload);
                            $array['result'] = $val_user;
                            
                            $array['message'] = 'User Login Successfully';
                        } else {
                            $array['error_code'] = 400;
                            $array['message'] = 'Please add fare in Vendor panel or contact with Vendor / Admin';
                        }
                }else{
                    $array['error_code'] = 400;
                    $array['message'] = 'Your Account in Unactive';
                }		
            } else {
                $array['error_code'] = 400;
                $array['message'] = 'Incorrect Password.';
            }
        } else {
            $array['error_code'] = 400;
            $array['message'] = 'Incorrect Username Password';
        }

        $finalarray['response'] = $array;
        return response()->json($finalarray);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        JWTAuth::invalidate($request->token);

        return response()->json(['message' => 'Successfully logged out']);
    }
}
