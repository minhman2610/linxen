<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CheckoutQuoteSessionService;
use App\Services\CommerceV2\CustomerSessionService;
use App\Services\CommerceV2\ErpCommerceClient;
use App\Services\CommerceV2\SessionCartService;
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
        protected ErpCommerceClient $client
    ) {
    }

    public function index(
        Request $request
    ): View|RedirectResponse {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index')
                ->with(
                    'error',
                    'Anh cần đăng nhập bằng magic link trước khi checkout.'
                );
        }

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
                        'Giỏ hàng chưa sẵn sàng để tạo báo giá.'
                    );
            }

            $account = $this->customer->account(
                $request->session()
            );

            return view('commerce_v2.pages.checkout', [
                'cart' => $cart,
                'account' => $account,
                'pageTitle' => 'Giao hàng — LIN XÉN',
                'pageDescription' => 'Chọn địa chỉ để ERP tạo báo giá có thời hạn.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.cart.index')
                ->with(
                    'error',
                    'Không thể chuẩn bị checkout lúc này.'
                );
        }
    }

    public function createQuote(
        Request $request
    ): RedirectResponse {
        if (! $this->customer->authenticated(
            $request->session()
        )) {
            return redirect()
                ->route('commerce.v2.account.index')
                ->with('error', 'Phiên customer chưa đăng nhập.');
        }

        $data = $request->validate([
            'shipping_address_id' => [
                'required',
                'string',
                'regex:/^address_\d+$/',
            ],
            'shipping_method' => [
                'required',
                'string',
                'in:standard',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:cod',
            ],
        ]);

        $items = $this->cart->raw(
            $request->session()
        );

        if ($items === []) {
            return redirect()
                ->route('commerce.v2.cart.index')
                ->with('error', 'Giỏ hàng đang trống.');
        }

        try {
            $result = $this->client->createCheckoutQuote(
                $this->customer->token($request->session()),
                $items,
                $data['shipping_address_id'],
                $data['shipping_method'],
                $data['payment_method'],
                $this->attribution->payload(
                    $request->session()
                )
            );
            $quoteId = trim((string) data_get(
                $result,
                'data.quote_id'
            ));

            if ($quoteId === '') {
                throw new \RuntimeException(
                    'ERP không trả quote_id.'
                );
            }

            $this->quoteSession->put(
                $request->session(),
                $quoteId
            );

            return redirect()
                ->route('commerce.v2.checkout.confirm')
                ->with(
                    'success',
                    'ERP đã tạo báo giá và kiểm tra lại giá, tồn kho.'
                );
        } catch (CommerceV2ClientException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Không thể tạo báo giá checkout.'
                );
        }
    }

    public function confirm(
        Request $request
    ): View|RedirectResponse {
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
                    'Chưa có báo giá. Vui lòng tạo báo giá mới.'
                );
        }

        try {
            $quote = (array) data_get(
                $this->client->checkoutQuote(
                    $this->customer->token(
                        $request->session()
                    ),
                    $quoteId
                ),
                'data',
                []
            );

            return view(
                'commerce_v2.pages.checkout_confirm',
                [
                    'quote' => $quote,
                    'pageTitle' => 'Xác nhận báo giá — LIN XÉN',
                    'pageDescription' => 'Báo giá được ERP revalidate trước khi hiển thị.',
                ]
            );
        } catch (CommerceV2ClientException $e) {
            $this->quoteSession->forget(
                $request->session()
            );

            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with(
                    'error',
                    $e->httpStatus === 409
                        ? 'Báo giá đã thay đổi hoặc hết hạn. Vui lòng tạo lại.'
                        : $e->getMessage()
                );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.checkout.index')
                ->with(
                    'error',
                    'Không thể đọc báo giá checkout.'
                );
        }
    }

    public function requote(
        Request $request
    ): RedirectResponse {
        $this->quoteSession->forget(
            $request->session()
        );

        return redirect()
            ->route('commerce.v2.checkout.index');
    }
}
