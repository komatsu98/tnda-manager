<?php
   
namespace App\Http\Middleware;
   
use Closure;
   
class CheckIpMiddleware
{
    
    public $whiteIps = ['161.97.71.137', '127.0.0.1', '103.216.122.104', '14.160.90.226'];
    public $whiteHosts = ['localhost'];
        
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->ip(), $this->whiteIps)) {
    
            /*
                 You can redirect to any error page. 
            */
            return response()->json(['status' => 'error', 'message' => 'Your IP is not allowed']);
        }
    
        return $next($request);
    }
}