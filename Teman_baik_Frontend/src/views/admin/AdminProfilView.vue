<template>
  <AdminLayout>
    <p class="adm-breadcrumb">
      Beranda &rsaquo; {{ instansi ? 'Admin Instansi' : 'Super Admin' }} &rsaquo;
      <b>Profil &amp; Akun</b>
    </p>

    <!-- ================= JUDUL ================= -->
    <div class="adm-head">
      <div>
        <div class="adm-title-row">
          <h1 class="adm-title">Profil &amp; Pengaturan Akun</h1>
          <span class="adm-pill-red pf-pill">
            {{ instansi ? 'Admin Instansi' : 'Root Administrator' }}
          </span>
        </div>

        <p class="adm-subtitle">
          Kelola informasi identitas, kredensial login, dan pengaturan
          keamanan akses akun Anda pada portal Teman Baik Banjarmasin.
        </p>
      </div>

      <div class="adm-head-actions">
        <button type="button" class="adm-btn" :disabled="menyimpan" @click="batal">
          Batal
        </button>

        <button
          type="button"
          class="adm-btn adm-btn-primary"
          :disabled="menyimpan || !formTerisi"
          @click="simpan"
        >
          <Save :size="13" /> {{ menyimpan ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </button>
      </div>
    </div>

    <p v-if="pesanSukses" class="adm-alert ok pad">{{ pesanSukses }}</p>
    <p v-if="galatUmum" class="adm-alert pad">{{ galatUmum }}</p>

    <div class="pf-grid">
      <!-- ============ KOLOM KIRI ============ -->
      <div class="pf-col">
        <!-- Identitas -->
        <section class="adm-card pf-card">
          <header class="pf-card-head">
            <span class="adm-kpi-icon"><IdCard :size="15" /></span>

            <div>
              <h2>Data Identitas {{ instansi ? 'Admin Instansi' : 'Super Admin' }}</h2>
              <p>Profil akun penanggung jawab sistem</p>
            </div>

            <span class="adm-tag netral pf-level">
              {{ instansi ? 'ADMIN OPD' : 'LEVEL 1' }}
            </span>
          </header>

          <div class="pf-ident">
            <span class="pf-avatar">{{ inisial(user.nama) }}</span>

            <div>
              <strong>{{ user.nama || '-' }}</strong>
              <small>@{{ user.username || '-' }}</small>
              <span class="adm-badge st-green">Akun Aktif</span>
            </div>
          </div>

          <div class="pf-fields">
            <div class="adm-field">
              <label>Nama Lengkap</label>
              <input class="adm-input" type="text" :value="user.nama" readonly />
            </div>

            <div class="adm-field">
              <label>Username</label>
              <input class="adm-input" type="text" :value="user.username" readonly />
            </div>

            <div class="adm-field pf-full">
              <label>Email Kedinasan</label>
              <input class="adm-input" type="text" :value="user.email || '-'" readonly />
            </div>

            <div v-if="instansi" class="adm-field pf-full">
              <label>Instansi / OPD</label>
              <input class="adm-input" type="text" :value="namaInstansiAktif || '-'" readonly />
            </div>

            <div class="adm-field">
              <label>Peran</label>
              <input
                class="adm-input"
                type="text"
                :value="instansi ? 'Admin Instansi' : 'Super Admin'"
                readonly
              />
            </div>

            <div class="adm-field">
              <label>Terdaftar Sejak</label>
              <input class="adm-input" type="text" :value="formatTanggal(user.created_at)" readonly />
            </div>
          </div>

          <p class="pf-hint">
            Data identitas dikelola oleh pengembang sistem dan tidak dapat
            diubah dari halaman ini.
          </p>
        </section>

        <!-- Keamanan -->
        <section class="adm-card pf-card">
          <header class="pf-card-head">
            <span class="adm-kpi-icon"><KeyRound :size="15" /></span>

            <div>
              <h2>Keamanan &amp; Kredensial Login</h2>
              <p>Perbarui kata sandi secara berkala agar akun tetap aman</p>
            </div>
          </header>

          <form class="pf-fields" novalidate @submit.prevent="simpan">
            <div class="adm-field pf-full">
              <label for="p-lama">Kata Sandi Saat Ini</label>

              <div class="pf-sandi">
                <input
                  id="p-lama"
                  v-model="form.password_lama"
                  class="adm-input"
                  :type="lihat ? 'text' : 'password'"
                  autocomplete="current-password"
                />

                <button
                  type="button"
                  class="adm-icon-btn"
                  :aria-label="lihat ? 'Sembunyikan' : 'Tampilkan'"
                  @click="lihat = !lihat"
                >
                  <EyeOff v-if="lihat" :size="14" />
                  <Eye v-else :size="14" />
                </button>
              </div>

              <span v-if="errors.password_lama" class="adm-field-error">
                {{ errors.password_lama }}
              </span>
            </div>

            <div class="adm-field">
              <label for="p-baru">Kata Sandi Baru</label>
              <input
                id="p-baru"
                v-model="form.password_baru"
                class="adm-input"
                :type="lihat ? 'text' : 'password'"
                placeholder="Minimal 8 karakter"
                autocomplete="new-password"
                maxlength="100"
              />
              <span v-if="errors.password_baru" class="adm-field-error">
                {{ errors.password_baru }}
              </span>
            </div>

            <div class="adm-field">
              <label for="p-konfirmasi">Konfirmasi Kata Sandi Baru</label>
              <input
                id="p-konfirmasi"
                v-model="form.password_baru_confirmation"
                class="adm-input"
                :type="lihat ? 'text' : 'password'"
                placeholder="Ulangi kata sandi baru"
                autocomplete="new-password"
                maxlength="100"
              />
              <span v-if="errors.password_baru_confirmation" class="adm-field-error">
                {{ errors.password_baru_confirmation }}
              </span>
            </div>

            <div class="pf-full pf-kekuatan">
              <div class="pf-kekuatan-head">
                <span>Kekuatan Sandi: <b>{{ kekuatan.label }}</b></span>
                <span>{{ kekuatan.persen }}%</span>
              </div>

              <div class="pf-bars">
                <i
                  v-for="n in 4"
                  :key="n"
                  :class="{ isi: n <= kekuatan.skor }"
                  :data-skor="kekuatan.skor"
                ></i>
              </div>

              <small>
                Gunakan kombinasi huruf besar, huruf kecil, angka, dan
                simbol. Disarankan 12 karakter atau lebih.
              </small>
            </div>
          </form>

          <!-- 2FA: belum ada di backend -->
          <div class="pf-row pf-row-off">
            <span class="adm-kpi-icon"><ShieldCheck :size="15" /></span>

            <div>
              <strong>Autentikasi Dua Langkah (2FA)</strong>
              <small>Fitur ini belum tersedia pada backend saat ini.</small>
            </div>

            <span class="adm-tag netral">BELUM TERSEDIA</span>
          </div>

          <h3 class="pf-sub">Sesi Login Aktif</h3>

          <div class="pf-row">
            <span class="adm-kpi-icon"><Monitor :size="15" /></span>

            <div>
              <strong>{{ perangkat }}</strong>
              <small>Masuk sebagai @{{ user.username }} &bull; sesi ini</small>
            </div>

            <span class="adm-badge st-green">Saat Ini</span>
          </div>
        </section>
      </div>

      <!-- ============ KOLOM KANAN ============ -->
      <div class="pf-col">
        <section class="adm-card pf-card">
          <header class="pf-card-head">
            <span class="adm-kpi-icon"><ShieldCheck :size="15" /></span>

            <div>
              <h2>Wewenang &amp; Hak Akses</h2>
              <p>Mandat otoritas sistem Teman Baik</p>
            </div>
          </header>

          <div v-if="!instansi" class="pf-full-akses">
            <KeyRound :size="15" />

            <div>
              <strong>Akses Penuh (Full Super Admin Level 1)</strong>
              <small>Hak kendali tertinggi atas seluruh data dan manajemen instansi.</small>
            </div>
          </div>

          <ul class="pf-hak">
            <li v-for="h in daftarHak" :key="h.judul">
              <CircleCheck :size="14" />

              <div>
                <strong>{{ h.judul }}</strong>
                <small>{{ h.uraian }}</small>
              </div>
            </li>
          </ul>

          <p class="pf-hint">
            Perubahan wewenang dilakukan pada sisi backend oleh pengembang
            sistem.
          </p>
        </section>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import {
  CircleCheck,
  Eye,
  EyeOff,
  IdCard,
  KeyRound,
  Monitor,
  Save,
  ShieldCheck,
} from 'lucide-vue-next'

import AdminLayout from '../../components/admin/AdminLayout.vue'

import api, { cekSesiAdmin, pesanError, statusSesiAdmin } from '../../services/api'
import { formatTanggal, inisial } from '../../services/admin'
import { namaInstansi } from '../../services/instansi'

const user = reactive({ ...(statusSesiAdmin.user || {}) })

const form = reactive({
  password_lama: '',
  password_baru: '',
  password_baru_confirmation: '',
})

const errors = ref({})
const galatUmum = ref('')
const pesanSukses = ref('')
const menyimpan = ref(false)
const lihat = ref(false)

const instansi = statusSesiAdmin.user?.role === 'admin_instansi'
const namaInstansiAktif = ref('')

// Hak akses sesuai route yang dilindungi middleware role di backend
const hakSuperAdmin = [
  {
    judul: 'Kelola Akun Admin OPD & Verifikator',
    uraian: 'Membuat, mengubah, dan menghapus akun admin instansi.',
  },
  {
    judul: 'Verifikasi, Disposisi & Tanggapan Pengaduan',
    uraian: 'Memverifikasi, menolak, meneruskan ke instansi, dan membalas laporan.',
  },
  {
    judul: 'Ekspor Rekapitulasi Pengaduan',
    uraian: 'Mengunduh data pengaduan dalam format CSV.',
  },
  {
    judul: 'Kelola Data Instansi',
    uraian: 'Menambah dan memperbarui data kontak instansi tujuan.',
  },
]

const hakInstansi = [
  {
    judul: 'Melihat Laporan yang Diteruskan ke Instansi',
    uraian: 'Hanya laporan milik instansi Anda yang berstatus diproses atau selesai.',
  },
  {
    judul: 'Mengirim Balasan Tindak Lanjut',
    uraian: 'Balasan dikirim sebagai draf ke Admin Teman Baik untuk ditinjau.',
  },
  {
    judul: 'Mengunggah Dokumentasi & Berita Acara',
    uraian: 'Foto JPG/PNG dan berita acara PDF, maksimal 10 lampiran per laporan.',
  },
]

const daftarHak = instansi ? hakInstansi : hakSuperAdmin

const formTerisi = computed(
  () =>
    !!(
      form.password_lama ||
      form.password_baru ||
      form.password_baru_confirmation
    ),
)

// Skor 0-4 berdasarkan panjang dan variasi karakter
const kekuatan = computed(() => {
  const p = form.password_baru

  if (!p) return { skor: 0, persen: 0, label: '-' }

  let skor = 0

  if (p.length >= 8) skor++
  if (p.length >= 12) skor++
  if (/[a-z]/.test(p) && /[A-Z]/.test(p)) skor++
  if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) skor++

  const label = ['Lemah', 'Cukup', 'Kuat', 'Sangat Kuat', 'Sangat Kuat'][skor]

  return { skor, persen: Math.max(skor, 1) * 25, label }
})

// Deteksi sederhana peramban dan sistem operasi dari user agent
const perangkat = computed(() => {
  const ua = navigator.userAgent

  const browser = /Edg\//.test(ua)
    ? 'Microsoft Edge'
    : /OPR\//.test(ua)
      ? 'Opera'
      : /Firefox\//.test(ua)
        ? 'Mozilla Firefox'
        : /Chrome\//.test(ua)
          ? 'Google Chrome'
          : /Safari\//.test(ua)
            ? 'Safari'
            : 'Peramban'

  const os = /Windows/.test(ua)
    ? 'Windows'
    : /Android/.test(ua)
      ? 'Android'
      : /iPhone|iPad/.test(ua)
        ? 'iOS'
        : /Mac OS X/.test(ua)
          ? 'macOS'
          : /Linux/.test(ua)
            ? 'Linux'
            : 'perangkat ini'

  return `${browser} di ${os}`
})

function batal() {
  form.password_lama = ''
  form.password_baru = ''
  form.password_baru_confirmation = ''
  errors.value = {}
  galatUmum.value = ''
  pesanSukses.value = ''
}

// POST /api/admin/ganti-password
async function simpan() {
  errors.value = {}
  galatUmum.value = ''
  pesanSukses.value = ''

  if (!formTerisi.value) return

  const e = {}

  if (!form.password_lama) e.password_lama = 'Kata sandi saat ini wajib diisi.'

  if (!form.password_baru) e.password_baru = 'Kata sandi baru wajib diisi.'
  else if (form.password_baru.length < 8) {
    e.password_baru = 'Kata sandi baru minimal 8 karakter.'
  } else if (form.password_baru === form.password_lama) {
    e.password_baru = 'Kata sandi baru harus berbeda dari kata sandi lama.'
  }

  if (form.password_baru_confirmation !== form.password_baru) {
    e.password_baru_confirmation = 'Konfirmasi kata sandi tidak cocok.'
  }

  if (Object.keys(e).length) {
    errors.value = e
    return
  }

  menyimpan.value = true

  try {
    await api.post('/admin/ganti-password', { ...form })

    pesanSukses.value = 'Kata sandi berhasil diganti.'

    form.password_lama = ''
    form.password_baru = ''
    form.password_baru_confirmation = ''
  } catch (err) {
    console.error('Ganti password gagal:', err.response?.data || err)

    const dariServer = err.response?.data?.errors

    if (err.response?.status === 422 && dariServer) {
      const hasil = {}

      for (const [k, v] of Object.entries(dariServer)) {
        hasil[k] = Array.isArray(v) ? v[0] : v
      }

      // Pesan bawaan Laravel berbahasa Inggris -> terjemahkan yang umum
      if (hasil.password_lama) hasil.password_lama = 'Kata sandi saat ini salah.'

      errors.value = hasil
    } else if (err.response?.status === 429) {
      galatUmum.value =
        'Terlalu banyak percobaan ganti kata sandi. Coba lagi dalam 10 menit.'
    } else {
      galatUmum.value = pesanError(err, 'Kata sandi gagal diganti')
    }
  } finally {
    menyimpan.value = false
  }
}

onMounted(async () => {
  // Segarkan data akun dari GET /api/admin/me (memuat created_at, dll.)
  const terbaru = await cekSesiAdmin()

  if (terbaru) Object.assign(user, terbaru)

  if (instansi) namaInstansiAktif.value = await namaInstansi(user.id_instansi)
})
</script>

<style scoped>
.pf-pill {
  margin-left: 0;
  font-size: 8px;
}

.pf-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
  align-items: start;
  gap: 20px;
}

