<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CashLoanRequestSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CashLoanRequestController extends Controller
{
    public function store(Request $request, CashLoanRequestSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'nopol' => ['required', 'string', 'max:20'],
            'motor_brand' => ['required', 'in:kawasaki,honda,yamaha'],
            'stnk_name_type' => ['required', 'in:self_name,different_name'],
            'house_ownership' => ['required', 'in:owned,family_owned,rented'],
        ]);

        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        try {
            [$cashLoanRequest] = $submission->submit($customer, $data);
        } catch (Throwable $exception) {
            Log::warning('Cash loan request could not be submitted.', [
                'customer_id' => $customer->id,
                'exception_type' => get_class($exception),
            ]);

            return back()->with('cash_loan_error', 'Data belum lengkap. Silakan periksa kembali.');
        }

        return redirect()->route('customer.chat.show', $cashLoanRequest->conversation_id);
    }
}
