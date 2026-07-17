<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\AttributionSessionService;
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
use App\Services\CommerceV2\ErpCommerceClient;
use App\Services\CommerceV2\OrderAccessSessionService;
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
        protected SessionCartService $cart,
        protected OrderAccessSessionService $access
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.checkout.index');
        }

        $quoteId = $this->quoteSession->id(
            $request->session()
        );

        if ($quoteId === '') {
            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with(
                    'error',
                    'Thanh toán một trang chưa có quote nội bộ hợp lệ.'
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

            $this->access->grant(
                $request->session(),
                $orderId
            );
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
                    'commerce.v2.orders.success',
                    ['order' => $orderId]
                )
                ->with('success', 'Đặt hàng thành công.');
        } catch (CommerceV2ClientException $e) {
            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with(
                    'error',
                    'Không thể ghi nhận đơn hàng lúc này.'
                );
        }
    }

    public function index(Request $request): View
    {
        try {
            if (
                $this->customer->verified(
                    $request->session()
                )
            ) {
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
                    'verifiedHistory' => true,
                    'pageTitle' => 'Đơn hàng — LIN XÉN',
                    'pageDescription' =>
                        'Danh sách đơn hàng của anh.',
                ]);
            }

            $items = [];

            if (
                $this->customer->authenticated(
                    $request->session()
                )
            ) {
                foreach (
                    $this->access->ids(
                        $request->session()
                    ) as $orderId
                ) {
                    try {
                        $items[] = (array) data_get(
                            $this->client->order(
                                $this->customer->token(
                                    $request->session()
                                ),
                                $orderId
                            ),
                            'data',
                            []
                        );
                    } catch (Throwable) {
                    }
                }
            }

            return view('commerce_v2.pages.orders', [
                'orders' => [
                    'items' => $items,
                    'returned' => count($items),
                ],
                'verifiedHistory' => false,
                'guestHistoryNotice' => true,
                'pageTitle' => 'Đơn hàng — LIN XÉN',
                'pageDescription' =>
                    'Các đơn được tạo trong phiên trình duyệt hiện tại.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('commerce_v2.pages.orders', [
                'orders' => ['items' => []],
                'verifiedHistory' => false,
                'guestHistoryNotice' => true,
                'pageTitle' => 'Đơn hàng — LIN XÉN',
            ]);
        }
    }

    public function success(
        Request $request,
        string $order
    ): View|RedirectResponse {
        if (! $this->canAccess($request, $order)) {
            return redirect()
                ->route('commerce.v2.orders.index')
                ->with(
                    'error',
                    'Phiên hiện tại không có quyền xem biên nhận này.'
                );
        }

        try {
            $result = $this->readOrder(
                $request,
                $order
            );

            return view(
                'commerce_v2.pages.order_success',
                [
                    'order' => $result,
                    'pageTitle' => 'Đặt hàng thành công — LIN XÉN',
                    'pageDescription' =>
                        'LIN XÉN đã tiếp nhận đơn hàng.',
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.orders.index')
                ->with(
                    'error',
                    'Không thể tải biên nhận đơn hàng.'
                );
        }
    }

    public function show(
        Request $request,
        string $order
    ): View|RedirectResponse {
        if (! $this->canAccess($request, $order)) {
            return redirect()
                ->route('commerce.v2.orders.index')
                ->with(
                    'error',
                    'Anh cần xác minh số điện thoại để xem đơn này.'
                );
        }

        try {
            $result = $this->readOrder(
                $request,
                $order
            );

            return view('commerce_v2.pages.order', [
                'order' => $result,
                'pageTitle' => data_get(
                    $result,
                    'order_code',
                    'Đơn hàng'
                ) . ' — LIN XÉN',
                'pageDescription' =>
                    'Chi tiết đơn hàng LIN XÉN.',
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
        if (! $this->canAccess($request, $order)) {
            return redirect()
                ->route('commerce.v2.orders.index')
                ->with(
                    'error',
                    'Phiên hiện tại không có quyền hủy đơn này.'
                );
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

    protected function canAccess(
        Request $request,
        string $order
    ): bool {
        if (
            ! $this->customer->authenticated(
                $request->session()
            )
        ) {
            return false;
        }

        return (
            $this->customer->verified(
                $request->session()
            )
            || $this->access->allows(
                $request->session(),
                $order
            )
        );
    }

    protected function readOrder(
        Request $request,
        string $order
    ): array {
        return (array) data_get(
            $this->client->order(
                $this->customer->token(
                    $request->session()
                ),
                $order
            ),
            'data',
            []
        );
    }
}
