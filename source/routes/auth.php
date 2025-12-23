<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

return function ($app) {

    // Login - Form - GET
    $app->get('/login', function (Request $request, Response $response, $args) {
        $view = Twig::fromRequest($request);
        
        return $view->render($response, 'unauth_pages/login.html', [
            'msg_error' => null,
            'username' => '',
            'session' => $_SESSION ?? [],
        ]);
    })->setName('login');


    // Login - Handler - POST
    $app->post('/login', function (Request $request, Response $response) {
        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $user = new User($db);

        $username = $data['user_username'] ?? '';
        $password = $data['user_password'] ?? '';
        $error = null;

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            $authenticatedUser = $user->authenticate($username, $password);
            
            if ($authenticatedUser) {
                // Start session and store user info
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $authenticatedUser['user_id'];
                $_SESSION['user_uuid'] = $authenticatedUser['user_uuid'];
                $_SESSION['user_username'] = $authenticatedUser['user_username'];
                $_SESSION['user_email'] = $authenticatedUser['user_email'];
                
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', '/dashboard');
            } else {
                $error = 'Invalid username or password';
            }
        }

        return $view->render($response, 'unauth_pages/login.html', [
            'msg_error' => $error,
            'username' => $username,
            'session' => $_SESSION ?? [],
        ]);
    });


    // Signup - Form - GET
    $app->get('/signup', function (Request $request, Response $response, $args) {
        $view = Twig::fromRequest($request);
        
        return $view->render($response, 'unauth_pages/signup.html', [
            'session' => $_SESSION ?? [],
        ]);
    })->setName('signup');


    // Signup - Handler - POST
    $app->post('/signup', function (Request $request, Response $response) {
        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $user = new User($db);

        $username = $data['user_username'] ?? '';
        $email = $data['user_email'] ?? '';
        $password = $data['user_password'] ?? '';
        $confirmPassword = $data['user_password_confirm'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'All fields are required';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long';
        } elseif (strlen($password) < 3) {
            $error = 'Password must be at least 3 characters long';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } else {
            try {
                $user->create([
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                ]);
                $success = true;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        return $view->render($response, 'unauth_pages/signup.html', [
            'msg_error' => $error,
            'msg_success' => $success ? 'Account created successfully! Please login.' : null,
            'username' => $username,
            'email' => $email,
            'session' => $_SESSION ?? [],
        ]);
    });


    // Change Password - Form - GET
    $app->get('/change-password', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        
        return $view->render($response, 'auth_pages/change_password.html', [
            'session' => $_SESSION ?? [],
            'msg_success' => null,
        ]);
    })->setName('change-password');


    // Change Password - Handler - POST
    $app->post('/change-password', function (Request $request, Response $response) {
        $authCheck = requireAuth($response);
        if ($authCheck) return $authCheck;

        $view = Twig::fromRequest($request);
        $data = $request->getParsedBody();
        $db = $this->get('db');
        $user = new User($db);

        $currentPassword = $data['app_current_password'] ?? '';
        $newPassword = $data['app_new_password'] ?? '';
        $confirmPassword = $data['app_confirm_password'] ?? '';
        $error = null;
        $success = false;

        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All fields are required';
        } elseif (strlen($newPassword) < 3) {
            $error = 'New password must be at least 3 characters long';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            // Verify current password
            $userData = $user->findById($_SESSION['user_id']);
            
            if (!$userData || !password_verify($currentPassword, $userData['user_password'])) {
                $error = 'Current password is incorrect';
            } else {
                try {
                    $user->updatePassword($_SESSION['user_id'], $newPassword);
                    $success = true;
                } catch (Exception $e) {
                    $error = 'Failed to update password: ' . $e->getMessage();
                }
            }
        }

        return $view->render($response, 'auth_pages/change_password.html', [
            'session' => $_SESSION ?? [],
            'msg_error' => $error,
            'msg_success' => $success ? 'Password changed successfully!' : null,
        ]);
    });

    
    // Logout - Route - GET
    $app->get('/logout', function (Request $request, Response $response, $args) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_destroy();
        
        return $response
            ->withStatus(302)
            ->withHeader('Location', '/login');
    })->setName('logout');
};
