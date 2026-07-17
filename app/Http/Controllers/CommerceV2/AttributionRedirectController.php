<?php

namespace App\Http\Controllers\CommerceV2;

use App\Http\Controllers\Controller;
use App\Services\CommerceV2\AttributionSessionService;
use App\Services\CommerceV2\CustomerSessionService;
use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class AttributionRedirectController extends Controller
{
    public function __construct(
        protected ErpCommerceClient $client,
        protected AttributionSessionService $attribution,
        protected CustomerSessionService $customer
    ) {
    }

    public function __invoke(
        Request $request,
        string $token
    ): RedirectResponse {
        try {
            $resolved = (array) data_get(
                $this->client->resolveAttribution($token),
                'data',
                []
            );
            $target = $this->safeTarget(
                (string) data_get(
                    $resolved,
                    'target',
                    '/v2'
                )
            );
            $capture = $this->attribution->capture(
                $request->session(),
                $token
            );
            $customerToken = $this->customer->token(
                $request->session()
            );

            if (data_get($capture, 'first_created')) {
                $this->recordQuietly(
                    $token,
                    'session_first',
                    $customerToken
                );
            }

            $this->recordQuietly(
                $token,
                'session_last',
                $customerToken
            );

            return redirect()
                ->to($target)
                ->withHeaders([
                    'Referrer-Policy' => 'no-referrer',
                    'Cache-Control' => 'no-store, private',
                ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.home')
                ->with(
                    'error',
                    'Liên kết theo dõi không hợp lệ hoặc đã hết hạn.'
                );
        }
    }

    protected function recordQuietly(
        string $token,
        string $eventType,
        string $customerToken
    ): void {
        try {
            $this->client->recordAttributionEvent(
                $token,
                $eventType,
                $customerToken !== ''
                    ? $customerToken
                    : null
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function safeTarget(string $target): string
    {
        $target = trim($target);

        if (
            ! str_starts_with($target, '/v2')
            || str_starts_with($target, '//')
            || str_contains($target, "\r")
            || str_contains($target, "\n")
        ) {
            return '/v2';
        }

        return $target;
    }
}
