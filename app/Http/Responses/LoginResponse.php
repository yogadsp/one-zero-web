<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Send each account type to its own dashboard and force a clean Inertia visit.
     */
    public function toResponse($request)
    {
        $destination = (int) $request->user()->level === 1
            ? '/admin/dashboard'
            : '/user/dashboard';

        if ($request->header('X-Inertia')) {
            return Inertia::location($destination);
        }

        return redirect()->intended($destination);
    }
}
