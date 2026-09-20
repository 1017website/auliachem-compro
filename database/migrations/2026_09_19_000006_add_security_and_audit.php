<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users',function(Blueprint $t){$t->boolean('is_active')->default(true)->index();$t->text('two_factor_secret')->nullable();$t->timestamp('last_login_at')->nullable();});
  Schema::create('login_histories',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('ip_hash',64);$t->text('user_agent')->nullable();$t->timestamp('logged_in_at')->index();});
  Schema::create('activity_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action');$t->nullableMorphs('subject');$t->string('description');$t->json('metadata')->nullable();$t->timestamp('created_at')->index();});
 }
 public function down(): void {Schema::dropIfExists('activity_logs');Schema::dropIfExists('login_histories');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['is_active','two_factor_secret','last_login_at']));}
};
