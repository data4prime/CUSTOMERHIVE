<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class AuthenticationTest extends TestCase
{
        public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('admin/login');
 
        $response->assertStatus(200);
    }
 
    public function test_users_can_authenticate_using_the_login_screen()
    {

 
        $response = $this->post('admin/login', [
            'email' => 'admin@chive.com',
            'password' => '123456',
        ]);
 
        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

}
