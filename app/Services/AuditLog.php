<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class AuditLog { public static function record(string $action,string $description,?Model $subject=null,array $metadata=[]): void { DB::table('activity_logs')->insert(['user_id'=>auth()->id(),'action'=>$action,'subject_type'=>$subject?->getMorphClass(),'subject_id'=>$subject?->getKey(),'description'=>$description,'metadata'=>$metadata?json_encode($metadata):null,'created_at'=>now()]); } }
