<?php

use App\Models\User;

it('logs the admin in with valid credentials and starts a session', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertNoContent();
    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password with a validation error', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->assertGuest();
});

it('logs the admin out and invalidates the session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/logout');

    $response->assertNoContent();
    $this->assertGuest();
});

it('redirects guests away from the admin vendor list', function () {
    $response = $this->get('/admin/vendors');

    $response->assertRedirect();
});

it('allows the authenticated admin to view the vendor list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/vendors');

    $response->assertOk();
});
