<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// API routes for the SPA
Route::get('/api/search', [ApiController::class, 'searchCountries'])->name('api.search');
Route::get('/search', [ApiController::class, 'searchCountries'])->name('countries.search');
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favorites/{countryId}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
Route::get('/countries/data/{id}', [CountryController::class, 'getData'])->name('countries.getData');

// API endpoints for data
Route::get('/api/countries/{code}', [CountryController::class, 'show'])->name('api.countries.show');
Route::get('/api/languages/{language}', [CountryController::class, 'byLanguageApi'])->name('api.countries.by-language');

// Auth routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Debug routes
Route::get('/debug/country/{code}', function($code) {
    $response = Http::timeout(3)
        ->retry(2, 1000)
        ->get("https://restcountries.com/v3.1/alpha/{$code}");
    
    if ($response->successful()) {
        $countryData = $response->json()[0];
        return response()->json([
            'raw_languages' => $countryData['languages'] ?? [],
            'other_data' => $countryData
        ]);
    }
    
    return response()->json(['error' => 'Country not found'], 404);
});

Route::get('/debug/language/{language}', function($language) {
    $response = Http::timeout(5)
        ->retry(2, 1000)
        ->get("https://restcountries.com/v3.1/lang/{$language}");
    
    if ($response->successful()) {
        $countries = $response->json();
        return response()->json([
            'count' => count($countries),
            'language' => $language,
            'countries' => collect($countries)->map(function($country) {
                return [
                    'name' => $country['name']['common'],
                    'languages' => $country['languages'] ?? []
                ];
            })
        ]);
    }
    
    return response()->json(['error' => 'No countries found for this language', 'language' => $language], 404);
});

// Auth routes
require __DIR__.'/auth.php';

// Catch-all route to serve the SPA
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('home');
