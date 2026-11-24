<?php

test('redirects the homepage to the login page', function (): void {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');
});
