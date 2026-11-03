import axios from 'axios'

// Alamat API Laravel (berakhiran /api). Atur di file .env:
// VITE_API_BASE_URL=http://localhost:8000/api
const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

// Alamat server Laravel tanpa /api (dipakai untuk /sanctum/csrf-cookie)
const SERVER_URL = new URL(API_BASE_URL).origin

const KUNCI_PENGGUNA = 'temanBaikPengguna'

// Status sesi login di sisi frontend (dipakai router guard)
export const statusSesi = {
  sudahDicek: false,
  aktif: false,
}

const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // wajib: backend memakai session cookie (Sanctum SPA)
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

const KUNCI_ADMIN = 'temanBaikAdmin'

// Status sesi login ADMIN di sisi frontend (dipakai router guard)
export const statusSesiAdmin = {
  sudahDicek: false,
  aktif: false,
  user: null,
}

// Endpoint yang tidak boleh memicu "lempar ke halaman login" saat 401
const ENDPOINT_TANPA_REDIRECT = [
  '/pengadu/login',
  '/pengadu/me',
  '/admin/login',
  '/admin/me',
]

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const url = error.config?.url || ''

    // Sesi habis / belum login -> kembali ke halaman login
    if (status === 401 && !ENDPOINT_TANPA_REDIRECT.includes(url)) {
      if (url.startsWith('/admin') || url.startsWith('/instansi/')) {
        // Sesi ADMIN habis -> halaman login admin
        sessionStorage.removeItem(KUNCI_ADMIN)
        statusSesiAdmin.aktif = false
        statusSesiAdmin.user = null

        if (window.location.pathname !== '/admin/login') {
          window.location.assign('/admin/login')
        }
      } else {
        // Sesi pengadu habis -> halaman login masyarakat
        sessionStorage.removeItem(KUNCI_PENGGUNA)
        statusSesi.aktif = false

        if (window.location.pathname !== '/') {
          window.location.assign('/')
        }
      }
    }

    return Promise.reject(error)
  },
)

// Ambil cookie CSRF dari Laravel (URL ini TIDAK memakai prefix /api)
export async function ambilCookieCSRF() {
  await axios.get(`${SERVER_URL}/sanctum/csrf-cookie`, {
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
  })
}

// Cek apakah sesi login masih aktif di server. Mengembalikan data
// pengadu atau null bila belum login.
export async function cekSesi() {
  try {
    const { data } = await api.get('/pengadu/me')

    sessionStorage.setItem(KUNCI_PENGGUNA, JSON.stringify(data))
    statusSesi.sudahDicek = true
    statusSesi.aktif = true

    return data
  } catch {
    sessionStorage.removeItem(KUNCI_PENGGUNA)
    statusSesi.sudahDicek = true
    statusSesi.aktif = false

    return null
  }
}

// Logout ke backend lalu bersihkan data lokal
export async function keluarDariSistem() {
  try {
    await api.post('/pengadu/logout')
  } catch (error) {
    // Tetap lanjut membersihkan sesi lokal walau request gagal
    console.error('Logout ke server gagal:', error)
  } finally {
    sessionStorage.removeItem(KUNCI_PENGGUNA)
    sessionStorage.removeItem('temanBaikTiketTerakhir')
    statusSesi.sudahDicek = true
    statusSesi.aktif = false
  }
}

// Cek sesi ADMIN di server. Peran yang diizinkan masuk: super_admin dan
// admin_instansi. Mengembalikan data user atau null.
export const PERAN_ADMIN = ['super_admin', 'admin_instansi']

export async function cekSesiAdmin() {
  try {
    const { data } = await api.get('/admin/me')
    const diizinkan = PERAN_ADMIN.includes(data?.role)

    statusSesiAdmin.sudahDicek = true
    statusSesiAdmin.aktif = diizinkan
    statusSesiAdmin.user = diizinkan ? data : null

    if (diizinkan) {
      sessionStorage.setItem(KUNCI_ADMIN, JSON.stringify(data))
    }

    return statusSesiAdmin.user
  } catch {
    sessionStorage.removeItem(KUNCI_ADMIN)
    statusSesiAdmin.sudahDicek = true
    statusSesiAdmin.aktif = false
    statusSesiAdmin.user = null

    return null
  }
}

// Halaman awal sesuai peran
export function berandaPeran(role) {
  return role === 'admin_instansi' ? '/instansi' : '/admin'
}

// Logout admin ke backend lalu bersihkan data lokal
export async function keluarAdmin() {
  try {
    await api.post('/admin/logout')
  } catch (error) {
    console.error('Logout admin ke server gagal:', error)
  } finally {
    sessionStorage.removeItem(KUNCI_ADMIN)
    statusSesiAdmin.sudahDicek = true
    statusSesiAdmin.aktif = false
    statusSesiAdmin.user = null
  }
}

// Ubah error Axios menjadi pesan yang mudah dibaca pengguna
export function pesanError(error, cadangan = 'Terjadi kesalahan.') {
  if (!error.response) {
    return 'Server tidak dapat dihubungi. Pastikan backend Laravel berjalan.'
  }

  const { status, data } = error.response

  if (status === 419) {
    return 'Sesi atau CSRF tidak valid. Muat ulang halaman lalu coba lagi.'
  }

  if (status === 422 && data?.errors) {
    return Object.values(data.errors).flat().join(' ')
  }

  return data?.message || `${cadangan} (HTTP ${status})`
}

export default api
