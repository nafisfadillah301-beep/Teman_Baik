import { reactive } from 'vue'

import api from './api'

/* =====================================================
   PORTAL ADMIN INSTANSI  (/api/instansi/...)
   Instansi hanya melihat laporan miliknya yang berstatus
   "dikirim" (menunggu balasan) atau "dijawab" (selesai).
===================================================== */

export const LABEL_STATUS_INSTANSI = {
  dikirim: 'Menunggu Balasan',
  dijawab: 'Selesai',
}

export const KELAS_STATUS_INSTANSI = {
  dikirim: 'st-red',
  dijawab: 'st-green',
}

// GET /api/instansi/ringkasan -> { menunggu_balasan, sudah_dijawab }
export const ringkasanInstansi = reactive({
  data: null,
  memuat: false,
  error: '',
})

export async function muatRingkasanInstansi() {
  ringkasanInstansi.memuat = true
  ringkasanInstansi.error = ''

  try {
    const { data } = await api.get('/instansi/ringkasan')

    ringkasanInstansi.data = data
  } catch (error) {
    ringkasanInstansi.error =
      error.response?.data?.message || 'Server tidak dapat dihubungi.'
  } finally {
    ringkasanInstansi.memuat = false
  }
}

const jalur = (tiket) => `/instansi/pengaduan/${encodeURIComponent(tiket)}`

// GET /api/instansi/pengaduan?status=&q=&page=
export async function ambilLaporanInstansi(params = {}) {
  const { data } = await api.get('/instansi/pengaduan', { params })

  return data // paginator Laravel
}

// GET /api/instansi/pengaduan/{no_tiket}
export async function ambilDetailInstansi(tiket) {
  const { data } = await api.get(jalur(tiket))

  return data
}

// POST /api/instansi/pengaduan/{no_tiket}/balasan  { isi }
export const kirimBalasanInstansi = (tiket, isi) =>
  api.post(`${jalur(tiket)}/balasan`, { isi })

// POST /api/instansi/pengaduan/{no_tiket}/lampiran (multipart)
export const unggahLampiranInstansi = (tiket, formData) =>
  api.post(`${jalur(tiket)}/lampiran`, formData)

// DELETE /api/instansi/pengaduan/{no_tiket}/lampiran/{id}
export const hapusLampiranInstansi = (tiket, idLampiran) =>
  api.delete(`${jalur(tiket)}/lampiran/${idLampiran}`)

export const jalurBuktiInstansi = (tiket) => `${jalur(tiket)}/bukti`

export const jalurLampiranInstansi = (tiket, id) =>
  `${jalur(tiket)}/lampiran/${id}`

// Nama instansi dari endpoint publik GET /api/instansi
export async function namaInstansi(idInstansi) {
  try {
    const { data } = await api.get('/instansi')

    return (
      (Array.isArray(data) ? data : []).find(
        (i) => Number(i.id) === Number(idInstansi),
      )?.nama_instansi || ''
    )
  } catch {
    return ''
  }
}
