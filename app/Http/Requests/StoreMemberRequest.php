<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = $this->route('member');
        $memberId = $member instanceof Member ? $member->getKey() : null;
        $lastName = is_string($this->input('last_name')) ? $this->input('last_name') : '';

        return [
            'first_name' => [
                'bail', 'required', 'string', 'max:255',
                Rule::unique('members')->where(fn (Builder $query): Builder => $query->where('last_name', $lastName))->ignore($memberId),
            ],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'email', 'max:255', Rule::unique('members', 'email')->ignore($memberId)],
            'phone_country' => ['bail', 'required', 'string', Rule::in(array_keys(config('phone.countries')))],
            'phone' => ['bail', 'required', 'string', 'max:50', 'regex:/^\+[1-9]\d{4,14}$/', Rule::unique('members', 'phone')->ignore($memberId)],
            'address' => ['nullable', 'string', 'max:1000'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'membership_category_id' => ['required', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'exists:membership_levels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.unique' => 'A member with this full name already exists.',
            'email.unique' => 'This email address is already registered to another member.',
            'phone.unique' => 'This phone number is already registered to another member.',
            'phone.regex' => 'Enter a valid phone number for the selected country.',
            'phone_country.in' => 'Choose a country from the calling-code list.',
        ];
    }

    public function attributes(): array
    {
        return ['phone_country' => 'phone country', 'street_name' => 'barangay, village, or district', 'zip_code' => 'ZIP code'];
    }

    protected function prepareForValidation(): void
    {
        $country = $this->input('phone_country', config('phone.default_country'));
        $country = is_string($country) ? strtoupper($country) : $country;
        $values = ['phone_country' => $country];
        $countries = config('phone.countries');
        $countryDetails = is_string($country) ? ($countries[$country] ?? null) : null;

        if (is_string($this->input('phone')) && $countryDetails !== null) {
            $values['phone'] = $this->normalizePhoneNumber($this->input('phone'), $countryDetails);
        }

        $addressFields = ['street_name', 'city', 'state', 'zip_code'];

        if ($this->hasAny($addressFields)) {
            $parts = [];

            foreach ($addressFields as $field) {
                $value = $this->input($field);
                $value = is_string($value) ? trim($value) : $value;
                $values[$field] = $value === '' ? null : $value;

                if (is_string($values[$field]) && $values[$field] !== '') {
                    $parts[] = $values[$field];
                }
            }

            $values['address'] = $parts === [] ? null : implode(', ', $parts);
        }

        $this->merge($values);
    }

    /** @param array{name: string, dial_code: string, national_prefix: string, national_length: int} $country */
    protected function normalizePhoneNumber(string $phone, array $country): string
    {
        $phone = trim($phone);

        if (! preg_match('/^\+?[0-9\s().-]+$/D', $phone)) {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($phone, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        $prefix = $country['national_prefix'];

        if ($prefix !== '' && str_starts_with($digits, $prefix) && ($prefix === '0' || strlen($digits) > $country['national_length'])) {
            $digits = substr($digits, strlen($prefix));
        }

        return '+'.$country['dial_code'].$digits;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['phone', 'phone_country'])) {
                return;
            }

            $country = config('phone.countries')[$this->input('phone_country')];
            $phone = $this->string('phone')->toString();

            if (! str_starts_with($phone, '+'.$country['dial_code'])) {
                $validator->errors()->add('phone', 'The phone number must match the selected country calling code.');

                return;
            }

            if ($this->input('phone_country') === 'PH') {
                $nationalNumber = substr($phone, 3);
                $duplicates = Member::whereIn('phone', ['0'.$nationalNumber, $nationalNumber]);
                $member = $this->route('member');

                if ($member instanceof Member) {
                    $duplicates->where('id', '!=', $member->getKey());
                }

                if ($duplicates->exists()) {
                    $validator->errors()->add('phone', $this->messages()['phone.unique']);
                }
            }
        }];
    }
}
