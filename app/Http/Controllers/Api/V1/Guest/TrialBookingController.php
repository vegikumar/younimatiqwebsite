<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\CampBookingController;
use App\Http\Controllers\Controller;
use App\Services\BookingWindowService;
use Illuminate\Http\Request;

class TrialBookingController extends Controller
{
    use RespondsWithJson;

    public function slots(Request $request, CampBookingController $camp, BookingWindowService $windows)
    {
        return $camp->getAvailableSlots($request, $windows);
    }

    public function bookTrial(Request $request, CampBookingController $camp)
    {
        $request->headers->set('Accept', 'application/json');

        return $camp->storeTrialBooking($request);
    }
}
