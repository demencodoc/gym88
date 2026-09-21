<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CheckIn\CheckInDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckIn\CheckInRequest;
use App\Http\Resources\CheckInResource;
use App\Http\Resources\UserResource;
use App\Models\CheckIn;
use App\Services\CheckInService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CheckInController extends Controller
{
    public function __construct(
        protected CheckInService $checkInService
    ) {}

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $dto = CheckInDTO::fromRequest($request);
        $result = $this->checkInService->checkIn($dto, $request->user());

        return response()->json([
            'message' => 'Check-in thành công. Chúc bạn có buổi tập hiệu quả!',
            'check_in' => new CheckInResource($result['check_in']),
            'member' => new UserResource($result['member']),
            'package_name' => $result['package']->name,
            'days_remaining' => $result['days_remaining'],
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = ($user->isStaff() && $request->filled('user_id'))
            ? (int) $request->input('user_id')
            : $user->id;

        $checkIn = $this->checkInService->checkOut($userId);

        return response()->json([
            'message' => 'Check-out thành công. Hẹn gặp lại bạn!',
            'check_in' => new CheckInResource($checkIn),
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $history = $this->checkInService->getHistory($request->user()->id, 15);

        return CheckInResource::collection($history);
    }

    public function today(Request $request): AnonymousResourceCollection
    {
        $todayCheckIns = CheckIn::with(['user.avatar', 'subscription.package'])
            ->whereDate('check_in_time', Carbon::today())
            ->latest('check_in_time')
            ->paginate(20);

        return CheckInResource::collection($todayCheckIns);
    }
}
