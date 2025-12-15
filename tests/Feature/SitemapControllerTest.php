<?php

test('sitemap can be accessed publicly', function () {
    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
});

test('sitemap contains valid XML structure', function () {
    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($content)->toContain('<urlset');
    expect($content)->toContain('</urlset>');
});

test('sitemap contains home page', function () {
    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain(route('home'));
});

test('sitemap contains legal pages', function () {
    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain(route('legal.mentions'));
    expect($content)->toContain(route('legal.privacy'));
    expect($content)->toContain(route('legal.terms'));
    expect($content)->toContain(route('legal.cookies'));
});

test('sitemap URLs have required fields', function () {
    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $content = $response->getContent();
    expect($content)->toContain('<loc>');
    expect($content)->toContain('<lastmod>');
    expect($content)->toContain('<changefreq>');
    expect($content)->toContain('<priority>');
});

