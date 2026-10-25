<?php

namespace App\Http\Middleware;

/**
 * TEMPORAL — MIDDLEWARE DE PROFILING
 * Mide y loggea tiempo por request + queries SQL lentas.
 *
 * Para activar: descomentar en bootstrap/app.php
 *   use App\Http\Middleware\ProfilerMiddleware;
 *   $middleware->append(ProfilerMiddleware::class);
 */

use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

class ProfilerMiddleware
{
    private Logger $log;

    public function __construct(EventsDispatcher $events)
    {
        $this->log = (new Logger('profiler'))
            ->pushHandler(
                (new StreamHandler())
                    ->setProcessor(new PsrLogMessageProcessor())
            );

        $events->listen(QueryExecuted::class, function (QueryExecuted $event): void {
            $duration = $event->time;
            if ($duration > 0.05) {
                $this->log->warning(sprintf(
                    '🐌 SLOW SQL (%.0fms): %s',
                    $duration * 1000,
                    Str::limit($event->query, 300)
                ));
            }
        });
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $start = floatval(microtime());
        $response = $next($request);
        $duration = (floatval(microtime()) - $start) * 1000;

        $this->log->info(sprintf(
            '⏱ [%.0fms] %s %s',
            $duration,
            $request->method(),
            $request->url()
        ));

        return $response;
    }
}