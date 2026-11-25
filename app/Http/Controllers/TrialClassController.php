<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrialClassController extends Controller
{
    public function show(Request $request)
    {
        return \App\Support\InertiaPage::react('Public/TrialClass');
    }
}
