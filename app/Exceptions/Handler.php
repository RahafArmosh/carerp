<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Session\TokenMismatchException;
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            // For AJAX/JSON requests, return JSON instead of redirect
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Your session has expired. Please log in again.',
                    'exception_type' => 'TokenMismatchException'
                ], 419);
            }
            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }
        
        // For AJAX requests or requests expecting JSON, always return JSON
        // Check multiple ways to detect AJAX/JSON requests
        $isAjaxRequest = $request->expectsJson() 
            || $request->ajax() 
            || $request->wantsJson() 
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || strpos($request->header('Accept', ''), 'application/json') !== false
            || strpos($request->path(), 'get-cashiers') !== false; // Specific route check
        
        if ($isAjaxRequest) {
            $statusCode = $this->isHttpException($exception) ? $exception->getStatusCode() : 500;
            
            // Log the exception for debugging
            \Log::error('Exception in AJAX request', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->fullUrl(),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
                'exception_type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ], $statusCode, [
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff'
            ], JSON_UNESCAPED_UNICODE);
        }

        return parent::render($request, $exception);
    }
}
