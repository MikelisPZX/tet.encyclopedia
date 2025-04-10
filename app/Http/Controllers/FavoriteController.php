<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FavoriteController extends Controller
{
    /**
     * Get all favorites
     */
    public function index(Request $request)
    {
        $favorites = Favorite::all();
        
        return response()->json($favorites);
    }

    /**
     * Toggle favorite status for a country.
     */
    public function toggle(Request $request, $countryCode)
    {
        // Check if already a favorite
        $favorite = Favorite::where('country_code', $countryCode)->first();
        $isFavorite = false;
        
        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            $isFavorite = false;
            $country = [
                'country_code' => $countryCode,
                'is_favorite' => false
            ];
        } else {
            try {
                // Use the alpha-3 code endpoint to get country data
                $apiEndpoint = "https://restcountries.com/v3.1/alpha/{$countryCode}";
                
                $response = Http::timeout(15)
                    ->retry(3, 1000)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_TCP_KEEPALIVE => 1,
                            CURLOPT_BUFFERSIZE => 1024 * 1024, // 1MB buffer
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                        ]
                    ])
                    ->get($apiEndpoint);
                
                if ($response->successful()) {
                    $apiData = $response->json();
                    // Handle both array and direct object responses
                    $apiData = is_array($apiData) && !isset($apiData['name']) ? $apiData[0] : $apiData;
                    
                    // Create a new favorite
                    $favorite = Favorite::create([
                        'country_code' => strval($apiData['cca3']),
                        'country_name' => strval($apiData['name']['common']),
                        'flag_emoji' => $apiData['flag'] ?? null
                    ]);
                    
                    $isFavorite = true;
                    $country = [
                        'country_code' => $favorite->country_code,
                        'country_name' => $favorite->country_name,
                        'flag_emoji' => $favorite->flag_emoji,
                        'is_favorite' => true
                    ];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Country not found with code: ' . $countryCode
                    ], 404);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error connecting to API: ' . $e->getMessage()
                ], 500);
            }
        }
        
        // Always ensure we return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson() || $request->header('Accept') == 'application/json') {
            return response()->json([
                'success' => true,
                'favorited' => $isFavorite,
                'country' => $country
            ]);
        }
        
        // If it's a regular form submission (not AJAX), redirect back
        return back()->with('status', $isFavorite ? 'Country added to favorites' : 'Country removed from favorites');
    }
}
