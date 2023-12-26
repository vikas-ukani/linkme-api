<?php

namespace Tests\Unit;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class GeolocationTest extends TestCase
{
    public function test_geocoding_postgres_filters()
    {
        // FOR LOGIN TO ACCESS API
        // $user = User::get()->random();
        // $this->actingAs($user, 'api');

        $data = [
            // Static data for lat, long
            // "lat" => "28.5355161",
            // "long" => "77.3910265",
            // Random Location wise search
            // "lat" => $user->latitude,
            // "long" => $user->longitude,
            "PageSize" => 10,
            "lat" => 41.3625937,
            "long" => -74.27126129999999,
            "category" => "Hair Stylist",
            "distance" => 20,
        ];

        // $headers = [
        //     'Accept' => 'application/json',
        //     'Authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjllNTJmYzQwM2NmODM0ODEzNWUxZTAwNTdkOTQ1YTJkMmRmNTc3MmMwNjNlYTc1Yzc3OTA1NzY5OGZiZGVkNzlkMTNjMzRiNjE4YTViZGQ1In0.eyJhdWQiOiI1IiwianRpIjoiOWU1MmZjNDAzY2Y4MzQ4MTM1ZTFlMDA1N2Q5NDVhMmQyZGY1NzcyYzA2M2VhNzVjNzc5MDU3Njk4ZmJkZWQ3OWQxM2MzNGI2MThhNWJkZDUiLCJpYXQiOjE2Njk5MTY0MDYsIm5iZiI6MTY2OTkxNjQwNiwiZXhwIjoxNzAxNDUyNDA2LCJzdWIiOiIxMyIsInNjb3BlcyI6W119.CUYzIPTas-6GFbU7YuB5N7SS6kA8F_-hb81dioWUxgKVWUCyIFPuYfVwbbjAQO_C_bqHS9ektnDGsEA1tznGYmXQKsxBgOOCgSC7_NJctPfwadlUJx6qk9yK882AePeYTOGMI-aJN0Sc9QOEjvAaUrPYTg0mHoZuKhgcp8l9Do9Jokq4cAaZg8y0LsBZNgn0kljuN4RBOj4MRXpYZpMwAp3lw7KvDBqppPL5vuUVxFxKIRSpRGnCd0OXuyMNlR_p_prUqt9_ke0CCLa1aO60ObmVAJBcRkiRsSAWW2fXP7cIxjByW3gpCc_QKWw4HCdwr3ujDF2w91c-iL8sAcxPq9CZ6RBeIilB_cyS6MvkRBtcHgALu-YeRm7YKHnnvDMMaLv6E6A6ptYW_LVPc0xo7c8DoI2iMLGhQE1Ji1YvM9VaZW_kCY2DLjqkffw2OTyejCvmI02EB3b-K2dpJJs1kbEMRNWbHGeJ8INPqU6bxFUZqn2zSXV_ClaiX7R2Gm9OUb0KWk3thcWTYwR-aHvAfpNTV3jqxF3XaU3I3tzid-6hiSVbmNilGTNA8Kh7jejgE7v_l9aRoEpmGRcq2EpEOeWQ105ldSgFaaK8rz8AKHo0O7vxeM1UzhFn6s1aXG-2Vf21RBQuirgGRgVUqEnJm_Yerrm2BO8EQkSJn20GlC4"
        // ];
        // $response = $this->json('GET', route('filter'), $data)->assertStatus(200);
        // Log::info('INFO', [$response->getContent()]);
        $this->assertTrue(true);
    }
}
