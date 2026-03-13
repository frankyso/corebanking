<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepositProductResource;
use App\Http\Resources\Api\V1\LoanProductResource;
use App\Http\Resources\Api\V1\SavingsProductResource;
use App\Models\DepositProduct;
use App\Models\LoanProduct;
use App\Models\SavingsProduct;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * List all active savings products.
     */
    public function savings(): AnonymousResourceCollection
    {
        return SavingsProductResource::collection(SavingsProduct::active()->get());
    }

    /**
     * List all active deposit products with their rates.
     */
    public function deposits(): AnonymousResourceCollection
    {
        return DepositProductResource::collection(DepositProduct::active()->with('rates')->get());
    }

    /**
     * List all active loan products.
     */
    public function loans(): AnonymousResourceCollection
    {
        return LoanProductResource::collection(LoanProduct::active()->get());
    }
}
