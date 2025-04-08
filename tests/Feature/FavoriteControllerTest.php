<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_toggle_favorite()
    {
        $country = Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651
        ]);

        // Initially no favorites
        $this->assertEquals(0, Favorite::count());

        // Add to favorites
        $response = $this->post('/favorites/' . $country->id);
        $response->assertRedirect();
        $this->assertEquals(1, Favorite::count());

        // Remove from favorites
        $response = $this->post('/favorites/' . $country->id);
        $response->assertRedirect();
        $this->assertEquals(0, Favorite::count());
    }

    public function test_can_toggle_favorite_with_ajax()
    {
        $country = Country::create([
            'cca2' => 'US',
            'cca3' => 'USA',
            'name_common' => 'United States',
            'name_official' => 'United States of America',
            'population' => 331002651
        ]);

        // Add to favorites
        $response = $this->postJson('/favorites/' . $country->id);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_favorite' => true
        ]);
        $this->assertEquals(1, Favorite::count());

        // Remove from favorites
        $response = $this->postJson('/favorites/' . $country->id);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_favorite' => false
        ]);
        $this->assertEquals(0, Favorite::count());
    }
} 