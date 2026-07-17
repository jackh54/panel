<?php

namespace Pterodactyl\Http\Middleware;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Events\Auth\FailedCaptcha;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyTurnstile
{
    public function __construct(private Dispatcher $dispatcher, private Repository $config)
    {
    }

    /**
     * Verify a Cloudflare Turnstile token when captcha is enabled.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!$this->config->get('turnstile.enabled')) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response')
            ?: $request->input('g-recaptcha-response');

        $result = null;

        if (!empty($token)) {
            $client = new Client(['timeout' => 10]);
            $res = $client->post($this->config->get('turnstile.domain'), [
                'form_params' => [
                    'secret' => $this->config->get('turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ],
            ]);

            if ($res->getStatusCode() === 200) {
                $result = json_decode($res->getBody());

                if (!empty($result->success)) {
                    return $next($request);
                }
            }
        }

        $this->dispatcher->dispatch(
            new FailedCaptcha(
                $request->ip(),
                !empty($result) ? ($result->hostname ?? null) : null
            )
        );

        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Failed to validate Turnstile captcha data.');
    }
}
