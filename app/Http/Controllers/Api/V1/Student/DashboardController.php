<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Services\StudentApiService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use RespondsWithJson;

    public function __construct(private StudentApiService $studentApi)
    {
    }

    public function index(Request $request)
    {
        return $this->success($this->studentApi->getDashboardData($request->user()));
    }

    public function trialStatus(Request $request)
    {
        return $this->success($this->studentApi->getTrialStatus($request->user()));
    }
}
