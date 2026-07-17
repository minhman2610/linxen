<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\AttributionSessionService;
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
use App\Services\CommerceV2\ErpCommerceClient;
use App\Services\CommerceV2\OnePageCheckoutSessionService;
use App\Services\CommerceV2\OrderAccessSessionService;
use App\Services\CommerceV2\OrderIdempotencySessionService;
use App\Services\CommerceV2\SessionCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        protected SessionCartService $cart,
        protected CustomerSessionService $customer,
        protected CheckoutQuoteSessionService $quoteSession,
        protected AttributionSessionService $attribution,
        protected OrderIdempotencySessionService $idempotency,
        protected OnePageCheckoutSessionService $pipeline,
        protected OrderAccessSessionService $orderAccess,
        protected ErpCommerceClient $client
    ) {
    }

    public function index(
        Request $request
    ): View|RedirectResponse {
        try {
            $cart = $this->cart->validated(
                $request->session()
            );

            if (
                empty(data_get($cart, 'items'))
                || data_get($cart, 'summary.valid') !== true
            ) {
                return redirect()
                    ->route('commerce.v2.cart.index')
                    ->with(
                        'error',
                        'Giỏ hàng chưa sẵn sàng để thanh toán.'
                    );
            }

            $capabilities = (array) data_get(
                $this->client->checkoutCapabilities(),
                'data',
                []
            );
            $locations = (array) data_get(
                $this->client->checkoutLocations(),
                'data.items',
                []
            );
            $account = $this->customer->verified(
                $request->session()
            )
                ? $this->customer->account(
                    $request->session()
                )
                : [];
            $identity = $this->prefillIdentity(
                $request,
                $account,
                $this->customer->checkoutIdentity(
                    $request->session()
                )
            );

            return view('commerce_v2.pages.checkout', [
                'cart' => $cart,
                'account' => $account,
                'identity' => $identity,
                'locations' => $locations,
                'capabilities' => $capabilities,
                'isVerifiedCustomer' =>
                    $this->customer->verified(
                        $request->session()
                    ),
                'isGuestCustomer' =>
                    $this->customer->guest(
                        $request->session()
                    ),
                'pageTitle' => 'Thanh toán — LIN XÉN',
                'pageDescription' =>
                    'Thanh toán một trang, COD và tổng tiền do ERP xác nhận.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.cart.index')
                ->with(
                    'error',
                    'Không thể chuẩn bị trang thanh toán lúc này.'
                );
        }
    }

    public function placeOrder(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'receiver_name' => [
                'required',
                'string',
                'max:191',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
            ],
            'email' => [
                'nullable',
                'email',
                'max:191',
            ],
            'location_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'ward_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'street' => [
                'required',
                'string',
                'max:255',
            ],
            'shipping_method' => [
                'required',
                'in:standard',
            ],
            'payment_method' => [
                'required',
                'in:cod',
            ],
        ]);

        try {
            $capabilities = (array) data_get(
                $this->client->checkoutCapabilities(),
                'data',
                []
            );

            if (
                data_get(
                    $capabilities,
                    'one_page_checkout_enabled'
                ) !== true
            ) {
                throw new CommerceV2ClientException(
                    'Thanh toán một trang hiện chưa được bật.',
                    503,
                    'storefront_one_page_checkout_disabled'
                );
            }

            if (
                data_get(
                    $capabilities,
                    'order_accept_enabled'
                ) !== true
            ) {
                throw new CommerceV2ClientException(
                    'Website đang hoàn thiện bước nhận đơn. Anh vui lòng quay lại sau.',
                    503,
                    'commerce_order_accept_disabled'
                );
            }

            $items = $this->cart->raw(
                $request->session()
            );
            $validatedCart = $this->cart->validated(
                $request->session()
            );

            if (
                $items === []
                || data_get(
                    $validatedCart,
                    'summary.valid'
                ) !== true
            ) {
                return redirect()
                    ->route('commerce.v2.cart.index')
                    ->with(
                        'error',
                        'Giỏ hàng đã thay đổi. Vui lòng kiểm tra lại.'
                    );
            }

            $identityPayload = [
                'receiver_name' => trim(
                    (string) $data['receiver_name']
                ),
                'phone' => $this->normalizePhone(
                    (string) $data['phone']
                ),
                'receiver_phone' => $this->normalizePhone(
                    (string) $data['phone']
                ),
                'email' => isset($data['email'])
                    ? strtolower(trim((string) $data['email']))
                    : null,
                'location_id' => (int) $data['location_id'],
                'ward_id' => (int) $data['ward_id'],
                'street' => preg_replace(
                    '/\s+/u',
                    ' ',
                    trim((string) $data['street'])
                ),
            ];

            if (
                $identityPayload['phone'] === ''
                || $identityPayload['street'] === ''
            ) {
                throw new CommerceV2ClientException(
                    'Số điện thoại hoặc địa chỉ không hợp lệ.',
                    422,
                    'storefront_checkout_identity_invalid'
                );
            }

            $fingerprint = hash(
                'sha256',
                json_encode(
                    [
                        'items' => $items,
                        'identity' => $identityPayload,
                        'shipping_method' =>
                            $data['shipping_method'],
                        'payment_method' =>
                            $data['payment_method'],
                        'attribution' =>
                            $this->attribution->payload(
                                $request->session()
                            ),
                    ],
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                )
            );
            $state = $this->pipeline->start(
                $request->session(),
                $fingerprint
            );
            $existingOrderId = trim((string) data_get(
                $state,
                'order_id'
            ));

            if (
                $existingOrderId !== ''
                && $this->orderAccess->allows(
                    $request->session(),
                    $existingOrderId
                )
            ) {
                return redirect()->route(
                    'commerce.v2.orders.success',
                    ['order' => $existingOrderId]
                );
            }

            $address = $this->resolveCheckoutIdentity(
                $request,
                $identityPayload
            );
            $addressId = trim((string) data_get(
                $address,
                'id'
            ));

            if ($addressId === '') {
                throw new \RuntimeException(
                    'ERP không trả shipping address id.'
                );
            }

            $quoteId = trim((string) data_get(
                $state,
                'quote_id'
            ));

            if ($quoteId === '') {
                $quoteResult = $this->client
                    ->createCheckoutQuote(
                        $this->customer->token(
                            $request->session()
                        ),
                        $items,
                        $addressId,
                        $data['shipping_method'],
                        $data['payment_method'],
                        $this->attribution->payload(
                            $request->session()
                        )
                    );
                $quoteId = trim((string) data_get(
                    $quoteResult,
                    'data.quote_id'
                ));

                if ($quoteId === '') {
                    throw new \RuntimeException(
                        'ERP không trả quote id.'
                    );
                }

                $this->pipeline->putQuote(
                    $request->session(),
                    $quoteId
                );
                $this->quoteSession->put(
                    $request->session(),
                    $quoteId
                );
            }

            $idempotencyKey = $this->idempotency->key(
                $request->session(),
                $quoteId
            );
            $orderResult = (array) data_get(
                $this->client->commitOrder(
                    $this->customer->token(
                        $request->session()
                    ),
                    $quoteId,
                    $idempotencyKey,
                    $this->attribution->payload(
                        $request->session()
                    )
                ),
                'data',
                []
            );
            $orderId = trim((string) data_get(
                $orderResult,
                'order_id'
            ));

            if ($orderId === '') {
                throw new \RuntimeException(
                    'ERP không trả local order id.'
                );
            }

            $this->orderAccess->grant(
                $request->session(),
                $orderId
            );
            $this->pipeline->putOrder(
                $request->session(),
                $orderId
            );
            $this->idempotency->forget(
                $request->session(),
                $quoteId
            );
            $this->cart->clear($request->session());

            return redirect()
                ->route(
                    'commerce.v2.orders.success',
                    ['order' => $orderId]
                )
                ->with(
                    'success',
                    data_get(
                        $orderResult,
                        'idempotent_replay'
                    )
                        ? 'Đơn hàng đã được ghi nhận trước đó.'
                        : 'Đặt hàng thành công.'
                );
        } catch (CommerceV2ClientException $e) {
            if (
                in_array(
                    $e->errorCode,
                    [
                        'commerce_quote_changed',
                        'commerce_quote_expired',
                        'commerce_quote_not_committable',
                        'commerce_order_quote_not_committable',
                        'commerce_order_payment_mismatch',
                    ],
                    true
                )
                || data_get(
                    $e->details,
                    'requote_required'
                ) === true
            ) {
                $this->pipeline->clear(
                    $request->session()
                );
                $this->quoteSession->forget(
                    $request->session()
                );
            }

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Không thể ghi nhận đơn hàng lúc này.'
                );
        }
    }

    public function wards(
        int $location
    ): JsonResponse {
        try {
            return response()->json([
                'ok' => true,
                'items' => (array) data_get(
                    $this->client->checkoutWards(
                        $location
                    ),
                    'data.items',
                    []
                ),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'items' => [],
            ], 502);
        }
    }

    public function createQuote(): RedirectResponse
    {
        return redirect()
            ->route('commerce.v2.checkout.index')
            ->with(
                'error',
                'Báo giá đã được chuyển thành bước xử lý ẩn trong thanh toán một trang.'
            );
    }

    public function confirm(): RedirectResponse
    {
        return redirect()
            ->route('commerce.v2.checkout.index');
    }

    public function requote(
        Request $request
    ): RedirectResponse {
        $this->pipeline->clear(
            $request->session()
        );
        $this->quoteSession->forget(
            $request->session()
        );

        return redirect()
            ->route('commerce.v2.checkout.index');
    }

    protected function resolveCheckoutIdentity(
        Request $request,
        array $identityPayload
    ): array {
        if (
            $this->customer->verified(
                $request->session()
            )
        ) {
            $result = $this->client
                ->upsertCheckoutAddress(
                    $this->customer->token(
                        $request->session()
                    ),
                    $identityPayload
                );
            $identity = (array) data_get(
                $result,
                'data',
                []
            );
            $this->customer->replaceCheckoutIdentity(
                $request->session(),
                $identity
            );

            return (array) data_get(
                $identity,
                'shipping_address',
                []
            );
        }

        $existingIdentity = $this->customer
            ->checkoutIdentity($request->session());
        $existingPhone = $this->normalizePhone(
            (string) data_get(
                $existingIdentity,
                'customer.phone'
            )
        );

        if (
            $this->customer->guest($request->session())
            && $existingPhone === $identityPayload['phone']
        ) {
            $result = $this->client
                ->upsertCheckoutAddress(
                    $this->customer->token(
                        $request->session()
                    ),
                    $identityPayload
                );
            $identity = (array) data_get(
                $result,
                'data',
                []
            );
            $this->customer->replaceCheckoutIdentity(
                $request->session(),
                $identity
            );

            return (array) data_get(
                $identity,
                'shipping_address',
                []
            );
        }

        $identity = $this->customer
            ->beginGuestCheckout(
                $request->session(),
                $identityPayload
            );

        return (array) data_get(
            $identity,
            'shipping_address',
            []
        );
    }

    protected function prefillIdentity(
        Request $request,
        array $account,
        array $checkoutIdentity
    ): array {
        $customer = (array) data_get(
            $account,
            'customer',
            data_get($checkoutIdentity, 'customer', [])
        );
        $addresses = collect((array) data_get(
            $account,
            'addresses',
            []
        ));
        $address = (array) (
            $addresses->firstWhere('is_default', true)
            ?: $addresses->first()
            ?: data_get(
                $checkoutIdentity,
                'shipping_address',
                []
            )
            ?: []
        );

        return [
            'receiver_name' => old(
                'receiver_name',
                data_get($address, 'receiver_name', '')
            ),
            'phone' => old(
                'phone',
                data_get(
                    $customer,
                    'phone',
                    data_get($address, 'receiver_phone', '')
                )
            ),
            'email' => old(
                'email',
                data_get($customer, 'email', '')
            ),
            'location_id' => (int) old(
                'location_id',
                data_get($address, 'location_id', 0)
            ),
            'ward_id' => (int) old(
                'ward_id',
                data_get($address, 'ward_id', 0)
            ),
            'ward_name' => old(
                'ward_name',
                data_get($address, 'ward_name', '')
            ),
            'street' => old(
                'street',
                data_get($address, 'street', '')
            ),
        ];
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($phone, '84')) {
            $phone = '0' . substr($phone, 2);
        }

        return preg_match('/^0\d{8,10}$/', $phone)
            ? $phone
            : '';
    }
}
