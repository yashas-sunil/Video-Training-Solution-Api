<?php

namespace App\Http\Controllers\v1;

use Illuminate\Http\Request;
use App\Models\user;
use App\Models\UserNotification;
use App\Models\JMoney;
use App\Models\Notification;

class FcmController extends Controller
{
   public function updateFcmTocken(Request $request){
    $id=auth('api')->id();
    info($id.'ddd');
    $data['fcm_token']=$request->input('fcm_token');
    $user = User::where('id',$id)->update($data);
   }
   public function firebasenotification(){
    $id=auth('api')->id();
   // $fcn_token=User::select('fcm_token')->where('id',$id)->first();
   $fcn_token= User::where('id',$id)->pluck('fcm_token')->toArray();
    $notification=Notification::select('notifications.notification_body','notifications.title','notifications.id')
                    ->join('user_notifications','user_notifications.notification_id','=','notifications.id','inner')
                    ->where('user_notifications.user_id',$id)
                    ->where('user_notifications.send_to_firebase',0)
                  //  ->where('user_notifications.is_read',0)
                    ->get();
                    $SERVER_API_KEY = 'AAAAK9S6vRg:APA91bFFKSLXT6zSmCZEoeu7jP-WC6fw6IVgcTZO06LbcOpyvmenwK9zSrYqIyqf5apYLiKjrzQLtxK7SNNvdefdU-tm-UmX7Hz9_y623njkM86KFSqF_VxQCyXSo05mGnARX15i1-c3';
    try{
                    foreach($notification as $row){
                       
                        $body=$row->notification_body;
                        $title=$row->title;

                        $data = [
                            "registration_ids" => $fcn_token,
                            "notification" => [
                                "title" => $title,
                                "body" => $body,
                                "content_available" => true,
                                "priority" => "high",
                            ]
                        ];
                      

                        $dataString = json_encode($data);
               
                        $headers = [
                            'Authorization: key=' . $SERVER_API_KEY,
                            'Content-Type: application/json',
                        ];
                
                        $ch = curl_init();
                
                        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
                
                        $response = curl_exec($ch);
                     

                        $update_fcm = UserNotification::where('notification_id',$row->id)->where('user_id',$id)->update(["send_to_firebase" => 1]);

                    }
        
    }catch (\Exception $exception) {
        info($exception->getMessage());
    }
   

   
   }
}
