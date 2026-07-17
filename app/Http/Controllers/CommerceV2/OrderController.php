<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\AttributionSessionService;
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
use App\Services\CommerceV2\ErpCommerceClient;
use App\Services\CommerceV2\OrderIdempotencySessionService;
use App\Services\CommerceV2\SessionCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        protected ErpCommerceClient $client,
        protected CustomerSessionService $customer,
        protected CheckoutQuoteSessionService $quoteSession,
        protected OrderIdempotencySessionService $idempotency,
        protected AttributionSessionService $attribution,
        protected SessionCartService $cart
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index')
                ->with('error', 'Phiên customer chưa đăng nhập.');
        }

        $quoteId = $this->quoteSession->id(
            $request->session()
        );

        if ($quoteId === '') {
            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with(
                    'error',
                    'Chưa có báo giá hợp lệ để đặt hàng.'
                );
        }

        try {
            $key = $this->idempotency->key(
                $request->session(),
                $quoteId
            );
            $result = (array) data_get(
                $this->client->commitOrder(
                    $this->customer->token(
                        $request->session()
                    ),
                    $quoteId,
                    $key,
                    $this->attribution->payload(
                        $request->session()
                    )
                ),
                'data',
                []
            );
            $orderId = trim((string) data_get(
                $result,
                'order_id'
            ));

            if ($orderId === '') {
                throw new \RuntimeException(
                    'ERP không trả local order id.'
                );
            }

            $this->cart->clear($request->session());
            $this->quoteSession->forget(
                $request->session()
            );
            $this->idempotency->forget(
                $request->session(),
                $quoteId
            );

            return redirect()
                ->route(
                    'commerce.v2.orders.show',
                    ['order' => $orderId]
                )
                ->with(
                    'success',
                    data_get($result, 'idempotent_replay')
                        ? 'Đơn hàng đã được ghi nhận trước đó.'
                        : 'Đơn hàng đã được ghi nhận an toàn.'
                );
        } catch (CommerceV2ClientException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Không thể ghi nhận đơn hàng lúc này.'
            );
        }
    }

    public function index(
        Request $request
    ): View|RedirectResponse {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index')
                ->with('error', 'Phiên customer chưa đăng nhập.');
        }

        try {
            $orders = (array) data_get(
                $this->client->orders(
                    $this->customer->token(
                        $request->session()
                    )
                ),
                'data',
                []
            );

            return view('commerce_v2.pages.orders', [
                'orders' => $orders,
                'pageTitle' => 'Đơn hàng — LIN XÉN',
                'pageDescription' => 'Danh sách đơn hàng của anh.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.account.index')
                ->with('error', 'Không thể tải đơn hàng.');
        }
    }

    public function show(
        Request $request,
        string $order
    ): View|RedirectResponse {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index');
        }

        try {
            $result = (array) data_get(
                $this->client->order(
                    $this->customer->token(
                        $request->session()
                    ),
                    $order
                ),
                'data',
                []
            );

            return view('commerce_v2.pages.order', [
                'order' => $result,
                'pageTitle' => data_get(
                    $result,
                    'order_code',
                    'Đơn hàng'
                ) . ' — LIN XÉN',
                'pageDescription' => 'Chi tiết đơn hàng LIN XÉN.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.orders.index')
                ->with('error', 'Không tìm thấy đơn hàng.');
        }
    }

    public function cancel(
        Request $request,
        string $order
    ): RedirectResponse {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index');
        }

        try {
            $this->client->cancelOrder(
                $this->customer->token(
                    $request->session()
                ),
                $order
            );

            return back()->with(
                'success',
                'Đơn hàng đã được hủy.'
            );
        } catch (CommerceV2ClientException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}
