<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Booking1Helper
{
     // Get the available service providers for a specific date
     public static function getAvailableProviders($date)
     {
         return DB::table('service_providers')
             ->where('is_active', 1)
             ->get();
             
     }

    public static function getServicePermutations($services, $date)
    {
        $perms = [];

        // Pre-check and filter providers based on service category
        $filteredProviders = [];
        
        // Get category for each service and filter providers by category
        foreach ($services as $serviceId => $provider) {
          
            // If the provider is "any", we need to consider all available providers for that service
            if ($provider === 'any') {
                // Get category for the service
                $categoryId = DB::table('services')->where('sID', $serviceId)->value('cID');

                // Filter providers by category for the service
                $filteredProviders[$serviceId] = DB::table('service_providers')
                    ->whereIn('spID', function ($query) use ($categoryId) {
                        $query->select('spID')->from('sp_category')->where('cID', $categoryId);
                    })
                    ->get();  // This gives a list of providers for the service based on category
            } else {
                // If a specific provider is selected, use that provider
                $filteredProviders[$serviceId] = DB::table('service_providers')
                    ->where('spID', $provider)
                    ->get();
            }
        }

        // Recursive function to generate permutations
        $generate = function ($services, $filteredProviders, $prefix = []) use (&$perms, &$generate) {
            if (empty($services)) {
                // If no more services to process, add the current permutation
                $perms[] = $prefix;
                return;
            }

            $serviceId = key($services); // Get the service ID (e.g., 1, 2, etc.)
            $provider = $services[$serviceId]; // Get the provider (either specific ID or "any")

            // Loop through each provider for this service and generate permutations
            foreach ($filteredProviders[$serviceId] as $providerObj) {
                $newPrefix = $prefix;
                $newPrefix[$serviceId] = $providerObj->spID; // Add provider ID to the service
                // Recurse to the next service
                $nextServices = $services;
                unset($nextServices[$serviceId]); // Remove the current service to move to the next
                $generate($nextServices, $filteredProviders, $newPrefix);
            }
        };

        // Call the recursive function to generate permutations
        $generate($services, $filteredProviders);
      
        return $perms;
    }

    // Get all possible orders of services (used to check different combinations)
    public static function getServiceOrders($services)
    {
        dd('end10');
        $results = [];
       
        // Only consider the service IDs (not the providers)
        $indexedServices = array_keys($services);
       
        // Permutation function to generate all permutations of service IDs
        $permute = function ($items, $prefix = []) use (&$results, &$permute) {
            if (empty($items)) {
                // When there are no more items to process, store the current permutation
                $results[] = $prefix;
                return;
            }
    
            for ($i = 0; $i < count($items); $i++) {
                // Create a new array excluding the current item
                $newItems = $items;
                $newPrefix = $prefix;
                $newPrefix[] = $newItems[$i]; // Add the current service to the prefix
                array_splice($newItems, $i, 1); // Remove the current service from the list
                // Recurse to continue processing the rest of the services
                $permute($newItems, $newPrefix);
            }
        };
    
        // Call the permutation function to generate all combinations of services
        $permute($indexedServices);
    
        return $results;
    }
    

    
     // Check each permutation for conflicts with existing bookings and validate available time slots
     public static function checkPermutation($assignment, $serviceOrder, $date, $maxWait, $selectedProviders, $customerSelectedProvider)
     {
         $slots = [];
         $providersUsed = [];
 
         foreach ($serviceOrder as $serviceId) {
             $providerId = $assignment[$serviceId];
 
             // Get provider's shift start and end time
             $provider = DB::table('service_providers')
                 ->where('spID', $providerId)
                 ->select('shift_start', 'shift_end')
                 ->first();
 
             if (!$provider) return ['success' => false];
 
             $shiftStart = Carbon::parse($provider->shift_start);
             $shiftEnd = Carbon::parse($provider->shift_end);
 
             // Fetch service duration
             $duration = DB::table('services')->where('sID', $serviceId)->value('duration');
             $duration = intval($duration);
 
             // Check existing bookings for the provider
             $existing = DB::table('customer_bookings_services')
                 ->where('spID', $providerId)
                 ->where('date', $date)
                 ->select('start_time', 'end_time')
                 ->get();
 
             $found = false;
             $slotStart = $shiftStart->copy();
             $slotEnd = $slotStart->copy()->addMinutes($duration);
 
             // Try to find an available time slot for the service
             while ($slotEnd->lte($shiftEnd)) {
                 $conflict = false;
 
                 // Check against existing bookings
                 foreach ($existing as $booked) {
                     $bookedStart = Carbon::parse($booked->start_time);
                     $bookedEnd = Carbon::parse($booked->end_time);
 
                     // Check for time slot conflicts
                     if (
                         $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
                         $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
                     ) {
                         $conflict = true;
                         break;
                     }
                 }
 
                 // Check if the new slot conflicts with previously selected slots
                 if (!$conflict) {
                     foreach ($slots as $slot) {
                         $bookedStart = Carbon::parse($slot['start_time']);
                         $bookedEnd = Carbon::parse($slot['end_time']);
 
                         if (
                             $slotStart->between($bookedStart, $bookedEnd->subMinute()) ||
                             $slotEnd->between($bookedStart->addMinute(), $bookedEnd)
                         ) {
                             $conflict = true;
                             break;
                         }
                     }
                 }
 
                 // If no conflict, save the slot and continue to the next service
                 if (!$conflict) {
                     $slots[] = [
                         'service_id' => $serviceId,
                         'provider_id' => $providerId,
                         'start_time' => $slotStart->format('H:i'),
                         'end_time' => $slotEnd->format('H:i'),
                         'selected' => isset($services[$serviceId]) && $services[$serviceId] == $providerId,
                     ];
 
                     // Move next slot after max wait time
                     $slotStart = $slotEnd->copy()->addMinutes($maxWait);
                     $providersUsed[] = $providerId;
                     $found = true;
                     break;
                 }
 
                 // Try the next slot after 5 minutes
                 $slotStart->addMinutes(5);
                 $slotEnd = $slotStart->copy()->addMinutes($duration);
             }
 
             // If no valid slot found for the service, fail the combination
             if (!$found) {
                 return ['success' => false];
             }
         }
 
         // Check total time span for the appointment
         $first = Carbon::parse($slots[0]['start_time']);
         $last = Carbon::parse($slots[count($slots) - 1]['end_time']);
 
         if ($first->diffInMinutes($last) > 4 * 60) {
             return ['success' => false];
         }
 
         return [
             'success' => true,
             'slots' => $slots,
             'start_time' => $first->format('H:i'),
             'end_time' => $last->format('H:i'),
             'providers' => $providersUsed
         ];
     }
 


}