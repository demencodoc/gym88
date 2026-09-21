<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Membership\CreateSubscriptionDTO;
use App\DTOs\Membership\FreezeSubscriptionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\FreezeSubscriptionRequest;
use App\Http\Requests\Membership\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\TransactionResource;
use App\Models\MemberSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function current(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription?->load('package');

        if (! $subscription) {
            return response()->json([
                'subscription' => null,
                'message' => 'Bạn chưa đăng ký gói tập nào đang kích hoạt.',
            ]);
        }

        return response()->json([
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $subscriptions = MemberSubscription::with('package')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(10);

        return SubscriptionResource::collection($subscriptions);
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $dto = CreateSubscriptionDTO::fromRequest($request, $request->user()->id);
        $result = $this->subscriptionService->subscribe($dto);

        return response()->json([
            'message' => 'Đăng ký gói tập thành công.',
            'subscription' => new SubscriptionResource($result['subscription']),
            'transaction' => new TransactionResource($result['transaction']),
        ], 201);
    }

    public function freeze(FreezeSubscriptionRequest $request): JsonResponse
    {
        $dto = FreezeSubscriptionDTO::fromRequest($request, $request->validated('subscription_id'));
        $subscription = $this->subscriptionService->freeze($dto, $request->user()->id);

        return response()->json([
            'message' => 'Bảo lưu gói tập thành công.',
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }

    public function unfreeze(int $id, Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->unfreeze($id, $request->user()->id);

        return response()->json([
            'message' => 'Mở khóa gói tập thành công.',
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }

    public function dynamicQr(Request $request): JsonResponse
    {
        $qrData = $this->subscriptionService->getDynamicQr($request->user());

        return response()->json($qrData);
    }
}
