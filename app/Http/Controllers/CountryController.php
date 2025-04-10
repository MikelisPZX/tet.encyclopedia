<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Define a class to handle API country data with isFavorite method
class ApiCountry {
    public $id;
    public $cca2;
    public $cca3;
    public $name_common;
    public $name_official;
    public $population;
    public $population_rank;
    public $flag_url;
    public $flag_emoji;
    public $area;
    public $languages;
    public $borders;
    public $is_favorite;
    
    public function __construct($apiData, $isFavorite = false) {
        $this->id = $apiData['ccn3'] ?? ($apiData['cca3'] ?? null);
        $this->cca2 = $apiData['cca2'] ?? null;
        $this->cca3 = $apiData['cca3'];
        $this->name_common = $apiData['name']['common'];
        $this->name_official = $apiData['name']['official'];
        $this->population = $apiData['population'] ?? 0;
        $this->population_rank = null;
        $this->flag_url = isset($apiData['flags']) ? ($apiData['flags']['png'] ?? null) : null;
        $this->flag_emoji = $apiData['flag'] ?? null;
        $this->area = $apiData['area'] ?? 0;
        $this->languages = isset($apiData['languages']) ? (array)$apiData['languages'] : [];
        $this->borders = $apiData['borders'] ?? [];
        $this->is_favorite = $isFavorite;
    }
}

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Fetch countries from API
            $response = Http::timeout(15)
                ->retry(3, 1000)
                ->get("https://restcountries.com/v3.1/all?fields=name,cca3,cca2,flags,flag");
                
            if (!$response->successful()) {
                return response()->json(['error' => 'Could not fetch countries'], 500);
            }
            
            $apiCountries = $response->json();
            
            // Get all favorites to check against
            $favorites = Favorite::all()->pluck('country_code')->toArray();
            
            // Transform API data
            $countries = collect($apiCountries)->map(function($countryData) use ($favorites) {
                $isCountryFavorite = in_array($countryData['cca3'], $favorites);
                
                return [
                    'cca3' => $countryData['cca3'],
                    'cca2' => $countryData['cca2'] ?? null,
                    'name_common' => $countryData['name']['common'],
                    'name_official' => $countryData['name']['official'] ?? null,
                    'flag_url' => isset($countryData['flags']) ? ($countryData['flags']['png'] ?? null) : null,
                    'flag_emoji' => $countryData['flag'] ?? null,
                    'is_favorite' => $isCountryFavorite
                ];
            });
            
            // Get favorite countries
            $favoriteCountries = $countries->filter(function($country) use ($favorites) {
                return in_array($country['cca3'], $favorites);
            })->values();
            
            // Only return JSON as we're using a SPA
            return response()->json([
                'countries' => $countries,
                'favorites' => $favoriteCountries,
                'search' => $request->input('search')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching countries: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $code, Request $request)
    {
        try {
            // Fetch country data from REST Countries API
            $response = Http::timeout(15)
                ->retry(3, 1000)
                ->withOptions([
                    'curl' => [
                        CURLOPT_TCP_KEEPALIVE => 1,
                        CURLOPT_BUFFERSIZE => 1024 * 1024, // 1MB buffer
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                    ]
                ])
                ->get("https://restcountries.com/v3.1/alpha/{$code}?fields=name,ccn3,cca2,cca3,flags,flag,population,area,languages,borders");
            
            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::warning("Country API call failed for code '{$code}'. Status: {$response->status()}");
                return response()->json(['error' => 'Country not found'], 404);
            }
            
            // Handle the API response which might be an array or a direct object
            $responseData = $response->json();
            $apiData = is_array($responseData) && isset($responseData[0]) ? $responseData[0] : $responseData;
            
            // Check if this country is in favorites
            $isFavorite = Favorite::where('country_code', $code)->exists();
            
            // Format API data for the view
            $countryData = new ApiCountry($apiData, $isFavorite);
            
            // Calculate population rank by comparing with all countries
            // Fetch all countries to determine population ranking
            try {
                $allCountriesResponse = Http::timeout(5)
                    ->retry(2, 1000)
                    ->get("https://restcountries.com/v3.1/all?fields=name,population");
                
                if ($allCountriesResponse->successful()) {
                    $allCountries = $allCountriesResponse->json();
                    
                    // Sort countries by population in descending order
                    usort($allCountries, function($a, $b) {
                        return ($b['population'] ?? 0) <=> ($a['population'] ?? 0);
                    });
                    
                    // Find the current country's position
                    $targetPopulation = $countryData->population;
                    $rank = 1;
                    
                    foreach ($allCountries as $index => $c) {
                        if (($c['population'] ?? 0) <= $targetPopulation) {
                            $rank = $index + 1;
                            break;
                        }
                    }
                    
                    // Set the population rank
                    $countryData->population_rank = $rank;
                }
            } catch (\Exception $e) {
                // If ranking fails, set a default
                $countryData->population_rank = "Unknown";
                \Illuminate\Support\Facades\Log::error("Error calculating population rank: " . $e->getMessage());
            }
            
            // Format area to ensure consistent display in both Blade and Vue
            if (isset($countryData->area) && is_numeric($countryData->area)) {
                // Format with thousands separators but no decimal places for large numbers
                $countryData->area = number_format($countryData->area, 0, '.', ',');
            }
            
            // Fetch neighbor countries directly from the API
            $neighbors = [];
            if (!empty($apiData['borders'])) {
                // Get all borders directly from the API
                $bordersStr = implode(',', $apiData['borders']);
                $neighborsResponse = Http::timeout(15)
                    ->retry(3, 1000)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_TCP_KEEPALIVE => 1,
                            CURLOPT_BUFFERSIZE => 1024 * 1024, // 1MB buffer
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                        ]
                    ])
                    ->get("https://restcountries.com/v3.1/alpha?codes={$bordersStr}&fields=cca3,name,flag");
                
                if ($neighborsResponse->successful()) {
                    $apiNeighbors = $neighborsResponse->json();
                    
                    // Get favorite status for neighbors
                    $favoriteCountryCodes = Favorite::all()->pluck('country_code')->toArray();
                    
                    foreach ($apiNeighbors as $neighbor) {
                        // Format the neighbor data for frontend consumption
                        $formattedNeighbor = [
                            'cca3' => $neighbor['cca3'],
                            'name_common' => $neighbor['name']['common'],
                            'flag_emoji' => $neighbor['flag'] ?? null,
                            'is_favorite' => in_array($neighbor['cca3'], $favoriteCountryCodes)
                        ];
                        $neighbors[] = $formattedNeighbor;
                    }
                }
            }
            
            // Return country data with neighbors
            return response()->json([
                'country' => $countryData,
                'neighbors' => $neighbors
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error showing country {$code}: " . $e->getMessage());
            return response()->json(['error' => 'Error retrieving country data', 'details' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Search for countries by name, including translations
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        
        if (empty($search)) {
            return response()->json([]);
        }
        
        try {
            // Use the countries API to search
            $response = Http::timeout(5)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/name/{$search}?fields=name,cca3,cca2,flag");
            
            if ($response->successful()) {
                $countries = $response->json();
                
                // Get favorite status
                $favoriteCountryCodes = Favorite::all()->pluck('country_code')->toArray();
                
                // Format for the frontend
                $formattedCountries = [];
                foreach ($countries as $country) {
                    $formattedCountries[] = [
                        'cca3' => $country['cca3'],
                        'cca2' => $country['cca2'] ?? null,
                        'name' => $country['name']['common'],
                        'flag' => $country['flag'] ?? null,
                        'is_favorite' => in_array($country['cca3'], $favoriteCountryCodes)
                    ];
                }
                
                return response()->json($formattedCountries);
            }
            
            return response()->json([]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error searching countries: " . $e->getMessage());
            return response()->json([]);
        }
    }
    
    /**
     * Get countries by language
     */
    public function byLanguageApi(string $language, Request $request)
    {
        try {
            // Use the REST Countries API to get countries by language
            $response = Http::timeout(5)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/lang/{$language}?fields=name,cca3,cca2,flags,flag");
            
            if ($response->successful()) {
                $countries = $response->json();
                
                // Get favorite status
                $favoriteCountryCodes = Favorite::all()->pluck('country_code')->toArray();
                
                $formattedCountries = collect($countries)->map(function($apiData) use ($favoriteCountryCodes) {
                    // Create lightweight country object with minimal data
                    $lightCountry = [
                        'id' => $apiData['cca3'] ?? null,
                        'cca3' => $apiData['cca3'] ?? null,
                        'cca2' => $apiData['cca2'] ?? null,
                        'name_common' => $apiData['name']['common'] ?? null,
                        'name_official' => $apiData['name']['official'] ?? null,
                        'flag_url' => isset($apiData['flags']) ? ($apiData['flags']['png'] ?? null) : null,
                        'flag_emoji' => $apiData['flag'] ?? null,
                        'is_favorite' => in_array($apiData['cca3'], $favoriteCountryCodes)
                    ];
                    
                    return $lightCountry;
                });
                
                return response()->json([
                    'language' => $language,
                    'countries' => $formattedCountries,
                    'total_count' => count($formattedCountries)
                ]);
            }
            
            // If API call fails, use manual lookup
            return $this->byLanguageManualLookupLightweight($language, $request);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error finding countries by language: ' . $e->getMessage());
            return response()->json(['error' => 'Error finding countries by language', 'details' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Lightweight version of manual lookup that returns minimal data
     */
    private function byLanguageManualLookupLightweight(string $language, Request $request)
    {
        try {
            // Fetch all countries from the API to manually filter by language
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->get("https://restcountries.com/v3.1/all?fields=name,cca3,cca2,languages,flags,flag");
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Could not fetch countries data'], 500);
            }
            
            $countries = $response->json();
            
            // Filter countries that have the specified language
            $matchingCountries = [];
            foreach ($countries as $country) {
                if (isset($country['languages'])) {
                    $countryLanguages = array_map('strtolower', array_values($country['languages']));
                    $languageCode = strtolower($language);
                    
                    // Check if language matches either by code or name
                    if (in_array($languageCode, array_keys($country['languages'])) || 
                        in_array($languageCode, $countryLanguages) || 
                        count(array_filter($countryLanguages, function($lang) use ($languageCode) {
                            return strpos($lang, $languageCode) !== false;
                        })) > 0) {
                        $matchingCountries[] = $country;
                    }
                }
            }
            
            // Get favorite status
            $favoriteCountryCodes = Favorite::all()->pluck('country_code')->toArray();
            
            // Format the countries for response
            $formattedCountries = collect($matchingCountries)->map(function($data) use ($favoriteCountryCodes) {
                $lightCountry = [
                    'id' => $data['cca3'] ?? null,
                    'cca3' => $data['cca3'] ?? null,
                    'cca2' => $data['cca2'] ?? null,
                    'name_common' => $data['name']['common'] ?? null,
                    'name_official' => $data['name']['official'] ?? null,
                    'flag_url' => isset($data['flags']) ? ($data['flags']['png'] ?? null) : null,
                    'flag_emoji' => $data['flag'] ?? null,
                    'is_favorite' => in_array($data['cca3'], $favoriteCountryCodes)
                ];
                
                return $lightCountry;
            });
            
            // Paginate the results - show 24 countries per page
            $perPage = 24;
            $page = request()->input('page', 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedCountries->forPage($page, $perPage),
                $formattedCountries->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            return response()->json([
                'countries' => $paginator->items(),
                'language' => $language,
                'total_count' => $formattedCountries->count(),
                'pagination' => $paginator->links()->toHtml()
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in manual language lookup: ' . $e->getMessage());
            return response()->json(['error' => 'Error finding countries by language', 'details' => $e->getMessage()], 500);
        }
    }
}
