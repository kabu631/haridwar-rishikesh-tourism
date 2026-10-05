<?php

namespace App\Http\Requests;

use App\Models\Enquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
{
    /**
     * Public form: anyone may submit.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(array_keys(Enquiry::TYPES))],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'tour' => ['nullable', 'string', 'max:190'],
            'page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'travel_date' => ['nullable', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:travel_date'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:99'],
            'children' => ['nullable', 'integer', 'min:0', 'max:99'],
            'message' => ['nullable', 'string', 'max:3000'],
            'source' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (digits, spaces, + and - only).',
            'travel_date.after_or_equal' => 'Please choose a travel date from today onwards.',
            'return_date.after_or_equal' => 'The return date must be after the travel date.',
        ];
    }

    /**
     * Forms on cached pages have no session to show errors, so a failed
     * non-AJAX submission is shown on the full booking form instead.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(
            redirect()->route('booking.create', array_filter(['tour' => $this->input('tour')]))
                ->withErrors($validator)
                ->withInput($this->except('website'))
        );
    }
}
