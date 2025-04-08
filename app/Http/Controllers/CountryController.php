<?php

namespace App\Http\Controllers;

use App\Models\Country;
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
    private $dbCountry;
    
    public function __construct($apiData, $dbCountry = null) {
        $this->id = $apiData['ccn3'] ?? ($dbCountry->id ?? null);
        $this->cca2 = $apiData['cca2'];
        $this->cca3 = $apiData['cca3'];
        $this->name_common = $apiData['name']['common'];
        $this->name_official = $apiData['name']['official'];
        $this->population = $apiData['population'] ?? 0;
        $this->population_rank = $apiData['populationRank'] ?? null;
        $this->flag_url = isset($apiData['flags']) ? ($apiData['flags']['png'] ?? null) : null;
        $this->flag_emoji = $apiData['flag'] ?? null;
        $this->area = $apiData['area'] ?? 0;
        $this->languages = isset($apiData['languages']) ? (array)$apiData['languages'] : [];
        $this->borders = $apiData['borders'] ?? [];
        $this->dbCountry = $dbCountry;
    }
    
    public function isFavorite() {
        if ($this->dbCountry) {
            return $this->dbCountry->isFavorite();
        }
        // If no db country, check if it's a favorite by ID
        return $this->id ? \App\Models\Favorite::where('country_id', $this->id)->exists() : false;
    }
}

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $countries = collect([]);
        
        // Get favorite countries
        $favorites = Country::whereHas('favorites')->get();
        
        if ($search) {
            // Use direct API search for initial load when search is provided
            try {
                $apiController = new ApiController();
                $apiRequest = Request::create('/api/search', 'GET', ['q' => $search]);
                $response = $apiController->searchCountries($apiRequest);
                
                $apiData = json_decode($response->getContent(), true);
                
                // Format API data to match our view expectations
                if (is_array($apiData) && !isset($apiData['error'])) {
                    $countries = collect($apiData)->map(function($country) {
                        return new ApiCountry($country);
                    });
                } else {
                    // Fallback to database search if API fails
                    $countries = Country::where('name_common', 'like', "%{$search}%")
                        ->orWhere('name_official', 'like', "%{$search}%")
                        ->orWhere(function($query) use ($search) {
                            // Search in translations - need to check all translation values
                            $query->where(function($q) use ($search) {
                                // Check each translation's common name field
                                foreach (config('app.available_locales', ['fra', 'spa', 'deu', 'est', 'fin', 'rus']) as $locale) {
                                    $q->orWhereRaw("LOWER(JSON_EXTRACT(translations, '$.\"{$locale}\".common')) LIKE ?", ['%' . strtolower($search) . '%']);
                                }
                            });
                        })
                        ->get();
                }
            } catch (\Exception $e) {
                // Fallback to database search if API request fails
                $countries = Country::where('name_common', 'like', "%{$search}%")
                    ->orWhere('name_official', 'like', "%{$search}%")
                    ->orWhere(function($query) use ($search) {
                        // Search in translations - need to check all translation values
                        $query->where(function($q) use ($search) {
                            // Check each translation's common name field
                            foreach (config('app.available_locales', ['fra', 'spa', 'deu', 'est', 'fin', 'rus']) as $locale) {
                                $q->orWhereRaw("LOWER(JSON_EXTRACT(translations, '$.\"{$locale}\".common')) LIKE ?", ['%' . strtolower($search) . '%']);
                            }
                        });
                    })
                    ->get();
            }
        }
        
        return view('countries.index', compact('countries', 'favorites', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $code)
    {
        try {
            // Fetch country data from REST Countries API
            $response = Http::timeout(3)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/alpha/{$code}");
            
            if (!$response->successful()) {
                // Fallback to database if API fails
                return $this->showFromDatabase($code);
            }
            
            $apiData = $response->json()[0];
            
            // Check if we have a record for this country in our database for favorites
            $country = Country::where('cca3', $code)->first();
            
            // If we don't have this country in our database but it exists in the API
            if (!$country && isset($apiData['ccn3'])) {
                // Create a minimal record for favoriting functionality
                $country = Country::firstOrCreate(
                    ['cca3' => $code],
                    [
                        'id' => $apiData['ccn3'],
                        'cca2' => $apiData['cca2'],
                        'name_common' => $apiData['name']['common'],
                        'name_official' => $apiData['name']['official'],
                        'flag_emoji' => $apiData['flag'] ?? null
                    ]
                );
            }
            
            // Format API data for the view
            $countryData = new ApiCountry($apiData, $country);
            
            // Fetch neighbor countries from the API
            $neighbors = collect([]);
            if (!empty($countryData->borders)) {
                $borderCodes = implode(',', $countryData->borders);
                $neighborsResponse = Http::timeout(3)
                    ->retry(2, 1000)
                    ->get("https://restcountries.com/v3.1/alpha?codes={$borderCodes}");
                
                if ($neighborsResponse->successful()) {
                    $apiNeighbors = $neighborsResponse->json();
                    $neighbors = collect($apiNeighbors)->map(function($neighbor) {
                        return new ApiCountry($neighbor);
                    });
                }
            }
            
            return view('countries.show', ['country' => $countryData, 'neighbors' => $neighbors]);
        } catch (\Exception $e) {
            // Fallback to database if API request fails
            return $this->showFromDatabase($code);
        }
    }
    
    /**
     * Fallback to show country from database
     */
    private function showFromDatabase(string $code)
    {
        $country = Country::where('cca3', $code)->firstOrFail();
        $neighbors = $country->neighbors();
        
        return view('countries.show', compact('country', 'neighbors'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Display countries by language.
     */
    public function byLanguage(string $language)
    {
        try {
            // Increase timeout for large responses like English (91 countries)
            $timeout = 15; // 15 seconds instead of default 5
            
            // First try with the exact case as provided (the API is case-sensitive)
            $response = Http::timeout($timeout)
                ->retry(3, 1000)
                ->get("https://restcountries.com/v3.1/lang/{$language}");
            
            // If exact case fails, try lowercase
            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::info("First attempt for language '{$language}' failed. Trying lowercase.");
                $lowercaseLanguage = strtolower(trim($language));
                $response = Http::timeout($timeout)
                    ->retry(3, 1000)
                    ->get("https://restcountries.com/v3.1/lang/{$lowercaseLanguage}");
            }
            
            if ($response->successful()) {
                $apiCountries = $response->json();
                \Illuminate\Support\Facades\Log::info("Found " . count($apiCountries) . " countries for language '{$language}'");
                
                // Convert all API countries to ApiCountry objects in chunks to avoid memory issues
                $allCountries = collect([]);
                foreach (array_chunk($apiCountries, 20) as $chunk) {
                    $allCountries = $allCountries->merge(
                        collect($chunk)->map(function($country) {
                            return new ApiCountry($country);
                        })
                    );
                }
                
                // Paginate the results - show 24 countries per page
                $perPage = 24;
                $page = request()->input('page', 1);
                $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allCountries->forPage($page, $perPage),
                    $allCountries->count(),
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
                
                return view('countries.by-language', [
                    'countries' => $paginator,
                    'language' => $language,
                    'total_count' => $allCountries->count()
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning("API response unsuccessful for language '{$language}'. Status: {$response->status()}");
            }
            
            // If direct lookup fails, try looking it up by iterating through all countries
            return $this->byLanguageManualLookup($language);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in byLanguage for '{$language}': " . $e->getMessage());
            return $this->byLanguageFromDatabase($language);
        }
    }
    
    /**
     * Fallback method to manually look up countries by language
     */
    private function byLanguageManualLookup(string $language)
    {
        try {
            // Fetch all countries from the API with increased timeout
            $response = Http::timeout(15)
                ->retry(3, 1000)
                ->get("https://restcountries.com/v3.1/all");
            
            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::warning("Failed to fetch all countries for manual language lookup. Status: {$response->status()}");
                return $this->byLanguageFromDatabase($language);
            }
            
            $apiCountries = $response->json();
            \Illuminate\Support\Facades\Log::info("Retrieved " . count($apiCountries) . " countries for manual filtering by language '{$language}'");
            
            $exactSearchTerm = trim($language);
            $lowerSearchTerm = strtolower($exactSearchTerm);
            
            // Try matching both exact case and lowercase for more flexibility
            $matchingCountries = [];
            
            foreach ($apiCountries as $country) {
                if (isset($country['languages'])) {
                    foreach ($country['languages'] as $langCode => $langName) {
                        if ($langName === $exactSearchTerm || 
                            strtolower($langName) === $lowerSearchTerm ||
                            $langCode === $exactSearchTerm ||
                            strtolower($langCode) === $lowerSearchTerm) {
                            $matchingCountries[] = $country;
                            break;
                        }
                    }
                }
            }
            
            \Illuminate\Support\Facades\Log::info("Manual filtering found " . count($matchingCountries) . " countries for language '{$language}'");
            
            // Convert to ApiCountry objects in chunks to avoid memory issues
            $allCountries = collect([]);
            foreach (array_chunk($matchingCountries, 20) as $chunk) {
                $allCountries = $allCountries->merge(
                    collect($chunk)->map(function($country) {
                        return new ApiCountry($country);
                    })
                );
            }
            
            // Paginate the results - show 24 countries per page
            $perPage = 24;
            $page = request()->input('page', 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allCountries->forPage($page, $perPage),
                $allCountries->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            // Debug info
            $debugInfo = [
                'language_search_term' => $language,
                'found_countries_count' => count($matchingCountries),
                'all_available_languages' => $this->getAllApiLanguages($apiCountries)
            ];
            
            return view('countries.by-language', [
                'countries' => $paginator,
                'language' => $language,
                'total_count' => $allCountries->count(),
                'debug' => $debugInfo
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in byLanguageManualLookup for '{$language}': " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->byLanguageFromDatabase($language);
        }
    }
    
    /**
     * Helper to get all available languages from the API
     */
    private function getAllApiLanguages($apiCountries)
    {
        $allLanguages = [];
        
        foreach ($apiCountries as $country) {
            if (isset($country['languages'])) {
                foreach ($country['languages'] as $code => $name) {
                    $allLanguages[$name] = $code;
                }
            }
        }
        
        ksort($allLanguages);
        return $allLanguages;
    }
    
    /**
     * Fallback to show countries by language from database
     */
    private function byLanguageFromDatabase(string $language)
    {
        $countries = Country::whereJsonContains('languages', $language)->get();
        
        return view('countries.by-language', [
            'countries' => $countries,
            'language' => $language
        ]);
    }
    
    /**
     * Search for countries by name or translation.
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        
        if (empty($search)) {
            return response()->json([]);
        }
        
        $countries = Country::where('name_common', 'like', "%{$search}%")
            ->orWhere('name_official', 'like', "%{$search}%")
            ->orWhere(function($query) use ($search) {
                // Search in translations - need to check all translation values
                $query->where(function($q) use ($search) {
                    // Check each translation's common name field
                    foreach (config('app.available_locales', ['fra', 'spa', 'deu', 'est', 'fin', 'rus']) as $locale) {
                        $q->orWhereRaw("LOWER(JSON_EXTRACT(translations, '$.\"{$locale}\".common')) LIKE ?", ['%' . strtolower($search) . '%']);
                    }
                });
            })
            ->limit(10)
            ->get();
            
        // Add favorite information to each country
        $countries->transform(function($country) {
            $country->is_favorite = $country->isFavorite();
            return $country;
        });
            
        return response()->json($countries);
    }
    
    /**
     * Get country data by ID.
     */
    public function getData($id)
    {
        // First try to get from database
        $country = Country::find($id);
        
        // If not in database, try API
        if (!$country) {
            try {
                // Try to find by numeric code (ccn3)
                $response = Http::timeout(3)
                    ->retry(2, 1000)
                    ->get("https://restcountries.com/v3.1/alpha/{$id}");
                
                if ($response->successful()) {
                    $apiData = $response->json()[0];
                    
                    // Create a minimal record in database
                    $country = Country::create([
                        'id' => $id,
                        'cca3' => $apiData['cca3'],
                        'cca2' => $apiData['cca2'],
                        'name_common' => $apiData['name']['common'],
                        'name_official' => $apiData['name']['official'],
                        'flag_emoji' => $apiData['flag'] ?? null
                    ]);
                    
                    return response()->json($country);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Country not found'], 404);
            }
        }
        
        if (!$country) {
            return response()->json(['error' => 'Country not found'], 404);
        }
        
        return response()->json($country);
    }
}
