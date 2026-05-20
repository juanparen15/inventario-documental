<?php

use App\Http\Controllers\Api\ChatwootSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Chatwoot Bot
|--------------------------------------------------------------------------
| Estas rutas son consumidas por el flujo de n8n para dar al bot de
| Chatwoot acceso en tiempo real al Sistema Unificado de Registro (SUR)
| y al Inventario Documental (FUID).
|
| Autenticación: header  X-Chatwoot-Token: {CHATWOOT_API_TOKEN}
|                o query ?token={CHATWOOT_API_TOKEN}
*/

Route::get('/chatwoot/search', [ChatwootSearchController::class, 'search']);
Route::get('/chatwoot/stats',  [ChatwootSearchController::class, 'stats']);
