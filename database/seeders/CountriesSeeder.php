<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding countries data...');
        
        try {
            // Use the all.json file from the root folder
            $jsonPath = base_path('all.json');
            
            if (!File::exists($jsonPath)) {
                $this->command->error('Countries data file not found at: ' . $jsonPath);
                return;
            }
            
            $this->command->info('Loading countries data from file...');
            $countries = json_decode(File::get($jsonPath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error('Error parsing JSON: ' . json_last_error_msg());
                return;
            }
            
            $this->command->info('Found ' . count($countries) . ' countries to seed.');
            
            // Clear existing data
            Country::truncate();
            
            $bar = $this->command->getOutput()->createProgressBar(count($countries));
            $bar->start();
            
            $imported = 0;
            $errors = 0;
            
            foreach ($countries as $countryData) {
                try {
                    // Map API data to our model structure
                    $country = new Country();
                    $country->cca2 = $countryData['cca2'] ?? null;
                    $country->cca3 = $countryData['cca3'] ?? null;
                    $country->name_common = $countryData['name']['common'] ?? null;
                    $country->name_official = $countryData['name']['official'] ?? null;
                    $country->population = $countryData['population'] ?? null;
                    $country->population_rank = $countryData['population'] ? $this->getPopulationRank($countryData['population']) : null;
                    $country->flag_url = $countryData['flags']['png'] ?? null;
                    $country->flag_emoji = $countryData['flag'] ?? null;
                    $country->area = $countryData['area'] ?? null;
                    
                    // Handle nested JSON data
                    $country->translations = $this->extractTranslations($countryData);
                    $country->borders = $countryData['borders'] ?? [];
                    $country->languages = $countryData['languages'] ?? [];
                    
                    $country->save();
                    $imported++;
                } catch (\Exception $e) {
                    $this->command->error('Error importing country: ' . ($countryData['name']['common'] ?? 'Unknown') . ' - ' . $e->getMessage());
                    $errors++;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->command->newLine(2);
            
            $this->command->info("Import completed: $imported countries imported, $errors errors");
            
        } catch (\Exception $e) {
            $this->command->error('An error occurred: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract translations from country data
     */
    private function extractTranslations(array $countryData): array
    {
        $translations = [];
        
        if (isset($countryData['translations'])) {
            foreach ($countryData['translations'] as $langCode => $translation) {
                if (isset($translation['common'])) {
                    $translations[$langCode] = $translation['common'];
                }
            }
        }
        
        // Also add native names as translations
        if (isset($countryData['name']['nativeName'])) {
            foreach ($countryData['name']['nativeName'] as $langCode => $nativeName) {
                if (isset($nativeName['common'])) {
                    $translations[$langCode] = $nativeName['common'];
                }
            }
        }
        
        return $translations;
    }
    
    /**
     * Determine population rank based on population size
     */
    private function getPopulationRank(int $population): int
    {
        static $populationRanks = [];
        static $isInitialized = false;

        if (!$isInitialized) {
            // Get all countries and sort by population
            $jsonPath = base_path('all.json');
            $countries = json_decode(File::get($jsonPath), true);
            
            // Sort countries by population in descending order
            usort($countries, function($a, $b) {
                return ($b['population'] ?? 0) <=> ($a['population'] ?? 0);
            });
            
            // Create population rank mapping
            foreach ($countries as $index => $country) {
                if (isset($country['population'])) {
                    $populationRanks[$country['population']] = $index + 1;
                }
            }
            
            $isInitialized = true;
        }

        return $populationRanks[$population] ?? 0;
    }
} 