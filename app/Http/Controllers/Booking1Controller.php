<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\BookingHelper;
use Carbon\Carbon;

class Booking1Controller extends Controller
{
    public function getAvailableBookingTime(Request $request)
    {
        // Extract data from the request
        $services = $request->services;  // This is an associative array
        $selectedProviders = $request->providers ?? [];
        $customerSelectedProvider = $request->customerSelectedProvider ?? [];
        $date = $request->date;  // The selected booking date
       
        $maxDays = 2;
        $salonStart = Carbon::createFromTime(9, 0, 0);  // Salon opens at 9 AM
        $salonEnd = Carbon::createFromTime(20, 0, 0);   // Salon closes at 8 PM
        $maxWait = 60;  // Max wait time in minutes between appointments

        // Loop through up to 2 days for availability
        for ($i = 0; $i <= $maxDays; $i++) {
            $currentDate = Carbon::parse($date)->addDays($i)->toDateString();

            // Get available providers for the current date
            $availableProviders = BookingHelper::getAvailableProviders($currentDate);
            
            // Generate service permutations
            $servicePermutations = BookingHelper::getServicePermutations(
                $services, $availableProviders, $currentDate, $salonStart, $salonEnd, $maxWait, 
                $selectedProviders, $customerSelectedProvider
            );
            dd($servicePermutations);
            // Get the order of services to handle
            $serviceOrders = BookingHelper::getServiceOrders($services);
          
            // Try matching each permutation of service and provider against booking slots
            foreach ($servicePermutations as $perm) {
                foreach ($serviceOrders as $order) {
                    $result = BookingHelper::checkPermutation(
                        $perm, $order, $currentDate, $maxWait, $selectedProviders, $customerSelectedProvider
                    );

                    // If a valid result is found, return the available slots
                    if ($result['success']) {
                        return response()->json([
                            'status' => 'success',
                            'date' => $currentDate,
                            'slots' => $result['slots'],
                            'start_time' => $result['start_time'],
                            'end_time' => $result['end_time'],
                            'providers_used' => $result['providers']
                        ]);
                    }
                }
            }
        }

        // If no slots were found, retry with services set to 'any' provider
        foreach ($services as $serviceId => $providerId) {
            if ($providerId !== 'any') {
                $services[$serviceId] = 'any';  // Set 'any' for services where specific provider isn't selected
                $this->getAvailableBookingTime($request);  // Call the function in the loop
            }
        }

         // If no available slots are found after retries, return a failure response
    return response()->json([
        'status' => 'failure',
        'message' => 'No available booking slots found after retries'
    ]);
    }
}

