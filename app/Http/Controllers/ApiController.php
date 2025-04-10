<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Favorite;

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
            // Direct attempt to get matching countries by name
            $response = Http::timeout(5)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/name/{$search}");

            // If name search successful, process results
            if ($response->successful()) {
                $apiCountries = $response->json();
                $formattedCountries = $this->processApiResults($apiCountries, $search);
                
                // Return results if we found any
                if (count($formattedCountries) > 0) {
                    // Limit to 10 results, sorted by name match relevance
                    $formattedCountries = array_slice($formattedCountries, 0, 10);
                    return response()->json($formattedCountries);
                }
            }
            
            // Try alpha code search as a fallback (cca2 or cca3)
            if (strlen($search) <= 3) {
                $codeResponse = Http::timeout(3)
                    ->retry(2, 1000)
                    ->get("https://restcountries.com/v3.1/alpha/{$search}");
                    
                if ($codeResponse->successful()) {
                    $result = $codeResponse->json();
                    
                    // Handle single country response
                    if (isset($result['name'])) {
                        $country = $this->formatApiCountry($result, $search);
                        return response()->json([$country]);
                    }
                    
                    // Handle multiple countries
                    $formattedCountries = $this->processApiResults($result, $search);
                    if (count($formattedCountries) > 0) {
                        return response()->json($formattedCountries);
                    }
                }
            }
            
            // If all previous searches failed, try all-countries approach with translations
            $allCountriesResponse = Http::timeout(10)
                ->retry(2, 1000)
                ->get("https://restcountries.com/v3.1/all");
                
            if ($allCountriesResponse->successful()) {
                $allCountries = $allCountriesResponse->json();
                $formattedCountries = [];
                $searchLower = strtolower($search);
                
                foreach ($allCountries as $apiCountry) {
                    // Check first in translations
                    $matched = false;
                    $matchReason = null;
                    
                    // Check translations first (often more effective)
                    if (isset($apiCountry['translations'])) {
                        foreach ($apiCountry['translations'] as $locale => $translation) {
                            if (isset($translation['common']) && 
                                stripos($translation['common'], $searchLower) !== false) {
                                $matched = true;
                                $matchReason = "{$locale}: {$translation['common']}";
                                break;
                            }
                            if (isset($translation['official']) && 
                                stripos($translation['official'], $searchLower) !== false) {
                                $matched = true;
                                $matchReason = "{$locale}: {$translation['official']}";
                                break;
                            }
                        }
                    }
                    
                    // Then check country names
                    if (!$matched) {
                        if (isset($apiCountry['name']['common']) && 
                            stripos($apiCountry['name']['common'], $searchLower) !== false) {
                            $matched = true;
                        }
                        else if (isset($apiCountry['name']['official']) && 
                            stripos($apiCountry['name']['official'], $searchLower) !== false) {
                            $matched = true;
                        }
                    }
                    
                    // If matched, format the country
                    if ($matched) {
                        $country = $this->formatApiCountry($apiCountry, $search);
                        if ($matchReason) {
                            $country['matched_translation'] = $matchReason;
                        }
                        $formattedCountries[] = $country;
                    }
                }
                
                // Return results if we found any
                if (count($formattedCountries) > 0) {
                    // Limit to 10 results
                    $formattedCountries = array_slice($formattedCountries, 0, 10);
                    return response()->json($formattedCountries);
                }
            }
            
            // If all approaches failed, return empty results
            return response()->json([]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Search error: " . $e->getMessage());
            return response()->json([]);
        }
    }
    
    /**
     * Process API results into formatted countries
     */
    private function processApiResults($apiCountries, $search)
    {
        $formattedCountries = [];
        
        foreach ($apiCountries as $apiCountry) {
            $formattedCountries[] = $this->formatApiCountry($apiCountry, $search);
        }
        
        return $formattedCountries;
    }
    
    /**
     * Format API country data for consistency
     */
    private function formatApiCountry($apiData, $search = null)
    {
        // Check if this country is in favorites
        $isFavorite = false;
        
        if (isset($apiData['cca3'])) {
            $isFavorite = Favorite::where('country_code', $apiData['cca3'])->exists();
        }
        
        // Create a consistent country format without unnecessary fields
        return [
            'id' => $apiData['ccn3'] ?? $apiData['cca3'] ?? null,
            'cca2' => $apiData['cca2'] ?? null,
            'cca3' => $apiData['cca3'] ?? null,
            'name_common' => $apiData['name']['common'] ?? null,
            'name_official' => $apiData['name']['official'] ?? null,
            'flag_emoji' => $apiData['flag'] ?? null,
            'flag_url' => isset($apiData['flags']) ? ($apiData['flags']['png'] ?? null) : null,
            'is_favorite' => $isFavorite
            // Don't include translations or other fields we don't need
        ];
    }
} 
