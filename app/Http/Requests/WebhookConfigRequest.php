<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WebhookConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'url' => [$isUpdate ? 'sometimes' : 'required', 'url', 'regex:/^https:\/\//i'],
            'secret_key' => [$isUpdate ? 'sometimes' : 'required', 'string', 'min:16', 'max:255'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Webhook name is required.',
            'name.string' => 'Webhook name must be a string.',
            'name.max' => 'Webhook name must not exceed 255 characters.',
            'url.required' => 'Webhook URL is required.',
            'url.url' => 'Webhook URL must be a valid URL.',
            'url.regex' => 'Webhook URL must use HTTPS protocol for security.',
            'secret_key.required' => 'Secret key is required for webhook signature verification.',
            'secret_key.string' => 'Secret key must be a string.',
            'secret_key.min' => 'Secret key must be at least 16 characters for security.',
            'secret_key.max' => 'Secret key must not exceed 255 characters.',
            'headers.array' => 'Headers must be an array.',
            'headers.*.string' => 'Each header value must be a string.',
            'retry_attempts.integer' => 'Retry attempts must be an integer.',
            'retry_attempts.min' => 'Retry attempts must be at least 0.',
            'retry_attempts.max' => 'Retry attempts must not exceed 10.',
            'timeout.integer' => 'Timeout must be an integer.',
            'timeout.min' => 'Timeout must be at least 5 seconds.',
            'timeout.max' => 'Timeout must not exceed 120 seconds.',
            'is_active.boolean' => 'Is active must be a boolean value.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $validator->errors()
            ]
        ], 400));
    }
}
