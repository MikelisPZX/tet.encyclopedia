<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\Favorite;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_country()
    {
        $countryData = [
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651,
            'population_rank' => 1,
            'flag_url' => 'https://flagcdn.com/w320/us.png',
            'flag_emoji' => '🇺🇸',
            'area' => 9372610,
            'translations' => ['fra' => ['common' => 'États-Unis']],
            'borders' => ['CAN', 'MEX'],
            'languages' => ['eng' => 'English']
        ];

        $country = Country::create($countryData);

        $this->assertInstanceOf(Country::class, $country);
        $this->assertEquals('US', $country->cca2);
        $this->assertEquals('USA', $country->cca3);
        $this->assertEquals('United States', $country->name_common);
        $this->assertEquals(331002651, $country->population);
    }

    public function test_neighbors_relationship()
    {
        // Create countries
        $usa = Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651,
            'borders' => ['CAN', 'MEX']
        ]);

        $canada = Country::create([
            'cca2' => 'CA',
            'cca3' => 'CAN',
            'name_common' => 'Canada',
            'name_official' => 'Canada',
            'population' => 38005238,
            'borders' => ['USA']
        ]);

        $mexico = Country::create([
            'cca2' => 'MX',
            'cca3' => 'MEX',
            'name_common' => 'Mexico',
            'name_official' => 'United Mexican States',
            'population' => 128932753,
            'borders' => ['USA']
        ]);

        // Test neighbors relationship
        $neighbors = $usa->neighbors();
        
        $this->assertEquals(2, $neighbors->count());
        $this->assertTrue($neighbors->contains($canada));
        $this->assertTrue($neighbors->contains($mexico));
    }

    public function test_favorite_functionality()
    {
        $country = Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651
        ]);

        $this->assertFalse($country->isFavorite());

        // Add to favorites
        Favorite::create(['country_id' => $country->id]);

        $this->assertTrue($country->isFavorite());
    }
}