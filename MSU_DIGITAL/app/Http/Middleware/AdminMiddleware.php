<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login dan adalah admin
        if (!Auth::check() || !Auth::user()->is_admin) {
            // Jika bukan admin, redirect ke halaman home atau tampilkan error 403
            // Pilihan: abort(403, 'Unauthorized action.');
            return redirect('/home')->with('error', 'Anda tidak memiliki hak akses admin.');
        }
        return $next($request);
    }
}
