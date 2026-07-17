<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceV2Presenter;
use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DiscoverController extends Controller
{
    public function __construct(
        protected ErpCommerceClient $client,
        protected CommerceV2Presenter $presenter
    ) {
    }

    public function index(Request $request): View
    {
        $feed = trim((string) $request->query(
            'feed',
            'de-xuat'
        ));
        $cursor = $request->query('cursor');

        try {
            $rules = (array) data_get(
                $this->client->discoverRules(),
                'data.items',
                []
            );
            $result = (array) data_get(
                $this->client->discover(
                    $feed,
                    12,
                    is_string($cursor) ? $cursor : null
                ),
                'data',
                []
            );

            $result['items'] = collect(
                (array) data_get($result, 'items', [])
            )
                ->map(fn ($product) => $this->presenter
                    ->productSummary((array) $product))
                ->values()
                ->all();

            return view('commerce_v2.pages.discover', [
                'rules' => $rules,
                'result' => $result,
                'activeFeed' => $feed,
                'pageTitle' => data_get(
                    $result,
                    'feed.name',
                    'Khám phá'
                ) . ' — LIN XÉN',
                'pageDescription' => 'Khám phá thiết kế LIN XÉN từ dữ liệu Commerce.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('commerce_v2.pages.discover', [
                'rules' => [],
                'result' => [
                    'items' => [],
                    'pagination' => [],
                ],
                'activeFeed' => $feed,
                'discoverError' => $e instanceof
                    CommerceV2ClientException
                        ? $e->getMessage()
                        : 'Discover đang được cập nhật.',
                'pageTitle' => 'Khám phá — LIN XÉN',
            ]);
        }
    }
}
