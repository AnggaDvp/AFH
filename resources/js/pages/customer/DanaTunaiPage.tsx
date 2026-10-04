import { router } from '@inertiajs/react';
import { useState } from 'react';
import CustomerPageHeader from '@/components/CustomerPageHeader';
import CustomerLayout from '@/layouts/CustomerLayout';
import type { CustomerPageProps } from '@/types/customer';

type MotorBrand = 'kawasaki' | 'honda' | 'yamaha';
type StnkNameType = 'self_name' | 'different_name';
type HouseOwnership = 'owned' | 'family_owned' | 'rented';

type Props = CustomerPageProps & {
    submissionKey: string;
    submissionError: string | null;
};

const motorBrands: { value: MotorBrand; label: string }[] = [
    { value: 'kawasaki', label: 'Kawasaki' },
    { value: 'honda', label: 'Honda' },
    { value: 'yamaha', label: 'Yamaha' },
];

const stnkOptions: { value: StnkNameType; label: string }[] = [
    { value: 'self_name', label: 'Atas Nama Sendiri' },
    { value: 'different_name', label: 'Beda Nama' },
];

const houseOptions: { value: HouseOwnership; label: string }[] = [
    { value: 'owned', label: 'Milik Sendiri' },
    { value: 'family_owned', label: 'Milik Keluarga' },
    { value: 'rented', label: 'Kontrak' },
];

export default function DanaTunaiPage(props: Props) {
    const [nopol, setNopol] = useState('');
    const [motorBrand, setMotorBrand] = useState<MotorBrand | ''>('');
    const [stnkNameType, setStnkNameType] = useState<StnkNameType | ''>('');
    const [houseOwnership, setHouseOwnership] = useState<HouseOwnership | ''>('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [error, setError] = useState('');

    const canSubmit = Boolean(nopol.trim() && motorBrand && stnkNameType && houseOwnership);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!canSubmit || processing) return;

        setError('');
        setErrors({});
        router.post('/customer/dana-tunai', {
            submission_key: props.submissionKey,
            nopol,
            motor_brand: motorBrand,
            stnk_name_type: stnkNameType,
            house_ownership: houseOwnership,
        }, {
            onStart: () => setProcessing(true),
            onError: (validationErrors) => {
                setErrors(validationErrors);
                setError('Data belum lengkap. Silakan periksa kembali.');
            },
            onFinish: () => setProcessing(false),
        });
    };

    const choiceClass = (selected: boolean) => [
        'flex min-h-12 cursor-pointer items-center rounded-xl border px-4 py-3 text-sm font-medium transition-colors duration-300',
        'focus-within:ring-4 focus-within:ring-red-100',
        selected
            ? 'border-brand bg-brand-subtle text-brand-dark ring-1 ring-brand'
            : 'border-slate-300 bg-white text-slate-700 hover:border-red-300 hover:bg-slate-50',
        processing ? 'cursor-not-allowed opacity-70' : '',
    ].join(' ');

    return (
        <CustomerLayout {...props}>
            <section className="mx-auto max-w-4xl">
                <CustomerPageHeader
                    eyebrow="Informasi Layanan"
                    title="Dana Tunai / Gadai BPKB"
                    description="Ajukan informasi awal kendaraan Anda untuk melanjutkan konsultasi dengan Agency Finance Honda."
                />
                <p className="mt-3 text-lg font-semibold text-slate-800">
                    Ajukan Dana Tunai dengan Jaminan BPKB Motor
                </p>

                <form onSubmit={submit} aria-busy={processing} className="customer-card mt-7 p-5 sm:p-8">
                    <div className="border-b border-slate-100 pb-5">
                        <h2 className="text-xl font-bold text-slate-950">Ajukan Dana Tunai</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Informasi ini merupakan pengajuan awal untuk konsultasi, bukan persetujuan atau pencairan.
                        </p>
                    </div>

                    {(error || props.submissionError) && (
                        <p role="alert" className="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">
                            {error || props.submissionError}
                        </p>
                    )}

                    <div className="mt-6 grid gap-7 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <label htmlFor="nopol" className="block text-sm font-semibold text-slate-800">
                                Nomor Polisi
                            </label>
                            <input
                                id="nopol"
                                name="nopol"
                                type="text"
                                value={nopol}
                                onChange={(event) => setNopol(event.target.value)}
                                placeholder="Contoh: B 1234 ABC"
                                maxLength={20}
                                required
                                disabled={processing}
                                aria-invalid={Boolean(errors.nopol)}
                                aria-describedby={errors.nopol ? 'nopol-error' : undefined}
                                className="customer-input mt-2 uppercase"
                            />
                            {errors.nopol && <p id="nopol-error" className="mt-1.5 text-sm text-red-700">{errors.nopol}</p>}
                        </div>

                        <fieldset>
                            <legend className="text-sm font-semibold text-slate-800">Tipe Motor</legend>
                            <div className="mt-2 grid gap-2">
                                {motorBrands.map((option) => (
                                    <label key={option.value} className={choiceClass(motorBrand === option.value)}>
                                        <input
                                            type="radio"
                                            name="motor_brand"
                                            value={option.value}
                                            checked={motorBrand === option.value}
                                            onChange={() => setMotorBrand(option.value)}
                                            disabled={processing}
                                            className="mr-3 h-4 w-4 shrink-0 accent-red-700"
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </div>
                            {errors.motor_brand && <p className="mt-1.5 text-sm text-red-700">{errors.motor_brand}</p>}
                        </fieldset>

                        <fieldset>
                            <legend className="text-sm font-semibold text-slate-800">Nama STNK</legend>
                            <div className="mt-2 grid gap-2">
                                {stnkOptions.map((option) => (
                                    <label key={option.value} className={choiceClass(stnkNameType === option.value)}>
                                        <input
                                            type="radio"
                                            name="stnk_name_type"
                                            value={option.value}
                                            checked={stnkNameType === option.value}
                                            onChange={() => setStnkNameType(option.value)}
                                            disabled={processing}
                                            className="mr-3 h-4 w-4 shrink-0 accent-red-700"
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </div>
                            {errors.stnk_name_type && <p className="mt-1.5 text-sm text-red-700">{errors.stnk_name_type}</p>}
                        </fieldset>

                        <fieldset className="sm:col-span-2">
                            <legend className="text-sm font-semibold text-slate-800">Status Kepemilikan Rumah</legend>
                            <div className="mt-2 grid gap-2 sm:grid-cols-3">
                                {houseOptions.map((option) => (
                                    <label key={option.value} className={choiceClass(houseOwnership === option.value)}>
                                        <input
                                            type="radio"
                                            name="house_ownership"
                                            value={option.value}
                                            checked={houseOwnership === option.value}
                                            onChange={() => setHouseOwnership(option.value)}
                                            disabled={processing}
                                            className="mr-3 h-4 w-4 shrink-0 accent-red-700"
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </div>
                            {errors.house_ownership && <p className="mt-1.5 text-sm text-red-700">{errors.house_ownership}</p>}
                        </fieldset>
                    </div>

                    <button
                        type="submit"
                        disabled={!canSubmit || processing}
                        className="customer-button-primary mt-7 w-full sm:w-auto"
                    >
                        {processing ? 'Memproses...' : 'Ajukan Dana Tunai'}
                    </button>
                </form>
            </section>
        </CustomerLayout>
    );
}
