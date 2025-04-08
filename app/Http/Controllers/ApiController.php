<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    /**
     * Search countries directly from REST Countries API
     */
    public function searchCountries(Request $request)
    {
        $search = $request->input('q');
        
        if (empty($search)) {
            return response()->json([]);
        }
        
        try {
            // Search by name
            $response = Http::timeout(3)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/name/{$search}");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            // If name search fails, try alpha code search (cca2 or cca3)
            if ($response->status() === 404 && strlen($search) <= 3) {
                $codeResponse = Http::timeout(3)
                    ->retry(2, 1000)
                    ->get("https://restcountries.com/v3.1/alpha/{$search}");
                    
                if ($codeResponse->successful()) {
                    // Alpha endpoint returns a single country or array based on query
                    $result = $codeResponse->json();
                    if (isset($result['name'])) {
                        // Single country response, wrap in array
                        return response()->json([$result]);
                    }
                    return response()->json($result);
                }
            }
            
            // If there's a different error, return the error status
            return response()->json(['error' => 'API error: ' . $response->status()], 500);
            
        } catch (\Exception $e) {
            // Return a fallback from our local database if the API fails
            return $this->fallbackToLocalSearch($search);
        }
    }
    
    /**
     * Fallback to local database search if API is unavailable
     */
    private function fallbackToLocalSearch($search)
    {
        // Use the existing search functionality as a fallback
        $countryController = new CountryController();
        $request = Request::create('/search', 'GET', ['q' => $search]);
        return $countryController->search($request);
    }
} 