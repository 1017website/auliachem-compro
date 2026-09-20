<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('conversion_events',function(Blueprint $t){$t->id();$t->string('type',40)->index();$t->string('locale',5)->default('id');$t->string('visitor_hash',64)->nullable()->index();$t->json('metadata')->nullable();$t->timestamp('occurred_at')->index();});}public function down():void{Schema::dropIfExists('conversion_events');}};
