<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>Sensitive Vechiles Notification</title>
            <style type="text/css">
                body {margin: 0; padding: 0; min-width: 100%!important;}
                .content {width: 100%; max-width: 600px;}  
            </style>
        </head>
        <body yahoo bgcolor="#f6f8f1">
            <table width="100%" bgcolor="#f6f8f1" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <table width="80%" class="content" align="center" cellpadding="0" cellspacing="0" border="0">
                            <tbody>
                                <tr style="border-collapse:collapse"> 
                                    <td style="border-collapse:collapse;border:1px solid lightgrey;padding:3px;text-align: center;"><img src="http://thedigitalparking.com/digital-parking/administration/upload/logo.png"><br></td> 
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#fff" style="word-break:normal;color:rgb(0,0,0);font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;font-size:14px;font-style:normal;font-variant-ligatures:normal;font-variant-caps:normal;font-weight:400;letter-spacing:normal;text-align:start;text-indent:0px;text-transform:none;white-space:normal;word-spacing:0px;text-decoration-style:initial;text-decoration-color:initial;box-sizing:border-box;border-radius:3px;background-color:rgb(255,255,255);margin:0px;border:1px solid rgb(233,233,233)">
                                                            <tbody>
                                                                <tr style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;margin:0px">
                                                                    <td valign="top" style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;vertical-align:top;margin:0px;padding:20px">
<?php if ($vehicle_status == 'Out') { ?>
                                                                            <strong>Sensitive Vehicle Out Form <?php echo $parking_name; ?>!</strong>
<?php } else { ?>
                                                                            <strong>Sensitive Vehicle Found in <?php echo $parking_name; ?>!</strong>
                                                                        <?php } ?>

                                                                        <br><br><br>
                                                                                    <strong>Vehicle Details :- </strong>
                                                                                    <br>Vehicle Number :-&nbsp; <?php echo $vehicle_number; ?>
                                                                                        <br>Mobile No :- <?php echo $mobile_number; ?>
                                                                                            <br>In Time :- <?php echo date('d/m/Y h:i A', $vehicle_in_date_time); ?>
<?php if ($vehicle_status == 'Out') { ?> 
                                                                                                    <br>Out Time :- <?php echo date('d/m/Y h:i A', $vehicle_out_date_time); ?>
<?php } ?>
                                                                                                    <br>Status :- <?php echo $vehicle_status; ?>		
                                                                                                    <br><br><br>

                                                                                                    <strong>Vendor Details :- </strong>
                                                                                                    <br>Vendor Name :-&nbsp; <?php echo $vendor_name; ?>
                                                                                                    <br>Vendor Mobile :-&nbsp; <?php echo $vendor_mobile; ?>
                                                                                                    <br>Vendor Email :-&nbsp; <?php echo $vendor_email; ?>
                                                                                                    <br>Vendor Address :-&nbsp; <?php echo $vendor_address; ?>

                                                                                                    <table width="100%" cellpadding="0" cellspacing="0" style="word-break:normal;font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;margin:0px">
                                                                                                    <tbody>
                                                                                                    <tr style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;margin:0px">
                                                                                                    <td valign="top" style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;box-sizing:border-box;font-size:14px;vertical-align:top;margin:0px;padding:0px 0px 20px">
                                                                                                    <div><br></div>
                                                                                                    <div>Thanks,<br></div>
                                                                                                    <div>Team VehiclePe - The Digital Parking<br></div>
                                                                                                    </td>
                                                                                                    </tr>
                                                                                                    </tbody>
                                                                                                    </table>
                                                                                                    </td>
                                                                                                    </tr>
                                                                                                    </tbody>
                                                                                                    </table>
                                                                                                    </td>
                                                                                                    </tr>
                                                                                                    </tbody>
                                                                                                    </table>
                                                                                                    </td>
                                                                                                    </tr>
                                                                                                    </table>
                                                                                                    </body>
                                                                                                    </html>