<?php
namespace App\Http\Controllers;use App\Services\ConversionTracker;use Illuminate\Http\Request;
class ConversionController extends Controller {public function store(Request $request,ConversionTracker $tracker){$data=$request->validate(['type'=>['required','in:email_click,phone_click,whatsapp_click'],'locale'=>['nullable','in:id,en,zh']]);$tracker->record($request,$data['type']);return response()->noContent();}}
