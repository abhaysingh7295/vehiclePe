<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class StaffDetailsJwtMiddleware extends BaseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check for the presence of the Authorization header
            if (! $request->hasHeader('Authorization')) {
                throw new Exception('Missing authorization token');
            }

            // Extract the token from the Authorization header
            $token = $request->bearerToken();

            // Validate and decode the JWT token
            $payload = JWTAuth::setToken($token)->getPayload();
           
            // Extract the user details from the payload
            $userDetails = [
                'user_id' => $payload['user_id'],
                'email' => $payload['email'],
                // Add any other user details you need
            ];
            // Attach the decoded token to the request object for further use
            $request->attributes->add(['userData' =>  $userDetails]);
            $request->attributes->add(['decoded_token' => $payload]);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
