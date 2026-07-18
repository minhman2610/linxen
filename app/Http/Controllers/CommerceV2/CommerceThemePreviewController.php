<?php

namespace App\Http\Controllers\CommerceV2;

use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceThemePreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CommerceThemePreviewController extends Controller
{
    public function __construct(
        protected CommerceThemePreviewService $preview
    ) {
    }

    public function activate(
        Request $request,
        string $target
    ): RedirectResponse {
        $route = $this->targets()[$target] ?? null;

        abort_if($route === null, 404);

        $expiresAt = max(
            time() + 300,
            (int) $request->query(
                'expires',
                time() + 14400
            )
        );

        $this->preview->activate(
            $request->session(),
            CommerceThemePreviewService::THEME,
            $expiresAt
        );

        return redirect()
            ->route($route)
            ->withHeaders([
                'Cache-Control' => 'private, no-store',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            ]);
    }

    public function clear(
        Request $request
    ): RedirectResponse {
        $this->preview->clear($request->session());

        return redirect()
            ->route('commerce.v2.home')
            ->with('success', 'Đã thoát bản xem trước giao diện.');
    }

    protected function targets(): array
    {
        return [
            'home' => 'commerce.v2.home',
            'shop' => 'commerce.v2.shop',
            'search' => 'commerce.v2.search',
            'discover' => 'commerce.v2.discover',
            'cart' => 'commerce.v2.cart.index',
            'checkout' => 'commerce.v2.checkout.index',
            'account' => 'commerce.v2.account.index',
            'orders' => 'commerce.v2.orders.index',
        ];
    }
}