.pf-col {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 20px;
}

.pf-card {
  padding: 20px;
}

.pf-card-head {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 16px;
}

.pf-card-head h2 {
  font-size: 13px;
  font-weight: 800;
}

.pf-card-head p {
  margin-top: 2px;
  color: #7d6e6f;
  font-size: 10px;
}

.pf-level {
  margin-left: auto;
}

.pf-ident {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 18px;
  padding: 16px;
  border-radius: 12px;
  background: #fff3f3;
}

.pf-avatar {
  display: grid;
  place-items: center;
  width: 58px;
  height: 58px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #b51220;
  color: #fff;
  font-size: 19px;
  font-weight: 800;
}

.pf-ident strong {
  display: block;
  font-size: 14px;
}

.pf-ident small {
  display: block;
  margin: 2px 0 7px;
  color: #7d6e6f;
  font-size: 11px;
}

.pf-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.pf-full {
  grid-column: 1 / -1;
}

.pf-fields .adm-input[readonly] {
  background: #fcf7f7;
  color: #4d3f40;
  cursor: default;
}

.pf-hint {
  margin-top: 14px;
  color: #8c7c7d;
  font-size: 10px;
  line-height: 1.55;
}

.pf-sandi {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pf-kekuatan {
  padding: 12px 14px;
  border-radius: 10px;
  background: #fff6f6;
  color: #6b5c5d;
  font-size: 10px;
}

.pf-kekuatan-head {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.pf-kekuatan-head b {
  color: #b51220;
}

.pf-bars {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 5px;
  margin-bottom: 8px;
}

.pf-bars i {
  height: 5px;
  border-radius: 99px;
  background: #ecdede;
}

.pf-bars i.isi {
  background: #b51220;
}

.pf-bars i.isi[data-skor='3'],
.pf-bars i.isi[data-skor='4'] {
  background: #1c8a4e;
}

.pf-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  padding: 13px 14px;
  border: 1px solid #f1e3e3;
  border-radius: 12px;
}

.pf-row div {
  min-width: 0;
  flex: 1;
}

.pf-row strong {
  display: block;
  font-size: 11.5px;
}

.pf-row small {
  display: block;
  margin-top: 2px;
  color: #7d6e6f;
  font-size: 10px;
}

.pf-row-off {
  opacity: 0.75;
}

.pf-sub {
  margin: 22px 0 0;
  font-size: 11px;
  font-weight: 800;
}

.pf-full-akses {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-bottom: 14px;
  padding: 14px;
  border-radius: 12px;
  background: #f6c9cd;
  color: #b51220;
}

.pf-full-akses strong {
  display: block;
  color: #7a0f18;
  font-size: 11.5px;
}

.pf-full-akses small {
  display: block;
  margin-top: 3px;
  color: #8d3b42;
  font-size: 10px;
  line-height: 1.5;
}

.pf-hak {
  display: flex;
  margin: 0;
  padding: 0;
  flex-direction: column;
  gap: 8px;
  list-style: none;
}

.pf-hak li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 10px;
  background: #fff6f6;
  color: #1c8a4e;
}

.pf-hak strong {
  display: block;
  color: #2b1f20;
  font-size: 11px;
}

.pf-hak small {
  display: block;
  margin-top: 2px;
  color: #7d6e6f;
  font-size: 10px;
  line-height: 1.5;
}

@media (max-width: 1000px) {
  .pf-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .pf-fields {
    grid-template-columns: 1fr;
  }
}
</style>
