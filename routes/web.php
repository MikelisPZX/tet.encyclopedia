<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', [CountryController::class, 'index'])->name('home');
Route::get('/countries/data/{id}', [CountryController::class, 'getData'])->name('countries.getData');
Route::get('/countries/{code}', [CountryController::class, 'show'])->name('countries.show');
Route::get('/languages/{language}', [CountryController::class, 'byLanguage'])->name('countries.by-language');
Route::get('/search', [CountryController::class, 'search'])->name('countries.search');
Route::get('/api/search', [ApiController::class, 'searchCountries'])->name('api.search');
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favorites/{countryId}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

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

require __DIR__.'/auth.php';
