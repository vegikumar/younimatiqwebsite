<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\PythonSandbox;
use Illuminate\Http\Request;

class PythonLabController extends Controller
{
    public function show(PythonSandbox $sandbox)
    {
        return \App\Support\InertiaPage::react('Student/PythonLab', [
            'available' => $sandbox->available(),
        ]);
    }

    public function run(Request $request, PythonSandbox $sandbox)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20000',
            'stdin' => 'nullable|string|max:8000',
        ]);

        $result = $sandbox->run($data['code'], (string) ($data['stdin'] ?? ''));
        $status = $result['available'] ? 200 : 503;

        return response()->json($result, $status);
    }
}
