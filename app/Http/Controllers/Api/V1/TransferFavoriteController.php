<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transfer\StoreTransferFavoriteRequest;
use App\Http\Resources\Api\V1\TransferFavoriteResource;
use App\Models\MobileUser;
use App\Models\SavingsAccount;
use App\Models\TransferFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransferFavoriteController extends Controller
{
    /**
     * List all transfer favorites for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $favorites = $this->mobileUser($request)->transferFavorites()->with(['savingsAccount.customer'])->get();

        return TransferFavoriteResource::collection($favorites);
    }

    /**
     * Store a new transfer favorite.
     */
    public function store(StoreTransferFavoriteRequest $request): TransferFavoriteResource
    {
        $account = SavingsAccount::where('account_number', $request->input('account_number'))->firstOrFail();

        $favorite = $this->mobileUser($request)->transferFavorites()->create([
            'savings_account_id' => $account->id,
            'alias' => $request->input('alias'),
        ]);

        $favorite->load('savingsAccount.customer');

        return TransferFavoriteResource::make($favorite);
    }

    /**
     * Delete a transfer favorite.
     */
    public function destroy(Request $request, TransferFavorite $transferFavorite): JsonResponse
    {
        $mobileUser = $this->mobileUser($request);

        if ($transferFavorite->mobile_user_id !== $mobileUser->id) {
            abort(403);
        }

        $transferFavorite->delete();

        return response()->json(['message' => 'Favorit berhasil dihapus.']);
    }

    private function mobileUser(Request $request): MobileUser
    {
        /** @var MobileUser */
        return $request->user('mobile');
    }
}
