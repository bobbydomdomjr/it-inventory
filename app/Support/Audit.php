<?php
namespace App\Support;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
class Audit {public static function record(string $action,string $module,?string $type=null,?int $id=null,array $old=[],array $new=[]):void{$clean=static function(array $data){unset($data['password'],$data['license_key'],$data['_token'],$data['_method']);return $data;};try{AuditLog::create(['user_id'=>Auth::id(),'action'=>$action,'module'=>$module,'record_type'=>$type,'record_id'=>$id,'old_values'=>$clean($old),'new_values'=>$clean($new),'ip_address'=>request()->ip(),'user_agent'=>substr((string)request()->userAgent(),0,500),'created_at'=>now()]);}catch(\Throwable $e){report($e);}}}
