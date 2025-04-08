<?php

namespace Tests\Feature;

use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('countries.index');
    }

    public function test_country_detail_page_loads()
    {
        $country = Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651
        ]);

        $response = $this->get('/countries/' . $country->cca3);

        $response->assertStatus(200);
        $response->assertViewIs('countries.show');
        $response->assertViewHas('country', $country);
    }

    public function test_country_search()
    {
        Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651,
            'translations' => [
                'fra' => ['common' => 'États-Unis']
            ]
        ]);

        $response = $this->get('/?search=United');
        $response->assertStatus(200);
        $response->assertViewHas('countries');
        $this->assertEquals(1, $response->viewData('countries')->count());

        // Test search by translation
        $response = $this->get('/?search=États-Unis');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('countries')->count());
    }

    public function test_by_language_page()
    {
        Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651,
            'languages' => ['eng' => 'English']
        ]);

        Country::create([
            'cca2' => 'GB',
            'cca3' => 'GBR',
            'name_common' => 'United Kingdom',
            'name_official' => 'United Kingdom of Great Britain and Northern Ireland',
            'population' => 67215293,
            'languages' => ['eng' => 'English']
        ]);

        $response = $this->get('/languages/English');
        $response->assertStatus(200);
        $response->assertViewHas('countries');
        $this->assertEquals(2, $response->viewData('countries')->count());
    }
} 