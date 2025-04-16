<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\BookingHelper;
use App\Models\CustomerBooking;
use App\Models\CustomerBookingService;
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
    ini_set('max_execution_time', 1800); 
    $services = $request->services;
    $selectedProviders = $request->providers ?? [];
    $customerSelectedProvider = $request->customerSelectedProvider ?? [];
    $date = $request->date;
    $serviceIds = array_keys($services);
    $salonId = $request->saloon_id;

    $is_services_order_swap= 1;
    $maxDays = 0;
    $salonStart = Carbon::createFromTime(9, 0, 0);  // 9 AM
    $salonEnd = Carbon::createFromTime(20, 0, 0);   // 8 PM
    $maxWait = 0; // in minutes

    $allValidResults = [];
    $timestamps = [];
    for ($i = 0; $i <= $maxDays; $i++) {
        $currentDate = Carbon::parse($date)->addDays($i)->toDateString();
        

        // Get available providers for the current date
        $availableProviders = BookingHelper::getAvailableProviders($currentDate,$salonId);
        
        // Filter providers based on selected providers (if provided)
        $providersToCheck = $selectedProviders ?
            array_intersect($availableProviders->pluck('spID')->toArray(), $selectedProviders) :
            $availableProviders->pluck('spID')->toArray();
           
         

            

            
        // Get permutations of providers for the services
        $servicePermutations = BookingHelper::getServicePermutations($services, $providersToCheck, $currentDate, $salonStart, $salonEnd, $maxWait);
        
  if($is_services_order_swap){
    $serviceOrders = BookingHelper::getServiceOrders($serviceIds);

  }
   else{
    $serviceOrders=[$serviceIds];
   }    
   

        $timestamps[] = ['label' => 'getServiceOrders end ', 'time' => Carbon::now()->toDateTimeString()];
  
        // Loop through each permutation of providers for services and check against orders
        $exitLoops = false;
        foreach ($servicePermutations as $perm) {
            if ($exitLoops) break;

            foreach ($serviceOrders as $order) {
                $timestamps[] = ['label' => 'servicePermutations inside function start ', 'time' => Carbon::now()->toDateTimeString()];
    
                $result = BookingHelper::checkPermutation(
                    $perm, $order, $currentDate, $maxWait, $selectedProviders,$salonId
                );
                $timestamps[] = ['label' => 'servicePermutations inside function end ', 'time' => Carbon::now()->toDateTimeString()];
    
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
                            'saloon_id'=>$salonId,
                            'start_time' => $combination['start_time'],
                            'end_time' => $combination['end_time'],
                            'slots' => $combination['slots'],
                            
                            
                        ];
                    }

                    



                }
                if(count($allValidResults)>4){
                    $exitLoops = true;
                    break 2; // breaks both foreach loops
                }
            }
        }
        
  
    }

    // if (!empty($allValidResults)) {
    //     // Sort all slots by start_time
    //     usort($allValidResults, function ($a, $b) {
    //         return strcmp($a['start_time'], $b['start_time']);
    //     });
    
    //     $filteredSlots = [];
    //     $lastStartTime = null;
    
    //     foreach ($allValidResults as $slot) {
    //         $currentStart = Carbon::createFromFormat('H:i', $slot['start_time']);
    
    //         if (is_null($lastStartTime) || $lastStartTime->diffInMinutes($currentStart) >= 30) {
    //             $filteredSlots[] = $slot;
    //             $lastStartTime = $currentStart;
    //         }
    //     }
    
        
    // }

    $filteredSlots = [];
      // Get salon start time from DB
        $salonShift = DB::table('saloons')
            ->where('saloon_id', $salonId)
            ->select('saloon_start')
            ->first();
        $intervalStart = Carbon::parse($salonShift->saloon_start)->setDateFrom(Carbon::parse($date));
        $intervalEnd = $intervalStart->copy()->addMinutes(15);

        while ($intervalStart < $salonEnd) {
            $matchedInInterval = false;

            foreach ($allValidResults as $index => $slot) {
                $slotStart = Carbon::createFromFormat('H:i', $slot['start_time']);

                if ($slotStart->between($intervalStart, $intervalEnd->copy()->subMinute())) {
                    $filteredSlots[] = $slot;
                    unset($allValidResults[$index]);
                    $allValidResults = array_values($allValidResults); // reindex
                    $matchedInInterval = true;
                    break; // pick only one slot per interval
                }
            }

            // Move to next 30-minute interval
            $intervalStart->addMinutes(15);
            $intervalEnd = $intervalStart->copy()->addMinutes(15);
        }



    //If any valid slots were found, return them
    if (!empty($allValidResults)) {
        return response()->json([
            'status' => 'success',
            'available_slots' => $filteredSlots
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

public function createBooking(Request $request)
{
    $validated = $request->validate([
        'csID' => 'required|integer', // Customer ID
        'date' => 'required|date',
        'saloon_id' => 'required|integer',
        'start_time' => 'required',
        'end_time' => 'required',
        'slots' => 'required|array',
        'slots.*.service_id' => 'required|integer',
        'slots.*.provider_id' => 'required|integer',
        'slots.*.start_time' => 'required',
        'slots.*.end_time' => 'required',
    ]);

    DB::beginTransaction();

    try {

        // Check if booking already exists
        $exists = CustomerBooking::where('csID', $validated['csID'])
            ->where('booking_date', $validated['date'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->where('saloon_id', $validated['saloon_id'])
            ->exists();

        if ($exists) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Booking already exists for the selected time slot, for the given date and customer.',
            ], 409); // 409 Conflict
        }

        // Step 1: Create booking
        $booking = CustomerBooking::create([
            'csID' => $validated['csID'],
            'booking_date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'saloon_id' => $validated['saloon_id'],
        ]);

        // Step 2: Create each slot record
        foreach ($validated['slots'] as $slot) {
            CustomerBookingService::create([
                'booking_id' => $booking->booking_id,
                'spID' => $slot['provider_id'],
                'sID' => $slot['service_id'],
                'booking_date' => $validated['date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'saloon_id' => $validated['saloon_id'],
            ]);
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Booking created successfully.',
            'booking_id' => $booking->booking_id,
        ]);
    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'status' => 'error',
            'message' => 'Booking failed: ' . $e->getMessage(),
        ], 500);
    }
}




}
