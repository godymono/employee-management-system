<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'max:50', 'unique:employees,employee_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'department' => ['required', 'string', 'max:100'],
            'branch' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'hire_date' => ['required', 'date'],
            'salary' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
