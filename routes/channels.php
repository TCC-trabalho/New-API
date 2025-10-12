<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aqui você registra os canais de broadcast que sua aplicação usa.
| O canal "pagamentos" será público, então não exige autenticação.
|
*/

Broadcast::channel('pagamentos', function () {
    return true; // canal público
});
