<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::table('content_fields',function(Blueprint $t){$t->json('alt_values')->nullable();$t->string('mobile_value')->nullable();$t->string('image_position',20)->default('center');});}public function down():void{Schema::table('content_fields',fn(Blueprint $t)=>$t->dropColumn(['alt_values','mobile_value','image_position']));}};
