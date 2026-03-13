<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Customer $model */
        $model = $this->resource;

        /** @var CustomerType $customerType */
        $customerType = $model->customer_type;

        /** @var CustomerStatus $status */
        $status = $model->status;

        /** @var RiskRating $riskRating */
        $riskRating = $model->risk_rating;

        return [
            'cif_number' => $model->cif_number,
            'customer_type' => $customerType->value,
            'status' => $status->value,
            'display_name' => $model->display_name,
            'branch' => [
                'id' => $model->branch?->id,
                'name' => $model->branch?->name,
            ],
            'risk_rating' => $riskRating->value,
            'individual_detail' => $this->when(
                $customerType === CustomerType::Individual && $model->individualDetail !== null,
                fn () => IndividualDetailResource::make($model->individualDetail),
            ),
            'corporate_detail' => $this->when(
                $customerType === CustomerType::Corporate && $model->corporateDetail !== null,
                function () use ($model): array {
                    $corporate = $model->corporateDetail;
                    assert($corporate !== null);

                    return [
                        'company_name' => $corporate->company_name,
                        'legal_type' => $corporate->legal_type,
                        'nib' => $corporate->nib,
                        'npwp_company' => $corporate->npwp_company,
                        'business_sector' => $corporate->business_sector,
                        'address_company' => $corporate->address_company,
                        'city' => $corporate->city,
                        'province' => $corporate->province,
                        'contact_person_name' => $corporate->contact_person_name,
                        'contact_person_phone' => $corporate->contact_person_phone,
                        'contact_person_position' => $corporate->contact_person_position,
                    ];
                },
            ),
        ];
    }
}
