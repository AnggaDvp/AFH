<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use App\Models\Village;
use App\Services\AdminNotificationService;
use App\Services\WhatsAppReminderService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;

class CustomerAccessController extends Controller
{
    private const DUPLICATE_PHONE_MESSAGE = 'Nomor telepon sudah terdaftar. Silakan pilih "Sudah Pernah Pengajuan".';

    private const UNKNOWN_PHONE_MESSAGE = 'Nomor telepon belum terdaftar. Silakan pilih "Belum Pernah Pengajuan" untuk membuat data baru.';

    private const INVALID_ADDRESS_MESSAGE = 'Data wilayah yang dipilih tidak sesuai.';

    public function register(
        Request $request,
        AdminNotificationService $notifications,
        WhatsAppReminderService $reminders
    )
    {
        $input = $request->all();
        $input['telepon'] = $this->normalizePhone((string) ($input['telepon'] ?? ''));

        $validator = Validator::make($input, [
            'nama' => ['required', 'string', 'min:2', 'max:150'],
            'telepon' => ['required', 'string', 'regex:/^08[0-9]{7,13}$/', Rule::unique('customers', 'phone')],
            'wilayah' => ['required', 'integer'],
            'kecamatan' => ['required', 'integer'],
            'kelurahan' => ['required', 'integer'],
            'kodePos' => ['nullable', 'digits:5'],
            'namaJalan' => ['required', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.min' => 'Nama lengkap minimal 2 karakter.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.regex' => 'Gunakan nomor telepon Indonesia, contoh 081315379958.',
            'telepon.unique' => self::DUPLICATE_PHONE_MESSAGE,
            'wilayah.required' => 'Pilih wilayah.',
            'kecamatan.required' => 'Pilih kecamatan.',
            'kelurahan.required' => 'Pilih kelurahan atau desa.',
            'namaJalan.required' => 'Nama jalan wajib diisi.',
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($input): void {
            foreach (['wilayah', 'kecamatan', 'kelurahan'] as $field) {
                if ($validator->errors()->has($field) || !is_numeric($input[$field] ?? null)) {
                    return;
                }
            }

            $region = Region::find((int) $input['wilayah']);
            $district = District::find((int) $input['kecamatan']);
            $village = Village::find((int) $input['kelurahan']);

            if (
                !$region
                || !$district
                || !$village
                || $district->region_id !== $region->id
                || $village->district_id !== $district->id
            ) {
                $validator->errors()->add('wilayah', self::INVALID_ADDRESS_MESSAGE);
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Periksa kembali data yang Anda isi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $village = Village::findOrFail((int) $input['kelurahan']);

        try {
            $customer = Customer::create([
                'full_name' => trim($input['nama']),
                'phone' => $input['telepon'],
                'region_id' => (int) $input['wilayah'],
                'district_id' => (int) $input['kecamatan'],
                'village_id' => $village->id,
                'postal_code' => $village->postal_code,
                'street_address' => trim($input['namaJalan']),
            ]);
        } catch (QueryException $exception) {
            if (Customer::where('phone', $input['telepon'])->exists()) {
                return response()->json([
                    'message' => self::DUPLICATE_PHONE_MESSAGE,
                    'errors' => ['telepon' => [self::DUPLICATE_PHONE_MESSAGE]],
                ], 422);
            }

            throw $exception;
        }

        $request->session()->regenerate();
        $request->session()->put('customer_id', $customer->id);
        $notifications->notifyActiveAdmins(
            'new_customer',
            'Customer baru terdaftar',
            $customer->full_name . ' (' . $customer->phone . ') baru membuat akun.',
            [
                'customer_id' => $customer->id,
                'url' => '/admin/customers/' . $customer->id,
            ]
        );
        $reminders->queueCustomerRegistration($customer);

        return redirect()->route('customer.show');
    }

    public function identify(Request $request)
    {
        $phone = $this->normalizePhone((string) $request->input('telepon', ''));
        $validator = Validator::make(['telepon' => $phone], [
            'telepon' => ['required', 'string', 'regex:/^08[0-9]{7,13}$/'],
        ], [
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.regex' => 'Gunakan nomor telepon Indonesia, contoh 081315379958.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Periksa kembali nomor telepon Anda.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('phone', $phone)->first();

        if (!$customer) {
            return response()->json([
                'message' => self::UNKNOWN_PHONE_MESSAGE,
                'errors' => ['telepon' => [self::UNKNOWN_PHONE_MESSAGE]],
            ], 422);
        }

        $request->session()->regenerate();
        $request->session()->put('customer_id', $customer->id);

        return redirect()->route('customer.show');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        if (str_starts_with($digits, '8')) {
            return '0' . $digits;
        }

        return $digits;
    }
}
