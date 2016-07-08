<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api;
use Closure;
use Illuminate\Support\Facades\App;
use OAuth2\HttpFoundationBridge\Request as OauthRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OauthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
       // return $next($request);
       if(!$request->has('access_token'))
        {
            throw new UnauthorizedHttpException('access token not found');
        }

        $req = Request::createFromGlobals();
        $brigedRequest = OauthRequest::createFromRequest($req);
        $brigedResponse = new \OAuth2\HttpFoundationBridge\Response();

        if(!$token = App::make('oauth')->getAccessTokenData($brigedRequest,$brigedResponse))
        {
            $response = App::make('oauth')->getResponse();

            if($response->isClientError() && $response->getParameter('error'))
            {
                if($response->getParameter('error') == 'expired_token')
                {
                    throw new UnauthorizedHttpException("the access token has expired");
                }

                throw new UnauthorizedHttpException('the access token provided is invalid');
            }
        }
        else
        {
            $request['user_id'] = $token['user_id'];
        }

        return $next($request);
    }
}
