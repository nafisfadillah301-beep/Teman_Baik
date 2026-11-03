import api from './api'
import { unduhBerkas } from './admin'

/* =====================================================
   Helper berkas (bukti pelapor & lampiran instansi)
   File dilindungi sesi login, jadi diambil lewat Axios
   sebagai blob lalu ditampilkan dengan object URL.
   Backend menyajikan file inline secara bawaan; tambahkan
   ?download=1 hanya untuk unduhan.
===================================================== */

const MIME = {
  jpg: 'image/jpeg',
  jpeg: 'image/jpeg',
  png: 'image/png',
  gif: 'image/gif',
  webp: 'image/webp',
  pdf: 'application/pdf',
}

function ekstensi(nama = '') {
  return String(nama).split('.').pop().toLowerCase()
}

// 'gambar' | 'pdf' | 'lain' (berdasarkan ekstensi nama file)
export function jenisBerkas(nama) {
  const mime = MIME[ekstensi(nama)] || ''

  if (mime.startsWith('image/')) return 'gambar'
  if (mime === 'application/pdf') return 'pdf'

  return 'lain'
}

// GET path -> Blob, dengan tipe MIME dipastikan benar agar tampil inline
export async function ambilBlobBerkas(path, nama) {
  const { data } = await api.get(path, { responseType: 'blob' })
  const mime = MIME[ekstensi(nama)]

  return mime && data.type !== mime ? new Blob([data], { type: mime }) : data
}

export function pesanGalatBerkas(error) {
  const status = error?.response?.status

  if (status === 404) return 'Berkas tidak ditemukan di server.'
  if (status === 403) return 'Anda tidak berhak membuka berkas ini.'
  if (!error?.response) return 'Server tidak dapat dihubungi.'

  return 'Berkas gagal dimuat.'
}

export { unduhBerkas }
