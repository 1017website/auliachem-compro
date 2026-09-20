<?php

namespace App\Http\Controllers;

use App\Models\ContentField;
use App\Services\CompanyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\Totp;
use App\Services\ImageProcessor;
use App\Services\AuditLog;

class CmsController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt([...$credentials, 'is_admin' => true, 'is_active' => true])) {
            throw ValidationException::withMessages(['email' => 'Email atau password admin tidak sesuai.']);
        }
        $user = Auth::user();
        if ($user->two_factor_secret) {
            $request->session()->put('two_factor_user', $user->id);
            Auth::logout();
            return redirect()->route('two-factor.challenge');
        }
        $this->recordLogin($request, $user);

        return redirect()->intended(route('cms.edit'));
    }

    public function challenge() { abort_unless(session('two_factor_user'), 403); return view('cms.two-factor'); }

    public function verifyTwoFactor(Request $request, Totp $totp)
    {
        $request->validate(['code' => ['required','digits:6']]);
        $user = User::whereKey($request->session()->get('two_factor_user'))->where('is_active', true)->firstOrFail();
        if (! $user->two_factor_secret || ! $totp->verify($user->two_factor_secret, $request->input('code'))) throw ValidationException::withMessages(['code'=>'Kode authenticator tidak valid.']);
        $request->session()->forget('two_factor_user'); Auth::login($user); $this->recordLogin($request, $user);
        return redirect()->intended(route('cms.edit'));
    }

    private function recordLogin(Request $request, User $user): void
    {
        $request->session()->regenerate(); $user->forceFill(['last_login_at'=>now()])->save();
        DB::table('login_histories')->insert(['user_id'=>$user->id,'ip_hash'=>hash('sha256',($request->ip() ?? '').config('app.key')),'user_agent'=>mb_substr((string)$request->userAgent(),0,1000),'logged_in_at'=>now()]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function edit(Request $request)
    {
        $sections = collect(CompanyPage::SECTIONS)->put('media', 'Semua gambar');
        $section = $request->query('section', 'home');
        abort_unless($sections->has($section), 404);

        return view('cms.edit', [
            'sections' => $sections, 'section' => $section,
            'fields' => ($section === 'media' ? ContentField::where('type', 'image') : ContentField::where('section', $section))->orderByRaw("CASE WHEN type = 'image' THEN 0 ELSE 1 END")->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, ImageProcessor $images)
    {
        $request->validate(['section' => 'required|string', 'fields' => 'required|array', 'version' => 'required|array']);
        $fields = ($request->input('section') === 'media' ? ContentField::where('type', 'image') : ContentField::where('section', $request->input('section')))->get();
        abort_if($fields->isEmpty(), 404);
        $rules = [];
        foreach ($fields as $field) {
            $languages = $field->type === 'text' ? ['id', 'en', 'zh'] : ['id'];
            foreach ($languages as $language) {
                $rules["fields.{$field->id}.{$language}"] = ['present', 'nullable', 'string', 'max:10000'];
                if ($field->type !== 'text') {
                    $rules["fields.{$field->id}.{$language}"][] = 'required_without:uploads.'.$field->id;
                    $rules["fields.{$field->id}.{$language}"][] = function ($attribute, $value, $fail) use ($field) {
                        if ($value === ($field->values['id'] ?? '')) {
                            return;
                        }
                        $safe = preg_match('~^https?://[^\s\x00-\x20<>"\x27()\\\\]+$~u', $value)
                            || preg_match('~^/(?!/)[a-zA-Z0-9/_.%-]+$~', $value);
                        if ($field->type === 'link') {
                            $safe = $safe || preg_match('~^#[a-zA-Z][a-zA-Z0-9_-]*$~', $value)
                                || preg_match('~^(mailto:[^\s<>"\x27]+@[^\s<>"\x27]+|tel:\+?[0-9 -]+)$~', $value);
                        }
                        if (! $safe) {
                            $fail('Gunakan URL http/https, path lokal, atau tautan kontak yang valid.');
                        }
                    };
                }
            }
            if ($field->type === 'image') {
                $rules["uploads.{$field->id}"] = $field->key === 'brand_favicon'
                    ? ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon', 'max:2048']
                    : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
                $rules["mobile_uploads.{$field->id}"] = ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'];
                $rules["alt.{$field->id}.*"] = ['nullable','string','max:180'];
                $rules["image_position.{$field->id}"] = ['nullable','in:center,top,bottom,left,right'];
            }
        }
        $request->validate($rules);
        DB::transaction(function () use ($request, $fields, $images) {
            foreach ($fields as $field) {
                $locked = ContentField::whereKey($field->id)->lockForUpdate()->firstOrFail();
                if (($request->input("version.{$field->id}")) !== hash('sha256', json_encode($locked->values))) {
                    throw ValidationException::withMessages(['fields' => 'Konten telah diubah di sesi lain. Muat ulang halaman sebelum menyimpan.']);
                }
            }
            foreach ($fields as $field) {
                $values = $request->input("fields.{$field->id}");
                if ($field->type !== 'text') {
                    $value = $values['id'];
                    if ($request->hasFile("uploads.{$field->id}")) {
                        $path = $field->key === 'brand_favicon' ? $request->file("uploads.{$field->id}")->store('cms', 'public') : $images->store($request->file("uploads.{$field->id}"));
                        $value = '/storage/'.$path;
                    }
                    $values = ['id' => $value, 'en' => $value, 'zh' => $value];
                } else {
                    $values = array_intersect_key($values, array_flip(['id', 'en', 'zh']));
                }
                $extras = [];
                if ($field->type === 'image') {
                    $extras['alt_values'] = array_merge(['id'=>'','en'=>'','zh'=>''], $request->input("alt.{$field->id}", []));
                    $extras['image_position'] = $request->input("image_position.{$field->id}", 'center');
                    if ($request->hasFile("mobile_uploads.{$field->id}")) $extras['mobile_value'] = '/storage/'.$images->store($request->file("mobile_uploads.{$field->id}"),'cms/mobile',900);
                }
                $field->update(['values' => $values, ...$extras]);
            }
        });
        AuditLog::record('content.updated', 'Konten bagian '.$request->input('section').' diperbarui.');

        return redirect()->route('cms.edit', ['section' => $request->input('section')])->with('status', 'Perubahan tersimpan dan sudah tampil di website.');
    }
}
