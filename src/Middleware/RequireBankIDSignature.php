<?php

namespace App\Http\Middleware;

use LJSystem\BankID\BankidToken;
use LJSystem\BankID;
use Closure;
use Response;

class RequireBankIDSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!isset($_SERVER['HTTP_BANKID_TOKEN'])) {
            return Response::json([
                'error' => 'BankID auth token required',
            ]);
        }

        $signable = $request->route()->parameters();

        //TODO check expiry time
        $token = BankidToken::where('token', '=', $_SERVER['HTTP_BANKID_TOKEN'])->first();

        if (!$token) {
            return Response::json([
                'error' => 'Invalid BankID auth token',
            ]);
        }

        if ($token->expires_at < now()) {
            return Response::json([
                'error' => 'This BankID token has expired',
            ]);
        }

        if($token->used > 0 && config('bankid.single_use_signature_tokens')) {
            return Response::json([
                'error' => 'This BankID token has already been used',
            ]);
        }

        if (!$this->matchesResource($signable, $token->signable)) {
            return Response::json([
                'error' => 'BankID auth token is issued for another resource',
            ]);
        }

        $token->used = $token->used + 1;
        $token->save();

        return $next($request);
    }

    public function matchesResource($signable, $tokenSignable)
    {
        foreach ($signable as $key => $value) {
            if ($value->is($tokenSignable)) {
                return true;
            }
        }
        return false;
    }
}
