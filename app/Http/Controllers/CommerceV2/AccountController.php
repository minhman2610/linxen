<?php

namespace App\Http\Controllers\CommerceV2;

use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CustomerSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AccountController extends Controller
{
    public function __construct(
        protected CustomerSessionService $customer
    ) {
    }

    public function exchange(
        Request $request,
        string $ticket
    ): RedirectResponse {
        try {
            $this->customer->exchange(
                $request->session(),
                $ticket
            );

            return redirect()
                ->route('commerce.v2.account.index')
                ->with('success', 'Đăng nhập thành công.')
                ->withHeaders([
                    'Cache-Control' => 'no-store, private',
                    'Referrer-Policy' => 'no-referrer',
                ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('commerce.v2.account.index')
                ->with('error', 'Magic link không hợp lệ hoặc đã hết hạn.');
        }
    }

    public function index(Request $request): View
    {
        try {
            $account = $this->customer->account(
                $request->session()
            );

            return view('commerce_v2.pages.account', [
                'account' => $account,
                'pageTitle' => 'Tài khoản — LIN XÉN',
                'pageDescription' => 'Thông tin khách hàng và địa chỉ nhận hàng.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('commerce_v2.pages.account', [
                'account' => [],
                'accountError' => 'Phiên đăng nhập không còn hợp lệ.',
                'pageTitle' => 'Tài khoản — LIN XÉN',
            ]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->customer->logout($request->session());

        return redirect()
            ->route('commerce.v2.home')
            ->with('success', 'Đã đăng xuất.');
    }
}
