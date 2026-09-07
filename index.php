<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/repositorios', [RepoController::class, 'index']);
$router->get('/repositorios/{id}', [RepoController::class, 'show']);
$router->post('/repositorios/{id}', [RepoController::class, 'update']);
$router->post('/repositorios/{id}/omitir', [RepoController::class, 'dismiss']);

$router->get('/atencion', [AttentionController::class, 'index']);

$router->get('/metricas', [MetricsController::class, 'index']);

$router->get('/categorias', [CategoryController::class, 'index']);
$router->post('/categorias', [CategoryController::class, 'store']);
$router->post('/categorias/{id}', [CategoryController::class, 'update']);
$router->post('/categorias/{id}/eliminar', [CategoryController::class, 'destroy']);

$router->get('/sincronizar', [SyncController::class, 'index']);
$router->post('/sincronizar', [SyncController::class, 'run']);
$router->post('/sincronizar/paso', [SyncController::class, 'step']);
$router->post('/sincronizar/reanalizar', [SyncController::class, 'rescan']);

$router->get('/configuracion', [SettingsController::class, 'index']);
$router->post('/configuracion', [SettingsController::class, 'save']);
$router->post('/tema', [SettingsController::class, 'theme']);

$router->get('/perfil', [ProfileController::class, 'index']);
$router->post('/perfil', [ProfileController::class, 'save']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
