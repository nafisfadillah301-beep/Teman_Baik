import { reactive } from 'vue'

import api from './api'

/* =====================================================
   KONSTANTA STATUS (sesuai tabel `statuses` di backend)
===================================================== */
export const DAFTAR_STATUS = [
  'diterima',
  'diverifikasi',
  'dikirim',
  'dijawab',
  'ditolak',
]

export const LABEL_STATUS = {
  diterima: 'Diterima',
  diverifikasi: 'Diverifikasi',
  dikirim: 'Diproses',
  dijawab: 'Selesai',
  ditolak: 'Ditolak',
}

export const KELAS_STATUS = {
  diterima: 'st-amber',
  diverifikasi: 'st-blue',
  dikirim: 'st-red',
  dijawab: 'st-green',
  ditolak: 'st-gray',
}

// Label tombol tindak lanjut utama per status
export const AKSI_UTAMA = {
  diterima: 'Verifikasi',
  diverifikasi: 'Disposisi',
  dikirim: 'Balas',
}

export const OPSI_RENTANG = [
  { nilai: 'semua', label: 'Semua Waktu' },
  { nilai: 'hari-ini', label: 'Hari Ini' },
  { nilai: '7', label: '7 Hari Terakhir' },
  { nilai: '30', label: '30 Hari Terakhir' },
  { nilai: '90', label: '90 Hari Terakhir' },
]

/* =====================================================
   FORMAT
===================================================== */
export function angka(nilai) {
  return nilai === null || nilai === undefined || Number.isNaN(Number(nilai))
    ? '-'
    : Number(nilai).toLocaleString('id-ID')
}

