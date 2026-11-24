<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditWrites
{
    /**
     * Records every write (POST, PUT, PATCH, DELETE) made by a signed-in user in the audit log,
     * with who made it, where, how it ended and from which address. Form contents are never logged.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Read before the request runs, because deleting an account signs the user out
        $user = $request->user();

        $response = $next($request);

        if ($user && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            Log::channel('audit')->info('write', [
                'user_id' => $user->id,
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
