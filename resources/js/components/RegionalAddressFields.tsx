import { useEffect, useState } from 'react';

type LocationOption = {
    id: number;
    code: string;
    name: string;
    type?: string;
    postal_code?: string;
};

type AddressValue = {
    wilayah: string;
    kecamatan: string;
    kelurahan: string;
    kodePos: string;
};

type AddressErrors = Partial<Record<keyof AddressValue, string>>;
type DataLevel = 'regions' | 'districts' | 'villages';

type Props = {
    value: AddressValue;
    errors: AddressErrors;
    onChange: (value: AddressValue) => void;
};

const loadErrorMessage = 'Data wilayah gagal dimuat. Silakan coba lagi.';

async function fetchOptions(url: string, signal: AbortSignal): Promise<LocationOption[]> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        signal,
    });

    if (!response.ok) {
        throw new Error('Regional data request failed.');
    }

    const payload: unknown = await response.json();
    if (!Array.isArray(payload)) {
        throw new Error('Regional data response was invalid.');
    }

    return payload as LocationOption[];
}

function isAbortError(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

function parseLocationId(value: string): number | null {
    const id = Number(value);

    return Number.isSafeInteger(id) && id > 0 ? id : null;
}

export default function RegionalAddressFields({ value, errors, onChange }: Props) {
    const [regions, setRegions] = useState<LocationOption[]>([]);
    const [districts, setDistricts] = useState<LocationOption[]>([]);
    const [villages, setVillages] = useState<LocationOption[]>([]);
    const [loadingRegions, setLoadingRegions] = useState(true);
    const [loadingDistricts, setLoadingDistricts] = useState(false);
    const [loadingVillages, setLoadingVillages] = useState(false);
    const [regionError, setRegionError] = useState(false);
    const [districtError, setDistrictError] = useState(false);
    const [villageError, setVillageError] = useState(false);
    const [retry, setRetry] = useState<Record<DataLevel, number>>({
        regions: 0,
        districts: 0,
        villages: 0,
    });

    useEffect(() => {
        const controller = new AbortController();
        setLoadingRegions(true);
        setRegionError(false);

        fetchOptions('/api/regions', controller.signal)
            .then(setRegions)
            .catch((error: unknown) => {
                if (!isAbortError(error)) setRegionError(true);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingRegions(false);
            });

        return () => controller.abort();
    }, [retry.regions]);

    useEffect(() => {
        const regionId = parseLocationId(value.wilayah);
        if (regionId === null) {
            setDistricts([]);
            setLoadingDistricts(false);
            setDistrictError(false);
            return undefined;
        }

        const controller = new AbortController();
        setDistricts([]);
        setLoadingDistricts(true);
        setDistrictError(false);

        fetchOptions(`/api/regions/${regionId}/districts`, controller.signal)
            .then(setDistricts)
            .catch((error: unknown) => {
                if (!isAbortError(error)) setDistrictError(true);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingDistricts(false);
            });

        return () => controller.abort();
    }, [value.wilayah, retry.districts]);

    useEffect(() => {
        const districtId = parseLocationId(value.kecamatan);
        if (districtId === null) {
            setVillages([]);
            setLoadingVillages(false);
            setVillageError(false);
            return undefined;
        }

        const controller = new AbortController();
        setVillages([]);
        setLoadingVillages(true);
        setVillageError(false);

        fetchOptions(`/api/districts/${districtId}/villages`, controller.signal)
            .then(setVillages)
            .catch((error: unknown) => {
                if (!isAbortError(error)) setVillageError(true);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingVillages(false);
            });

        return () => controller.abort();
    }, [value.wilayah, value.kecamatan, retry.villages]);

    const retryLevel = (level: DataLevel) => {
        setRetry((current) => ({ ...current, [level]: current[level] + 1 }));
    };

    const fieldClassName = 'mt-2 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 outline-none transition duration-300 focus:border-red-500 focus:ring-4 focus:ring-red-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500';

    return (
        <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
            <div>
                <label htmlFor="gate-wilayah" className="block text-sm font-semibold text-slate-800">Wilayah</label>
                <select
                    id="gate-wilayah"
                    name="wilayah"
                    required
                    value={value.wilayah}
                    disabled={loadingRegions || regionError}
                    onChange={(event) => onChange({
                        ...value,
                        wilayah: event.target.value,
                        kecamatan: '',
                        kelurahan: '',
                        kodePos: '',
                    })}
                    aria-invalid={Boolean(errors.wilayah)}
                    aria-describedby={errors.wilayah ? 'gate-wilayah-error' : regionError ? 'gate-wilayah-load-error' : undefined}
                    className={fieldClassName}
                >
                    <option value="">{loadingRegions ? 'Memuat wilayah...' : 'Pilih Wilayah'}</option>
                    {regions.map((region) => (
                        <option key={region.id} value={region.id}>{region.name}</option>
                    ))}
                </select>
                {errors.wilayah && <p id="gate-wilayah-error" className="mt-1.5 text-xs text-red-700">{errors.wilayah}</p>}
                {regionError && (
                    <p id="gate-wilayah-load-error" role="alert" className="mt-1.5 text-xs text-red-700">
                        {loadErrorMessage}{' '}
                        <button type="button" onClick={() => retryLevel('regions')} className="font-semibold underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                            Coba lagi
                        </button>
                    </p>
                )}
            </div>

            <div>
                <label htmlFor="gate-kecamatan" className="block text-sm font-semibold text-slate-800">Kecamatan</label>
                <select
                    id="gate-kecamatan"
                    name="kecamatan"
                    required
                    value={value.kecamatan}
                    disabled={!value.wilayah || loadingDistricts || districtError || districts.length === 0}
                    onChange={(event) => onChange({
                        ...value,
                        kecamatan: event.target.value,
                        kelurahan: '',
                        kodePos: '',
                    })}
                    aria-invalid={Boolean(errors.kecamatan)}
                    aria-describedby={errors.kecamatan ? 'gate-kecamatan-error' : districtError ? 'gate-kecamatan-load-error' : undefined}
                    className={fieldClassName}
                >
                    <option value="">{loadingDistricts ? 'Memuat kecamatan...' : 'Pilih Kecamatan'}</option>
                    {districts.map((district) => (
                        <option key={district.id} value={district.id}>{district.name}</option>
                    ))}
                </select>
                {errors.kecamatan && <p id="gate-kecamatan-error" className="mt-1.5 text-xs text-red-700">{errors.kecamatan}</p>}
                {districtError && (
                    <p id="gate-kecamatan-load-error" role="alert" className="mt-1.5 text-xs text-red-700">
                        {loadErrorMessage}{' '}
                        <button type="button" onClick={() => retryLevel('districts')} className="font-semibold underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                            Coba lagi
                        </button>
                    </p>
                )}
            </div>

            <div>
                <label htmlFor="gate-kelurahan" className="block text-sm font-semibold text-slate-800">Kelurahan / Desa</label>
                <select
                    id="gate-kelurahan"
                    name="kelurahan"
                    required
                    value={value.kelurahan}
                    disabled={!value.kecamatan || loadingVillages || villageError || villages.length === 0}
                    onChange={(event) => {
                        const selected = villages.find((village) => String(village.id) === event.target.value);
                        onChange({
                            ...value,
                            kelurahan: event.target.value,
                            kodePos: selected?.postal_code ?? '',
                        });
                    }}
                    aria-invalid={Boolean(errors.kelurahan)}
                    aria-describedby={errors.kelurahan ? 'gate-kelurahan-error' : villageError ? 'gate-kelurahan-load-error' : undefined}
                    className={fieldClassName}
                >
                    <option value="">{loadingVillages ? 'Memuat kelurahan/desa...' : 'Pilih Kelurahan / Desa'}</option>
                    {villages.map((village) => (
                        <option key={village.id} value={village.id}>{village.name}</option>
                    ))}
                </select>
                {errors.kelurahan && <p id="gate-kelurahan-error" className="mt-1.5 text-xs text-red-700">{errors.kelurahan}</p>}
                {villageError && (
                    <p id="gate-kelurahan-load-error" role="alert" className="mt-1.5 text-xs text-red-700">
                        {loadErrorMessage}{' '}
                        <button type="button" onClick={() => retryLevel('villages')} className="font-semibold underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                            Coba lagi
                        </button>
                    </p>
                )}
            </div>

            <div>
                <label htmlFor="gate-kode-pos" className="block text-sm font-semibold text-slate-800">Kode Pos</label>
                <input
                    id="gate-kode-pos"
                    name="kodePos"
                    type="text"
                    inputMode="numeric"
                    autoComplete="postal-code"
                    maxLength={5}
                    value={value.kodePos}
                    readOnly
                    placeholder="Terisi otomatis"
                    aria-invalid={Boolean(errors.kodePos)}
                    aria-describedby={errors.kodePos ? 'gate-kode-pos-error' : undefined}
                    className={`${fieldClassName} bg-slate-50`}
                />
                {errors.kodePos && <p id="gate-kode-pos-error" className="mt-1.5 text-xs text-red-700">{errors.kodePos}</p>}
            </div>
        </div>
    );
}
