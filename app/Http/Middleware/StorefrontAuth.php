<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StorefrontAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Đã auto-login từ ERP
        if (session()->has('customer')) {
            return $next($request);
        }

        // Chưa login → quay về checkout
        return redirect()
            ->route('linxen.checkout')
            ->with('warning', 'Vui lòng đăng nhập để tiếp tục.');
    }
}
