<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tarefas agendadas. Em produção, um único cron chama o agendador:
|     * * * * * cd /caminho/do/projeto && php artisan schedule:run
| Em desenvolvimento: php artisan schedule:work
*/

// Devolve ao estoque os pneus de compras que não foram pagas no prazo.
Schedule::command('purchases:expire')->everyMinute()->withoutOverlapping();
