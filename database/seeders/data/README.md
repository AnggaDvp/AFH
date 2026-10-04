# Jabodetabek seed data

`jabodetabek.json` is a scoped snapshot for the 13 Jabodetabek mainland cities
and regencies requested by the application.

Administrative names, hierarchy, and Kemendagri codes are sourced from
[`kode-wilayah-id` 1.2.0](https://www.npmjs.com/package/kode-wilayah-id),
distributed under the MIT license. The package documents its regional data as
BPS Semester 1 2025 and its postal-code mapping as sourced from PT Pos Indonesia.
The snapshot stores the Kemendagri codes to preserve the administrative
identifiers and dotted format used by this application.

The package snapshot has an outdated status/code for Setia Asih. Peraturan
Daerah Kabupaten Bekasi Nomor 1 Tahun 2022 changed it from a village to an
urban village
([official document](https://peraturan.bpk.go.id/Download/357333/2022perda32160001.pdf)).
The regional hierarchy is corrected to `32.16.01.1007`, consistent with the
[current Tarumajaya regional listing](https://api.datawilayah.com/api/desa_kelurahan/32.16.01.json).
The package has no postal code for this entry; `17215` is supplemented from
[postcode.id](https://postcode.id/kodepos/jawa-barat/bekasi/tarumajaya/setia-asih).

The village type follows the first digit of the final four-digit Kemendagri
village code: `1` is stored as `urban_village`, and `2` as `village`.
Kepulauan Seribu is intentionally excluded.

Snapshot totals: 13 regions, 183 districts, and 1,502 villages.
