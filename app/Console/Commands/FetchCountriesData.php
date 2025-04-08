<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class FetchCountriesData extends Command
{
    protected $signature = 'countries:fetch';
    protected $description = 'Fetch countries data from REST Countries API and seed the database';

    public function handle()
    {
        $this->info('Fetching countries data from REST Countries API...');

        try {
            // Only fetch the required fields according to the requirements
            $fields = [
                'name',           // For common and official names
                'cca2',          // For country code
                'cca3',          // Alternative country code (needed for borders)
                'population',     // For population and rank
                'flags',         // For flag URL
                'area',          // For area
                'borders',       // For neighboring countries
                'languages',     // For languages
                'translations'   // For search functionality in different languages
            ];

            $response = Http::get('https://restcountries.com/v3.1/all?fields=' . implode(',', $fields));
            
            if (!$response->successful()) {
                $this->error('Failed to fetch data from API. Status code: ' . $response->status());
                return 1;
            }

            $countries = $response->json();
            
            if (empty($countries)) {
                $this->error('No countries data received from API');
                return 1;
            }

            $this->info('Successfully fetched ' . count($countries) . ' countries');

            // Save the raw data
            File::put(base_path('all.json'), $response->body());
            $this->info('Countries data saved to all.json');

            // Run the seeder
            $this->call('db:seed', ['--class' => 'CountriesSeeder']);

            $this->info('Countries data has been successfully fetched and seeded to the database');
            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }
    }
} 