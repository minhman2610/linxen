<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\SessionCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        protected SessionCartService $cart
    ) {
    }

    public function index(Request $request): View
    {
        try {
            $cart = $this->cart->validated(
                $request->session()
            );

            return view('commerce_v2.pages.cart', [
                'cart' => $cart,
                'pageTitle' => 'Giỏ hàng — LIN XÉN',
                'pageDescription' => 'Giá và tồn được kiểm tra lại từ ERP.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('commerce_v2.pages.cart', [
                'cart' => ['items' => [], 'summary' => []],
                'cartError' => 'Không thể kiểm tra giỏ hàng lúc này.',
                'pageTitle' => 'Giỏ hàng — LIN XÉN',
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sellable_sku_id' => [
                'required',
                'string',
                'regex:/^sku_\d+$/',
            ],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->cart->add(
                $request->session(),
                $data['sellable_sku_id'],
                (int) ($data['quantity'] ?? 1)
            );

            return redirect()
                ->route('commerce.v2.cart.index')
                ->with('success', 'Đã thêm sản phẩm vào giỏ.');
        } catch (CommerceV2ClientException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(
        Request $request,
        string $sellableSkuId
    ): RedirectResponse {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $this->cart->update(
                $request->session(),
                $sellableSkuId,
                (int) $data['quantity']
            );

            return back()->with('success', 'Đã cập nhật giỏ hàng.');
        } catch (CommerceV2ClientException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(
        Request $request,
        string $sellableSkuId
    ): RedirectResponse {
        $this->cart->remove(
            $request->session(),
            $sellableSkuId
        );

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cart->clear($request->session());

        return back()->with('success', 'Giỏ hàng đã được làm trống.');
    }
}