export function formatTanggal(iso) {
  if (!iso) return '-'

  const d = new Date(iso)

  if (Number.isNaN(d.getTime())) return '-'

  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

export function formatWaktu(iso) {
  if (!iso) return '-'

  const d = new Date(iso)

  if (Number.isNaN(d.getTime())) return '-'

  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function waktuRelatif(iso) {
  if (!iso) return '-'

  const d = new Date(iso)

  if (Number.isNaN(d.getTime())) return '-'

  const detik = (Date.now() - d.getTime()) / 1000

  if (detik < 60) return 'Baru saja'
  if (detik < 3600) return `${Math.floor(detik / 60)} menit lalu`
  if (detik < 86400) return `${Math.floor(detik / 3600)} jam lalu`
  if (detik < 7 * 86400) return `${Math.floor(detik / 86400)} hari lalu`

  return formatTanggal(iso)
}

export function inisial(nama) {
  const kata = String(nama || '').trim().split(/\s+/).filter(Boolean)

  if (!kata.length) return '?'

  return (kata[0][0] + (kata.length > 1 ? kata[kata.length - 1][0] : ''))
    .toUpperCase()
}

// Tanggal lokal YYYY-MM-DD
export function tglISO(date) {
  const p = (n) => String(n).padStart(2, '0')

  return `${date.getFullYear()}-${p(date.getMonth() + 1)}-${p(date.getDate())}`
}

// Batas awal waktu untuk pilihan rentang (null = tanpa batas)
export function batasRentang(rentang) {
  const d = new Date()

  if (rentang === 'hari-ini') {
    d.setHours(0, 0, 0, 0)
    return d
  }

  const hari = { 7: 7, 30: 30, 90: 90 }[rentang]

  if (!hari) return null

  d.setDate(d.getDate() - hari)
  d.setHours(0, 0, 0, 0)

  return d
}

/* =====================================================
   RINGKASAN DASHBOARD  (GET /api/admin/dashboard)
===================================================== */
export const ringkasan = reactive({
  data: null,
  memuat: false,
  error: '',
})

export async function muatRingkasan() {
  ringkasan.memuat = true
  ringkasan.error = ''

  try {
    const { data } = await api.get('/admin/dashboard')

    ringkasan.data = data
  } catch (error) {
    ringkasan.error =
      error.response?.data?.message || 'Server tidak dapat dihubungi.'
  } finally {
    ringkasan.memuat = false
  }
}

// Turunan angka dari respons dashboard
export function hitungRingkasan(data) {
  const per = {}

  for (const s of data?.per_status || []) {
    per[s.status] = s.jumlah
  }

  const total = data?.total || 0
  const pengaduan = data?.per_jenis?.pengaduan || 0
  const informasi = data?.per_jenis?.informasi || 0
  const selesai = per.dijawab || 0

  const persen = (bagian, semua) =>
    semua > 0 ? Math.round((bagian / semua) * 1000) / 10 : 0

  return {
    total,
    diterima: per.diterima || 0,
    diverifikasi: per.diverifikasi || 0,
    dikirim: per.dikirim || 0,
    ditolak: per.ditolak || 0,
    // "Diproses" = sudah diverifikasi atau sudah diteruskan ke instansi
    diproses: (per.diverifikasi || 0) + (per.dikirim || 0),
    selesai,
    pengaduan,
    informasi,
    persenPengaduan: persen(pengaduan, total),
    persenInformasi: persen(informasi, total),
    persenSelesai: persen(selesai, total),
    rataKepuasan: data?.evaluasi?.rata_rata ?? null,
    responden: data?.evaluasi?.responden || 0,
  }
}

/* =====================================================
   PENGADUAN  (/api/admin/pengaduan)
===================================================== */
export async function ambilPengaduan(params = {}) {
  const { data } = await api.get('/admin/pengaduan', { params })

  return data // paginator Laravel: { data, current_page, last_page, total, ... }
}

export async function ambilDetailPengaduan(tiket) {
  const { data } = await api.get(
    `/admin/pengaduan/${encodeURIComponent(tiket)}`,
  )

  return data
}

const jalurTiket = (tiket) => `/admin/pengaduan/${encodeURIComponent(tiket)}`

export const verifikasiPengaduan = (tiket, catatan) =>
  api.post(`${jalurTiket(tiket)}/verifikasi`, { catatan: catatan || null })

export const tolakPengaduan = (tiket, alasan) =>
  api.post(`${jalurTiket(tiket)}/tolak`, { alasan })

export const teruskanPengaduan = (tiket, instansi) =>
  api.post(`${jalurTiket(tiket)}/teruskan`, instansi ? { instansi } : {})

export const balasPengaduan = (tiket, isi) =>
  api.post(`${jalurTiket(tiket)}/balasan`, { isi })

/* =====================================================
   INSTANSI & AKUN ADMIN INSTANSI
===================================================== */
export async function ambilInstansi() {
  const { data } = await api.get('/admin/instansi')

  return Array.isArray(data) ? data : []
}

export async function ambilAkun() {
  const { data } = await api.get('/admin/users')

  return Array.isArray(data) ? data : []
}

export const buatAkun = (payload) => api.post('/admin/users', payload)

export const ubahAkun = (id, payload) =>
  api.put(`/admin/users/${id}`, payload)

export const hapusAkun = (id) => api.delete(`/admin/users/${id}`)

/* =====================================================
   UNDUH FILE (butuh cookie sesi, jadi lewat blob)
===================================================== */
export function simpanBlob(blob, namaFile) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')

  a.href = url
  a.download = namaFile
  document.body.appendChild(a)
  a.click()
  a.remove()

  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

export async function unduhBerkas(path, namaFile) {
  const { data } = await api.get(path, {
    params: { download: 1 },
    responseType: 'blob',
  })

  simpanBlob(data, namaFile)
}

// GET /api/admin/export -> CSV (filter: status, instansi, dari, sampai)
export async function unduhEkspor(filter = {}) {
  const params = {}

  for (const [k, v] of Object.entries(filter)) {
    if (v !== '' && v !== null && v !== undefined) params[k] = v
  }

  const { data } = await api.get('/admin/export', {
    params,
    responseType: 'blob',
  })

  const cap = new Date()
    .toISOString()
    .replace(/[-:T]/g, '')
    .slice(0, 14)

  simpanBlob(data, `data_pengaduan_${cap}.csv`)
}

// Buat CSV dari array objek (dipakai ekspor daftar akun)
export function bangunCsv(kolom, baris) {
  const esc = (v) => {
    let t = String(v ?? '')

    // cegah formula injection di Excel
    if (/^[=+\-@\t\r]/.test(t)) t = `'${t}`

    return `"${t.replace(/"/g, '""')}"`
  }

  return [
    kolom.map((k) => esc(k.judul)).join(';'),
    ...baris.map((b) => kolom.map((k) => esc(b[k.kunci])).join(';')),
  ].join('\r\n')
}

export function simpanTeks(namaFile, teks) {
  simpanBlob(
    new Blob(['\uFEFF' + teks], { type: 'text/csv;charset=utf-8' }),
    namaFile,
  )
}
