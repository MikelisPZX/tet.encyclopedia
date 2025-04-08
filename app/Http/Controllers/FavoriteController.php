<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FavoriteController extends Controller
{
    /**
     * Get all favorites
     */
    public function index()
    {
        $favorites = Favorite::all();
        return response()->json($favorites);
    }

    /**
     * Toggle favorite status for a country.
     */
    public function toggle(Request $request, $countryId)
    {
        // First check if country exists in our database
        $country = Country::find($countryId);
        
        // If country doesn't exist, verify it with the API and create a minimal record
        if (!$country) {
            try {
                // Try both the numeric code and alpha-3 code endpoints
                $apiEndpoint = is_numeric($countryId) 
                    ? "https://restcountries.com/v3.1/alpha/{$countryId}" 
                    : "https://restcountries.com/v3.1/alpha/{$countryId}";
                
                $response = Http::timeout(5)
                    ->retry(3, 1000)
                    ->get($apiEndpoint);
                
                // If the first attempt fails and it was numeric, try by alpha code
                if (!$response->successful() && is_numeric($countryId)) {
                    // Try searching by all codes as a fallback
                    $response = Http::timeout(5)
                        ->retry(3, 1000)
                        ->get("https://restcountries.com/v3.1/all");
                    
                    if ($response->successful()) {
                        $allCountries = $response->json();
                        $filteredCountry = null;
                        
                        // Find country by numeric code in the full dataset
                        foreach ($allCountries as $apiCountry) {
                            if (isset($apiCountry['ccn3']) && $apiCountry['ccn3'] == $countryId) {
                                $filteredCountry = $apiCountry;
                                break;
                            }
                        }
                        
                        if ($filteredCountry) {
                            // Check if the country exists by cca3 code first
                            $existingCountry = Country::where('cca3', $filteredCountry['cca3'])->first();
                            
                            if ($existingCountry) {
                                $country = $existingCountry;
                            } else {
                                // Create minimal country record for favoriting
                                $country = Country::create([
                                    'id' => $countryId,
                                    'cca3' => strval($filteredCountry['cca3']),
                                    'cca2' => strval($filteredCountry['cca2']),
                                    'name_common' => strval($filteredCountry['name']['common']),
                                    'name_official' => strval($filteredCountry['name']['official']),
                                    'flag_emoji' => $filteredCountry['flag'] ?? null
                                ]);
                            }
                        } else {
                            return response()->json([
                                'success' => false,
                                'message' => 'Country not found in API'
                            ], 404);
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Country not found in API'
                        ], 404);
                    }
                } elseif ($response->successful()) {
                    $apiData = $response->json();
                    // Handle both array and direct object responses
                    $apiData = is_array($apiData) && !isset($apiData['name']) ? $apiData[0] : $apiData;
                    
                    // Check if the country exists by cca3 code first
                    $existingCountry = Country::where('cca3', $apiData['cca3'])->first();
                    
                    if ($existingCountry) {
                        $country = $existingCountry;
                    } else {
                        // Create minimal country record for favoriting
                        $country = Country::create([
                            'id' => $countryId,
                            'cca3' => strval($apiData['cca3']),
                            'cca2' => strval($apiData['cca2']),
                            'name_common' => strval($apiData['name']['common']),
                            'name_official' => strval($apiData['name']['official']),
                            'flag_emoji' => $apiData['flag'] ?? null
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Country not found in API'
                    ], 404);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error connecting to API: ' . $e->getMessage()
                ], 500);
            }
        }
        
        // Check if already a favorite
        $favorite = Favorite::where('country_id', $country->id)->first();
        
        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            $isFavorite = false;
        } else {
            // Add to favorites
            Favorite::create([
                'country_id' => $country->id
            ]);
            $isFavorite = true;
        }
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite
            ]);
        }
        
        return redirect()->back()->with('success', 
            $isFavorite ? 'Country added to favorites.' : 'Country removed from favorites.');
    }
}
