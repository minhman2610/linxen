<?php

namespace App\Http\Controllers\CommerceV2;

use App\Exceptions\CommerceV2\CommerceV2ClientException;
use App\Http\Controllers\Controller;
use App\Services\CommerceV2\CommerceV2Presenter;
use App\Services\CommerceV2\ErpCommerceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PdpSizeAdviceController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        ErpCommerceClient $client,
        CommerceV2Presenter $presenter
    ): JsonResponse {
        $validator = Validator::make(
            $request->all(),
            [
                'height_cm' => ['nullable', 'numeric', 'between:130,200'],
                'weight_kg' => ['nullable', 'numeric', 'between:30,150'],
                'bust_cm' => ['nullable', 'numeric', 'between:45,160'],
                'waist_cm' => ['nullable', 'numeric', 'between:45,160'],
                'hip_cm' => ['nullable', 'numeric', 'between:45,180'],
                'fit_preference' => [
                    'nullable',
                    'in:fitted,regular,relaxed',
                ],
                'color_id' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Thông tin số đo chưa hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reference = $presenter->normalizeProductReference(
            $slug
        );
        try {
            $result = $client->productSizeAdvice(
                $reference,
                $validator->validated()
            );

            return response()->json([
                'ok' => true,
                'data' => (array) data_get(
                    $result,
                    'data',
                    []
                ),
            ]);
        } catch (CommerceV2ClientException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'code' => $e->errorCode,
                ],
            ], $e->httpStatus);
        }
    }
}
