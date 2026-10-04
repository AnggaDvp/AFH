<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;

class EnsureCustomerIdentified
{
    public function handle(Request $request, Closure $next)
    {
        $customerId = filter_var(
            $request->session()->get('customer_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($customerId === false) {
            $request->session()->forget('customer_id');

            return redirect('/');
        }

        $customer = Customer::find($customerId);

        if (!$customer) {
            $request->session()->forget('customer_id');

            return redirect('/');
        }

        $request->attributes->set('customer', $customer);

        return $next($request);
    }
}
