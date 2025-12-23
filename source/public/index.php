<?php
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../vendor/autoload.php';

// Load Database class
require __DIR__ . '/../src/Database.php';

// Load User class
require __DIR__ . '/../src/User.php';

// Load Weight class
require __DIR__ . '/../src/Weight.php';

// Load DataObject class
require __DIR__ . '/../src/DataObject.php';

// Load Module class
require __DIR__ . '/../src/Module.php';

// Load Helpers
require __DIR__ . '/../src/Helpers.php';

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Build DI Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'db' => function () {
        $dbConfig = require __DIR__ . '/../config/database.php';
        return new Database($dbConfig['db']);
    },
]);
$container = $containerBuilder->build();

// Set container for Slim
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

// Add global variables to Twig
$twig->getEnvironment()->addGlobal('site_name', $_ENV['SITE_NAME'] ?? 'DLog');
$twig->getEnvironment()->addGlobal('current_year', date('Y'));
$twig->getEnvironment()->addGlobal('statcounter_project', $_ENV['STATCOUNTER_PROJECT'] ?? null);
$twig->getEnvironment()->addGlobal('statcounter_security', $_ENV['STATCOUNTER_SECURITY'] ?? null);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Add routing middleware
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Register routes with the app
$authRoutes = require __DIR__ . '/../routes/auth.php';
$appRoutes = require __DIR__ . '/../routes/app.php';

$authRoutes($app);
$appRoutes($app);

$app->run();
