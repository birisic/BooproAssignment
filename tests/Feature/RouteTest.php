<?php

namespace Tests\Feature;

//use Illuminate\Foundation\Testing\RefreshDatabase;
//use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RouteTest extends TestCase
{
    public function test_unauthorized_client_redirected_to_login_page(): void
    {
        $response = $this->get('/api/score/php');

        $response->assertRedirect("/api/login");
    }

    public function test_mandatory_route_parameter_word_not_provided(): void
    {
        $response = $this->get("/api/score/");

        $response->assertSee("404");
        $response->assertSee("Not Found");
        $response->assertStatus(404);
    }

//    public function test_unauthorized_client_receives_oauth_token()
//    {
//        $response = $this->post('/oauth/token', [
//            'grant_type' => 'client_credentials',
//            'client_id' => 1,
//            'client_secret' => "b9rDs9T7PgLyOtogmuBj69MM3etftUWKLZVe7tIN",
//            'scope' => '',
//        ]);
//
//
//        $response->assertStatus(200);
//        return $response->json('access_token');
//    }

//    public function test_authorized_client_searches_word_score(): void
//    {
//        $token = "";//$this->test_unauthorized_client_receives_oauth_token();
//
//        $word = 'css';
//        $headers = ['Authorization' => 'Bearer ' . $token];
//        $scoreResponse = $this->withHeaders($headers)->get("/api/score/$word");
//
//        $scoreResponse->assertStatus(200);
//        $scoreResponse->assertJson([
//            'word' => $word,
//        ]);
//    }
}
