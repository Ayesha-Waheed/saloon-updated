<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\BookingHelper;

class BookingController extends Controller
{
   
    // returns first valid for one permuation fisrt order 
    //public function getAvailableBookingTime(Request $request)
// {
//     // Extract necessary data from request
//     $services = $request->services;
//     $selectedProviders = $request->providers ?? [];
//     $customerSelectedProvider = $request->customerSelectedProvider ?? [];
//     $date = $request->date;
//     $serviceIds = array_keys($services);
    

    
//     $maxDays = 2;
//     $salonStart = Carbon::createFromTime(9, 0, 0);  //  9 AM
//     $salonEnd   = Carbon::createFromTime(20, 0, 0); //  8 PM
//     $maxWait = 0; // in minutes


//     for ($i = 0; $i <= $maxDays; $i++) {
//         $currentDate = Carbon::parse($date)->addDays($i)->toDateString();

//         // Get available providers for the current date
//         $availableProviders = BookingHelper::getAvailableProviders($currentDate);

//         // Filter providers based on selected providers (if provided)
//         $providersToCheck = $selectedProviders ? 
//             array_intersect($availableProviders->pluck('spID')->toArray(), $selectedProviders) :
//             $availableProviders->pluck('spID')->toArray();
           
//         // Get permutations of providers for the services
//         $servicePermutations = BookingHelper::getServicePermutations($services, $providersToCheck, $currentDate, $salonStart, $salonEnd, $maxWait);
        
//         $serviceOrders = BookingHelper::getServiceOrders($serviceIds);
       
//         // Ensure correct structure: $serviceOrders should be an array of service IDs
//         // Loop through each permutation of providers for services and check against orders
//         foreach ($servicePermutations as $perm) {
//             foreach ($serviceOrders as $order) {
//                 // Make sure the assignment (perm) and serviceOrder are structured properly
//                 $result = BookingHelper::checkPermutation(
//                     $perm, $order, $currentDate, $maxWait, $selectedProviders
//                 );

//                 // If a valid result is found, return it
//                 if ($result['success']) {
//                     return response()->json([
//                         'status' => 'success',
//                         'date' => $currentDate,
//                         'slots' => $result['slots'],
//                         'start_time' => $result['start_time'],
//                         'end_time' => $result['end_time'],
//                         'providers_used' => $result['providers']
//                     ]);
//                 }
//             }
//         }
//     }
    
//     // If no slots were found, retry with services set to 'any' provider
//     foreach ($services as $serviceId => $providerId) {
//         if ($providerId !== 'any') {
//             $services[$serviceId] = 'any';  // Set 'any' for services where specific provider isn't selected
//             $this->getAvailableBookingTime($request);  // Call the function in the loop
//         }
//     }



//     // If no available slots are found
//     return response()->json([
//         'status' => 'error',
//         'message' => 'No available time slot found within given constraints.'
//     ], 404);
// }


//return for all permutations and orders in array
public function getAvailableBookingTime(Request $request)
{
    $services = $request->services;
    $selectedProviders = $request->providers ?? [];
    $customerSelectedProvider = $request->customerSelectedProvider ?? [];
    $date = $request->date;
    $serviceIds = array_keys($services);

    $maxDays = 0;
    $salonStart = Carbon::createFromTime(9, 0, 0);  // 9 AM
    $salonEnd = Carbon::createFromTime(20, 0, 0);   // 8 PM
    $maxWait = 0; // in minutes

    $allValidResults = [];

    for ($i = 0; $i <= $maxDays; $i++) {
        $currentDate = Carbon::parse($date)->addDays($i)->toDateString();

        // Get available providers for the current date
        $availableProviders = BookingHelper::getAvailableProviders($currentDate);

        // Filter providers based on selected providers (if provided)
        $providersToCheck = $selectedProviders ?
            array_intersect($availableProviders->pluck('spID')->toArray(), $selectedProviders) :
            $availableProviders->pluck('spID')->toArray();

        // Get permutations of providers for the services
        $servicePermutations = BookingHelper::getServicePermutations($services, $providersToCheck, $currentDate, $salonStart, $salonEnd, $maxWait);

        $serviceOrders = BookingHelper::getServiceOrders($serviceIds);

        // Loop through each permutation of providers for services and check against orders
        foreach ($servicePermutations as $perm) {
            foreach ($serviceOrders as $order) {
                $result = BookingHelper::checkPermutation(
                    $perm, $order, $currentDate, $maxWait, $selectedProviders
                );

                // If a valid result is found, add it to the array
                if ($result['success']) {
                    // $allValidResults[] = [
                    //     'date' => $currentDate,
                    //     'slots' => $result['slots'],
                    //     'start_time' => $result['start_time'],
                    //     'end_time' => $result['end_time'],
                    //     //'providers_used' => $result['providers']
                    // ];
                    foreach ($result['valid_combinations'] as $combination) {
                        $allValidResults[] = [
                            'date' => $currentDate,
                            'slots' => $combination['slots'],
                            'start_time' => $combination['start_time'],
                            'end_time' => $combination['end_time'],
                        ];
                    }





                }
            }
        }
    }

    //If any valid slots were found, return them
    if (!empty($allValidResults)) {
        return response()->json([
            'status' => 'success',
            'available_slots' => $allValidResults
        ]);
    }
   





    // If no slots were found, retry with services set to 'any' provider
    foreach ($services as $serviceId => $providerId) {
        if ($providerId !== 'any') {
            $services[$serviceId] = 'any';  // Set 'any' for services where specific provider isn't selected
            return $this->getAvailableBookingTime($request);  // Call the function recursively
        }
    }

    // If no available slots are found
    return response()->json([
        'status' => 'error',
        'message' => 'No available time slot found within given constraints.'
    ], 404);
}




}
