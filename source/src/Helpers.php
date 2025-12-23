<?php

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Check if user session exists, redirect to login if not
 * 
 * @param Response $response PSR-7 Response object
 * @return Response|null Returns redirect response if session doesn't exist, null if session is valid
 */
function requireAuth(Response $response): ?Response {
    if (empty($_SESSION['user_id'] ?? null)) {
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/login');
    }
    return null;
}
