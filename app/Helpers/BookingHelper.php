<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingHelper
{
    public static function getAvailableProviders($date,$salonId)
    {
        return DB::table('service_providers')
            ->where('is_active', 1)
            ->where('saloon_id', $salonId)
            ->get();
    }

//old payload
// public static function getServicePermutations($services, $providers, $date, $salonStart, $salonEnd, $maxWait)
// {
//     $perms = [];
    
//     // Pre-check and filter providers based on service category
//     $filteredProviders = [];
    
//     // Get category for each service
//     foreach ($services as $serviceId) {
//         $categoryId = DB::table('services')->where('sID', $serviceId)->value('cID');
        
//         // Filter providers by category for the service
//         $filteredProviders[$serviceId] = DB::table('service_providers')
//             ->whereIn('spID', function ($query) use ($categoryId) {
//                 $query->select('spID')->from('sp_category')->where('cID', $categoryId);
//             })
//             ->get();  // This gives a list of providers for the service based on category
//     }

//     $generate = function ($services, $filteredProviders, $prefix = []) use (&$perms, &$generate) {
//         if (empty($services)) {
//             $perms[] = $prefix;
//             return;
//         }

//         $service = array_shift($services);
//         foreach ($filteredProviders[$service] as $provider) {
//             $newPrefix = $prefix;
//             $newPrefix[$service] = $provider->spID;  // Add provider ID to the service
//             $generate($services, $filteredProviders, $newPrefix);
//         }
//     };

//     $generate($services, $filteredProviders);

//     return $perms;
// }

public static function getServicePermutations($services, $providers, $date, $salonStart, $salonEnd, $maxWait)
{
    $perms = [];

    $filteredProviders = [];

    // Step 1: Build filteredProviders for each service
    foreach ($services as $serviceId => $providerId) {
        if ($providerId === 'any') {
            $categoryId = DB::table('services')->where('sID', $serviceId)->value('cID');

            $filteredProviders[$serviceId] = DB::table('service_providers')
                ->whereIn('spID', function ($query) use ($categoryId) {
                    $query->select('spID')->from('sp_category')->where('cID', $categoryId);
                })
                ->pluck('spID')
                ->map(fn($id) => (int) $id)  // Ensure integer
                ->toArray();
        } else {
            $filteredProviders[$serviceId] = [(int) $providerId];  // Ensure integer
        }
    }

    // Step 2: Generate permutations
    $generate = function ($remainingServices, $filteredProviders, $prefix = []) use (&$perms, &$generate) {
        if (empty($remainingServices)) {
            $perms[] = $prefix;
            return;
        }

        $serviceId = array_shift($remainingServices);

        foreach ($filteredProviders[$serviceId] as $providerId) {
            $newPrefix = $prefix;
            $newPrefix[$serviceId] = (int) $providerId;  // Ensure integer
            $generate($remainingServices, $filteredProviders, $newPrefix);
        }
    };

    $generate(array_keys($services), $filteredProviders);

    return $perms;
}

  // generate all service orders (permutations of service execution)
    public static function getServiceOrders($services)
    {
        $results = [];

        $permute = function ($items, $prefix = []) use (&$results, &$permute) {
            if (empty($items)) {
                $results[] = $prefix;
                return;
            }

            for ($i = 0; $i < count($items); $i++) {
                $newItems = $items;
                $newPrefix = $prefix;
                $newPrefix[] = $newItems[$i];
                array_splice($newItems, $i, 1);
                $permute($newItems, $newPrefix);
            }
        };

        $permute($services);

        return $results;
    }


//old wait not correct
// public static function checkPermutation($assignment, $serviceOrder, $date, $maxWait, $customerSelectedProvider)
// {
//     $slots = [];
//     $providersUsed = [];

//     foreach ($serviceOrder as $serviceId) {
//         $providerId = $assignment[$serviceId];

//         // Get provider's shift start and end
//         $provider = DB::table('service_providers')
//             ->where('spID', $providerId)
//             ->select('shift_start', 'shift_end')
//             ->first();

//         if (!$provider) return ['success' => false]; // Safety check

//         $shiftStart = Carbon::parse($provider->shift_start);
//         $shiftEnd = Carbon::parse($provider->shift_end);

//         $duration = DB::table('services')->where('sID', $serviceId)->value('duration');
//         $duration = intval($duration);

//         // Existing bookings for the provider
//         $existing = DB::table('customer_bookings_services')
//             ->where('spID', $providerId)
//             ->where('date', $date)
//             ->select('start_time', 'end_time')
//             ->get();

//         $found = false;
//         $slotStart = $shiftStart->copy();
//         $slotEnd = $slotStart->copy()->addMinutes($duration);

//         while ($slotEnd->lte($shiftEnd)) {
//             $conflict = false;

//             // Check against existing bookings
//             foreach ($existing as $booked) {
//                 $bookedStart = Carbon::parse($booked->start_time);
//                 $bookedEnd = Carbon::parse($booked->end_time);

//                 if (
//                     $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
//                     $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
//                 ) {
//                     $conflict = true;
//                     break;
//                 }

//             }

//             // Check against already selected slots in the current permutation (for overlap check)
//             if (!$conflict) {
//                 foreach ($slots as $slot) {
//                     // Compare current service time slot with already selected slots
//                     $bookedStart = Carbon::parse($slot['start_time']);
//                     $bookedEnd = Carbon::parse($slot['end_time']);

//                     // Check if the new slot overlaps with the existing slots
//                     if (
//                         $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
//                         $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
//                     ) {
//                         $conflict = true;
//                         break;
//                     }
//                 }
//             }

          


            
//             // If no conflict, save this slot
//             if (!$conflict) {
                
//                 $slots[] = [
//                     'service_id' => $serviceId,
//                     'provider_id' => $providerId,
//                     'start_time' => $slotStart->format('H:i'),
//                     'end_time' => $slotEnd->format('H:i'),
                    
//                 ];

//                 // Move next slot start time based on maxWait
//                 $slotStart = $slotEnd->copy()->addMinutes($maxWait);
//                 $providersUsed[] = $providerId;
//                 $found = true;
//                 break;
//             }

//             // Try the next slot after 5 minutes
//             $slotStart->addMinutes(5);
//             $slotEnd = $slotStart->copy()->addMinutes($duration);
//         }

//         // If no valid slot found for this service, fail the combination
//         if (!$found) {
//             return ['success' => false];
//         }
//     }

//     // Validate the total time span between the first and last service
//     $first = Carbon::parse($slots[0]['start_time']);
//     $last = Carbon::parse($slots[count($slots) - 1]['end_time']);

//     if ($first->diffInMinutes($last) > 4 * 60) {
//         return ['success' => false];
//     }

//     return [
//         'success' => true,
//         'slots' => $slots,
//         'start_time' => $first->format('H:i'),
//         'end_time' => $last->format('H:i'),
//         'providers' => $providersUsed
//     ];
// }

//wait corrected but next day sceduling
// public static function checkPermutation($assignment, $serviceOrder, $date, $maxWait, $customerSelectedProvider)
// {
//     $slots = [];
//     $providersUsed = [];
//     $previousSlotEnd = null;

//     foreach ($serviceOrder as $serviceId) {
//         $providerId = $assignment[$serviceId];

//         // Get provider's shift start and end
//         $provider = DB::table('service_providers')
//             ->where('spID', $providerId)
//             ->select('shift_start', 'shift_end')
//             ->first();

//         if (!$provider) return ['success' => false]; // Safety check

//         $shiftStart = Carbon::parse($provider->shift_start);
//         $shiftEnd = Carbon::parse($provider->shift_end);

//         $duration = DB::table('services')->where('sID', $serviceId)->value('duration');
//         $duration = intval($duration);

//         // Existing bookings for the provider
//         $existing = DB::table('customer_bookings_services')
//             ->where('spID', $providerId)
//             ->where('date', $date)
//             ->select('start_time', 'end_time')
//             ->get();

//         $found = false;
        
//         // Determine the earliest possible start time for this service
//         $earliestStart = $shiftStart->copy();
//         if ($previousSlotEnd !== null) {
//             // The service can't start later than maxWait minutes after the previous slot ended
//             $earliestStart = max($earliestStart, $previousSlotEnd);
//         }

//         $slotStart = $earliestStart->copy();
//         $slotEnd = $slotStart->copy()->addMinutes($duration);

//         while ($slotEnd->lte($shiftEnd)) {
//             $conflict = false;

//             // Check against existing bookings
//             foreach ($existing as $booked) {
//                 $bookedStart = Carbon::parse($booked->start_time);
//                 $bookedEnd = Carbon::parse($booked->end_time);

//                 if (
//                     $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
//                     $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
//                 ) {
//                     $conflict = true;
//                     break;
//                 }
//             }

//             // Check against already selected slots in the current permutation (for overlap check)
//             if (!$conflict) {
//                 foreach ($slots as $slot) {
//                     $bookedStart = Carbon::parse($slot['start_time']);
//                     $bookedEnd = Carbon::parse($slot['end_time']);

//                     if (
//                         $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
//                         $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
//                     ) {
//                         $conflict = true;
//                         break;
//                     }
//                 }
//             }

//             // If no conflict, save this slot
//             if (!$conflict) {
//                 // Check wait time constraint (except for first service)
//                 if ($previousSlotEnd !== null) {
//                     $waitTime = $slotStart->diffInMinutes($previousSlotEnd);
//                     if ($waitTime > $maxWait) {
//                         return ['success' => false];
//                     }
//                 }

//                 $slots[] = [
//                     'service_id' => $serviceId,
//                     'provider_id' => $providerId,
//                     'start_time' => $slotStart->format('H:i'),
//                     'end_time' => $slotEnd->format('H:i'),
//                 ];

//                 $previousSlotEnd = $slotEnd->copy();
//                 $providersUsed[] = $providerId;
//                 $found = true;
//                 break;
//             }

//             // Try the next slot after 5 minutes
//             $slotStart->addMinutes(5);
//             $slotEnd = $slotStart->copy()->addMinutes($duration);
//         }

//         // If no valid slot found for this service, fail the combination
//         if (!$found) {
//             return ['success' => false];
//         }
//     }

//     // Validate the total time span between the first and last service
//     $first = Carbon::parse($slots[0]['start_time']);
//     $last = Carbon::parse($slots[count($slots) - 1]['end_time']);

//     if ($first->diffInMinutes($last) > 4 * 60) {
//         return ['success' => false];
//     }

//     return [
//         'success' => true,
//         'slots' => $slots,
//         'start_time' => $first->format('H:i'),
//         'end_time' => $last->format('H:i'),
//         'providers' => $providersUsed
//     ];
// }

//final with arrays of each service and combinatons seems correct returns first valid found
// public static function checkPermutation($assignment, $serviceOrder, $date, $maxWait, $customerSelectedProvider)
// {
//     $slots = [];
//     $providersUsed = [];
//     $previousSlotEnd = null;

//     // Step 1: Generate available slots for each service
//     $serviceSlots = [];
//     foreach ($serviceOrder as $serviceId) {
//         $providerId = $assignment[$serviceId];
//         $provider = DB::table('service_providers')
//             ->where('spID', $providerId)
//             ->select('shift_start', 'shift_end')
//             ->first();
            
//         if (!$provider) return ['success' => false]; // Safety check

//         $shiftStart = Carbon::parse($provider->shift_start);
//         $shiftEnd = Carbon::parse($provider->shift_end);

//         $duration = DB::table('services')->where('sID', $serviceId)->value('duration');
//         $duration = intval($duration);

//         // Existing bookings for the provider
//         $existing = DB::table('customer_bookings_services')
//             ->where('spID', $providerId)
//             ->where('date', $date)
//             ->select('start_time', 'end_time')
//             ->get();
           
//         $availableSlots = [];
//         $slotStart = $shiftStart->copy();
//         while ($slotStart->lt($shiftEnd)) {
//             $slotEnd = $slotStart->copy()->addMinutes($duration);
//             if ($slotEnd->gt($shiftEnd)) {
//                 break;
//             }
//             $conflict = false;

//             // Check against existing bookings
//             foreach ($existing as $booked) {
//                 $bookedStart = Carbon::parse($booked->start_time);
//                 $bookedEnd = Carbon::parse($booked->end_time);

//                 if (
//                     $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
//                     $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
//                 ) {
//                     $conflict = true;
//                     break;
//                 }
//             }

//             if (!$conflict) {
//                 $availableSlots[] = [
//                     'service_id' => $serviceId,
//                     'provider_id' => $providerId,
//                     'start_time' => $slotStart->format('H:i'),
//                     'end_time' => $slotEnd->format('H:i'),
//                 ];
//             }
            
//             // Move to the next slot
//             $slotStart = $slotStart->copy()->addMinutes(1);
//         }
       
//         $serviceSlots[$serviceId] = $availableSlots;
//     }
    
//     // Step 2: Generate all combinations of slots across services
//     $allCombinations = self::generateCombinations($serviceSlots, $serviceOrder);
   
//     // Step 3: Validate each combination
//     foreach ($allCombinations as $combination) {
//         $valid = true;
//         $previousSlotEnd = null;
//         $providersUsed = [];

//         foreach ($combination as $slot) {
//             // Check wait time constraint
//             if ($previousSlotEnd !== null) {
//                 $waitTime = Carbon::parse($slot['start_time'])->diffInMinutes($previousSlotEnd);
//                 if ($waitTime > $maxWait) {
//                     $valid = false;
//                     break;
//                 }
//             }

//             // Check for overlapping service times
//             foreach ($providersUsed as $usedSlot) {
//                 $existingStart = Carbon::parse($usedSlot['start_time']);
//                 $existingEnd = Carbon::parse($usedSlot['end_time']);
//                 $newStart = Carbon::parse($slot['start_time']);
//                 $newEnd = Carbon::parse($slot['end_time']);

//                 if ($newStart < $existingEnd && $newEnd > $existingStart) {
//                     $valid = false;
//                     break 2; // Break out of both loops
//                 }
//             }

//             $providersUsed[] = $slot;
//             $previousSlotEnd = Carbon::parse($slot['end_time']);
//         }

//         if ($valid) {
//             // Return the valid schedule
//             return [
//                 'success' => true,
//                 'slots' => $combination,
//                 'start_time' => $combination[0]['start_time'],
//                 'end_time' => end($combination)['end_time'],
//                 //'providers' => $providersUsed
//             ];
//         }
//     }

//     // If no valid combination is found
//     return ['success' => false];
// }

//final for multiple but 1 minute time slots 
public static function checkPermutation($assignment, $serviceOrder, $date, $maxWait, $customerSelectedProvider, $salonId)
{
    $validCombinations = [];

    // Step 1: Generate available slots for each service
    $serviceSlots = [];
    foreach ($serviceOrder as $serviceId) {
        $providerId = $assignment[$serviceId];
        $provider = DB::table('service_providers')
            ->where('spID', $providerId)
            ->select('shift_start', 'shift_end')
            ->first();

        if (!$provider) return ['success' => false, 'valid_combinations' => []];

        $shiftStart = Carbon::parse($provider->shift_start);
        $shiftEnd = Carbon::parse($provider->shift_end);

        $duration = DB::table('services')->where('sID', $serviceId)->value('duration');
        $duration = intval($duration);

        // Existing bookings for the provider
        $existing = DB::table('customer_bookings_services')
            ->where('spID', $providerId)
            ->where('booking_date', $date)
            ->where('saloon_id', $salonId) 
            ->select('start_time', 'end_time')
            ->get();

        $availableSlots = [];
        $slotStart = $shiftStart->copy();
        while ($slotStart->lt($shiftEnd)) {
            $slotEnd = $slotStart->copy()->addMinutes($duration);
            if ($slotEnd->gt($shiftEnd)) {
                break;
            }
            $conflict = false;

            // Check against existing bookings
            foreach ($existing as $booked) {
                $bookedStart = Carbon::parse($booked->start_time);
                $bookedEnd = Carbon::parse($booked->end_time);

                // if (
                //     $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
                //     $slotEnd->between($bookedStart->addMinute(), $bookedEnd) ||
                //     $slotEnd->equalTo($bookedEnd) ||
                //     $slotStart->equalTo($bookedStart)
                // ) {
                //     $conflict = true;
                //     break;
                // }
                if (
                    $slotStart < $bookedEnd &&
                    $slotEnd > $bookedStart
                ) {
                    $conflict = true;
                    break;
                }
                



            }

            if (!$conflict) {
                $availableSlots[] = [
                    'service_id' => $serviceId,
                    'provider_id' => $providerId,
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                ];
            }

            // Move to the next slot
            $slotStart = $slotStart->copy()->addMinutes(5);
        }

        $serviceSlots[$serviceId] = $availableSlots;
    }

    // Step 2: Generate all combinations of slots across services
    $allCombinations = self::generateCombinations($serviceSlots, $serviceOrder);

    // Step 3: Validate each combination
    foreach ($allCombinations as $combination) {
        $valid = true;
        $previousSlotEnd = null;
        $providersUsed = [];

        foreach ($combination as $slot) {
            // Check wait time constraint
            if ($previousSlotEnd !== null) {
                $waitTime = Carbon::parse($slot['start_time'])->diffInMinutes($previousSlotEnd);
                if ($waitTime > $maxWait) {
                    $valid = false;
                    break;
                }
            }

            // Check for overlapping service times
            foreach ($providersUsed as $usedSlot) {
                $existingStart = Carbon::parse($usedSlot['start_time']);
                $existingEnd = Carbon::parse($usedSlot['end_time']);
                $newStart = Carbon::parse($slot['start_time']);
                $newEnd = Carbon::parse($slot['end_time']);

                if ($newStart < $existingEnd && $newEnd > $existingStart) {
                    $valid = false;
                    break 2; // Break out of both loops
                }
            }

            $providersUsed[] = $slot;
            $previousSlotEnd = Carbon::parse($slot['end_time']);
        }

        if ($valid) {
            $validCombinations[] = [
                'saloon_id'=>$salonId,
                'start_time' => $combination[0]['start_time'],
                'end_time' => end($combination)['end_time'],
                'slots' => $combination,
            ];
        }
    }

    return [
        'success' => !empty($validCombinations),
        'valid_combinations' => $validCombinations,
    ];
}










private static function generateCombinations($serviceSlots, $serviceOrder, $index = 0, $currentCombination = []) {
    // Base case: If we've processed all services, return the current combination
    if ($index === count($serviceOrder)) {
        return [$currentCombination];
    }

    $combinations = [];
    $currentService = $serviceOrder[$index];

    // Ensure the current service has slots defined
    if (isset($serviceSlots[$currentService]) && !empty($serviceSlots[$currentService])) {
        foreach ($serviceSlots[$currentService] as $slot) {
            // Include the current slot in the combination
            $newCombination = array_merge($currentCombination, [$slot]);
            // Recursively generate combinations for the next service
            $combinations = array_merge($combinations, self::generateCombinations($serviceSlots, $serviceOrder, $index + 1, $newCombination));
        }
    }

    return $combinations;
}














}
