<?php

use App\Models\Session;
use App\Models\User;

test('public can view session with valid share token', function () {
    $user = User::factory()->create();
    $session = Session::factory()->for($user)->create([
        'share_token' => 'test-token-123',
    ]);

    $response = $this->get(route('public.session.show', 'test-token-123'));

    $response->assertStatus(200);
    $response->assertViewIs('sessions.public');
    $response->assertViewHas('session');
});

test('public cannot view session with invalid share token', function () {
    $response = $this->get(route('public.session.show', 'invalid-token'));

    $response->assertStatus(200);
    $response->assertViewIs('sessions.public-error');
    $response->assertViewHas('message');
});

test('public can view free session with custom layout', function () {
    $user = User::factory()->create();
    $session = Session::factory()->for($user)->create([
        'share_token' => 'free-session-token',
    ]);
    $session->layout()->create([
        'layout_data' => ['test' => 'data'],
        'canvas_width' => 800,
        'canvas_height' => 1200,
    ]);

    $response = $this->get(route('public.session.show', 'free-session-token'));

    $response->assertStatus(200);
    $response->assertViewIs('sessions.public-free');
    $response->assertViewHas('session');
    $response->assertViewHas('layout');
});

test('public can download pdf for free session', function () {
    $user = User::factory()->create();
    $session = Session::factory()->for($user)->create([
        'share_token' => 'pdf-session-token',
        'name' => 'Test Session',
    ]);
    $layout = $session->layout()->create([
        'layout_data' => ['test' => 'data'],
        'canvas_width' => 800,
        'canvas_height' => 1200,
        'pdf_path' => 'session-pdfs/test.pdf',
    ]);
    \Illuminate\Support\Facades\Storage::disk('local')->put('session-pdfs/test.pdf', 'fake pdf content');

    $response = $this->get(route('public.session.pdf', 'pdf-session-token'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'inline; filename="test-session.pdf"');
});

test('public cannot download pdf for session without layout', function () {
    $user = User::factory()->create();
    $session = Session::factory()->for($user)->create([
        'share_token' => 'no-layout-token',
    ]);

    $response = $this->get(route('public.session.pdf', 'no-layout-token'));

    $response->assertStatus(404);
    $response->assertViewIs('sessions.public-error');
});

test('public cannot download pdf for session with missing file', function () {
    $user = User::factory()->create();
    $session = Session::factory()->for($user)->create([
        'share_token' => 'missing-pdf-token',
    ]);
    $session->layout()->create([
        'layout_data' => ['test' => 'data'],
        'canvas_width' => 800,
        'canvas_height' => 1200,
        'pdf_path' => 'session-pdfs/missing.pdf',
    ]);

    $response = $this->get(route('public.session.pdf', 'missing-pdf-token'));

    $response->assertStatus(404);
    $response->assertViewIs('sessions.public-error');
});

