<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user can upload an avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    expect($user->avatar)->toContain($user->avatar_path);
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('uploading a new avatar deletes the previous one', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ]);

    $firstPath = $user->refresh()->avatar_path;

    $this->actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ]);

    $secondPath = $user->refresh()->avatar_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('avatar upload requires an image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

    $response->assertSessionHasErrors('avatar');
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('user can remove their avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $path = $user->refresh()->avatar_path;

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.avatar.destroy'));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('users without an avatar serialize a null avatar url', function () {
    $user = User::factory()->create();

    expect($user->avatar)->toBeNull();
});
