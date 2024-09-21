<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
