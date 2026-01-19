<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AttendanceQueryRequest extends FormRequest
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
        $rules = [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'employee_id' => ['nullable', 'integer'],
            'device_sn' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];

        // For employee-specific endpoint, dates are optional
        if ($this->route()->getName() === 'hr.employees.attendances') {
            $rules['start_date'] = ['nullable', 'date'];
            $rules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.date_format' => 'Start date must be in Y-m-d format (e.g., 2025-11-15).',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.date_format' => 'End date must be in Y-m-d format (e.g., 2025-11-15).',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'employee_id.string' => 'Employee ID must be a string.',
            'employee_id.max' => 'Employee ID must not exceed 255 characters.',
            'limit.integer' => 'Limit must be an integer.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit must not exceed 1000.',
            'offset.integer' => 'Offset must be an integer.',
            'offset.min' => 'Offset must be at least 0.',
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
