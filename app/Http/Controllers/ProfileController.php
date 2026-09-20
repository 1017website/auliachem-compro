<?php
namespace App\Http\Controllers;
use App\Services\AuditLog;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class ProfileController extends Controller {
 public function show(Request $r){return view('cms.profile',['history'=>DB::table('login_histories')->where('user_id',$r->user()->id)->latest('logged_in_at')->limit(10)->get(),'pendingSecret'=>$r->session()->get('two_factor_setup')]);}
 public function password(Request $r){$data=$r->validate(['current_password'=>['required','current_password'],'password'=>['required','string','min:12','confirmed']]);$r->user()->update(['password'=>$data['password']]);DB::table('sessions')->where('user_id',$r->user()->id)->where('id','!=',$r->session()->getId())->delete();AuditLog::record('password.changed','Password akun diubah.',$r->user());return back()->with('status','Password diperbarui dan sesi lain dikeluarkan.');}
 public function begin2fa(Request $r,Totp $totp){$secret=$totp->secret();$r->session()->put('two_factor_setup',$secret);return back()->with('status','Masukkan secret ke aplikasi authenticator, lalu konfirmasi kode.');}
 public function confirm2fa(Request $r,Totp $totp){$data=$r->validate(['code'=>['required','digits:6']]);$secret=$r->session()->get('two_factor_setup');if(!$secret||!$totp->verify($secret,$data['code']))throw ValidationException::withMessages(['code'=>'Kode authenticator tidak valid.']);$r->user()->update(['two_factor_secret'=>$secret]);$r->session()->forget('two_factor_setup');AuditLog::record('2fa.enabled','Autentikasi dua faktor diaktifkan.',$r->user());return back()->with('status','Autentikasi dua faktor aktif.');}
 public function disable2fa(Request $r){$r->validate(['current_password'=>['required','current_password']]);$r->user()->update(['two_factor_secret'=>null]);AuditLog::record('2fa.disabled','Autentikasi dua faktor dinonaktifkan.',$r->user());return back()->with('status','Autentikasi dua faktor dinonaktifkan.');}
 public function activity(){return view('cms.activity',['logs'=>DB::table('activity_logs')->leftJoin('users','users.id','=','activity_logs.user_id')->select('activity_logs.*','users.name')->latest('activity_logs.created_at')->paginate(30)]);}
}
