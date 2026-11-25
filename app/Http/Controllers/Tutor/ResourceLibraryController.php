<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;

class ResourceLibraryController extends Controller
{
    public function index()
    {
        return \App\Support\InertiaPage::react('Tutor/Resources');
    }
}
