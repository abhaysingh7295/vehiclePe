<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    public function validator_response(request $request,$validator){
        
        if ($validator->fails()) {
            $error_msg = [];
            foreach ($validator->messages()->all() as $key => $value) {
            array_push($error_msg, $value);
             }
        
            if ($error_msg) {
                return $error_msg[0];
             }
            }
    }

     /**
     * function to print error log.
     *
     * 
     * 
     */
    public function eror_log($apirequest,$apiresponse){
       if(env("API_DEBUG")==true){
       $path =$_SERVER['DOCUMENT_ROOT']."/trustmoney/admin/storage/Api-log/";
       //code to write file
       $filename = $path.date("Y-m-d").".txt";
        
       $file = fopen( $filename, "a+" );
       
       if( $file == false ) {
          echo ( "Error in opening new file" );
          exit();
       }
       $msg ="-------".date("Y-m-d H:i:s")."------------"."\n";
       $msg.="Request=".json_encode($apirequest)."\n";
       $msg.="Response=".json_encode($apiresponse)."\n";
       $msg.="------------------------------------------"; 
       fwrite( $file,$msg );
       fclose( $file );
    }
    }

    //plan refer
    public function genrate_randomstring($data){
        $Length = 10;
        $RandomString = substr(str_shuffle(md5(time())), 0, $Length).$data;
        $code="P".$RandomString;
        return $code;

    } 

    //sip refer
    public function genrate_randomreferstring($data){
        $Length = 10;
        $RandomString = substr(str_shuffle(md5(time())), 0, $Length).$data;
        $code="S".$RandomString;
        return $code;

    } 


} 
?>
