<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'status' => ['required', Rule::in(['Pending', 'Active', 'Inactive'])]];
    }
}
