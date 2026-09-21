<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipPackageResource;
use App\Models\MembershipPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipPackageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $packages = MembershipPackage::where('is_active', true)
            ->orderBy('price')
            ->get();

        return MembershipPackageResource::collection($packages);
    }

    public function show(int $id): JsonResponse
    {
        $package = MembershipPackage::findOrFail($id);

        return response()->json([
            'package' => new MembershipPackageResource($package),
        ]);
    }
}
