<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DeveloperToolsController extends Controller
{
    private const COMMANDS = [
        'clear-cache' => ['command' => 'optimize:clear', 'arguments' => []],
        'migrate' => ['command' => 'migrate', 'arguments' => ['--force' => true]],
        'production-check' => ['command' => 'cms:production-check', 'arguments' => []],
        'media-audit' => ['command' => 'cms:media-audit', 'arguments' => []],
    ];

    public function index()
    {
        return view('cms.developer');
    }

    public function run(Request $request)
    {
        $validated = $request->validate(['tool' => ['required', 'string', 'in:'.implode(',', array_keys(self::COMMANDS))]]);
        $tool = self::COMMANDS[$validated['tool']];

        try {
            $exitCode = Artisan::call($tool['command'], $tool['arguments']);
            $output = trim(Artisan::output());

            return back()->with($exitCode === 0 ? 'status' : 'command_error', [
                'command' => 'php artisan '.$tool['command'],
                'output' => $output !== '' ? $output : 'Perintah selesai tanpa keluaran.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('command_error', [
                'command' => 'php artisan '.$tool['command'],
                'output' => $exception->getMessage(),
            ]);
        }
    }
}
