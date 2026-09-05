<?php

use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;

it('redirects guests away from the admin package list', function () {
    $response = $this->get('/admin/packages');

    $response->assertRedirect();
});

it('allows the authenticated admin to view the package list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/packages');

    $response->assertOk();
});

it('lets the admin feature a package with a note', function () {
    $user = User::factory()->create();
    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'is_featured' => false,
        'featured_note' => null,
    ]);

    $response = $this->actingAs($user)->post("/admin/packages/{$package->id}", [
        'is_featured' => '1',
        'featured_note' => 'Löst mein Cache-Problem seit Jahren zuverlässig.',
    ]);

    $response->assertRedirect();
    expect($package->fresh())
        ->is_featured->toBeTrue()
        ->featured_note->toBe('Löst mein Cache-Problem seit Jahren zuverlässig.');
});

it('lets the admin unfeature a package by omitting the checkbox', function () {
    $user = User::factory()->create();
    $package = Package::factory()->for(Vendor::factory()->composer())->featured()->create();

    $response = $this->actingAs($user)->post("/admin/packages/{$package->id}", [
        'featured_note' => $package->featured_note,
    ]);

    $response->assertRedirect();
    expect($package->fresh()->is_featured)->toBeFalse();
});

it('rejects a featured_note over 500 characters', function () {
    $user = User::factory()->create();
    $package = Package::factory()->for(Vendor::factory()->composer())->create();

    $response = $this->actingAs($user)->post("/admin/packages/{$package->id}", [
        'is_featured' => '1',
        'featured_note' => str_repeat('a', 501),
    ]);

    $response->assertSessionHasErrors('featured_note');
});

it('denies guests from updating a package', function () {
    $package = Package::factory()->for(Vendor::factory()->composer())->create();

    $response = $this->post("/admin/packages/{$package->id}", ['is_featured' => '1']);

    $response->assertRedirect();
    expect($package->fresh()->is_featured)->toBeFalse();
});

it('lets the admin quickly toggle featured on with the star button', function () {
    $user = User::factory()->create();
    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'is_featured' => false,
        'featured_note' => 'Bestehende Notiz',
    ]);

    $response = $this->actingAs($user)->post("/admin/packages/{$package->id}/toggle-featured");

    $response->assertRedirect();
    expect($package->fresh())
        ->is_featured->toBeTrue()
        ->featured_note->toBe('Bestehende Notiz');
});

it('lets the admin quickly toggle featured off, ignoring any submitted note', function () {
    $user = User::factory()->create();
    $package = Package::factory()->for(Vendor::factory()->composer())->featured()->create();
    $originalNote = $package->featured_note;

    $response = $this->actingAs($user)->post("/admin/packages/{$package->id}/toggle-featured", [
        'featured_note' => 'sollte ignoriert werden',
    ]);

    $response->assertRedirect();
    expect($package->fresh())
        ->is_featured->toBeFalse()
        ->featured_note->toBe($originalNote);
});

it('denies guests from toggling featured', function () {
    $package = Package::factory()->for(Vendor::factory()->composer())->create(['is_featured' => false]);

    $response = $this->post("/admin/packages/{$package->id}/toggle-featured");

    $response->assertRedirect();
    expect($package->fresh()->is_featured)->toBeFalse();
});
