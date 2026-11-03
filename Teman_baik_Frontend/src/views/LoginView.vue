
<template>
  <div class="login-page">
    <header class="login-header">
      <RouterLink to="/" class="brand">
        <span class="brand-logo">B</span>
        <span class="brand-name">Teman Baik</span>
      </RouterLink>
    </header>

    <main class="login-main">
      <section class="login-card">
        <div class="service-icon">
          <ShieldCheck :size="27" :stroke-width="2.5" />
          <span class="service-badge">
            <Check :size="10" :stroke-width="3" />
          </span>
        </div>

        <div class="service-caption">
          <span class="caption-dot"></span>
          LAYANAN RESMI PEMKO BANJARMASIN
        </div>

        <form class="login-form" @submit.prevent="masuk">
          <div class="form-group">
            <div class="label-row">
              <label for="nik">Nomor Induk Kependudukan (NIK)</label>
              <span>16 Digit Wajib</span>
            </div>

            <div class="input-wrapper" :class="{ invalid: nikError }">
              <ContactRound :size="15" class="input-icon" />

              <input
                id="nik"
                v-model="nik"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                maxlength="16"
                placeholder="Masukkan 16 digit NIK"
                :disabled="sedangLogin"
                @input="validasiNik"
              />
            </div>

            <p v-if="nikError" class="error-message">
              {{ nikError }}
            </p>
          </div>

          <div class="form-group">
            <div class="label-row">
              <label for="nama">Nama Lengkap</label>
              <span>Sesuai KTP</span>
            </div>

            <div class="input-wrapper" :class="{ invalid: namaError }">
              <UserRound :size="15" class="input-icon" />

              <input
                id="nama"
                v-model="nama"
                type="text"
                autocomplete="name"
                placeholder="Masukkan nama lengkap sesuai KTP"
                :disabled="sedangLogin"
                @input="namaError = ''; pesan = ''"
              />
            </div>

            <p v-if="namaError" class="error-message">
              {{ namaError }}
            </p>
          </div>

          <p
            v-if="pesan"
            class="form-message"
            :class="{ 'success-message': jenisPesan === 'sukses' }"
            role="alert"
          >
            {{ pesan }}
          </p>

          <button
            type="submit"
            class="login-button"
            :disabled="sedangLogin"
          >
            <span>
              {{ sedangLogin ? 'Menghubungkan...' : 'Masuk' }}
            </span>

            <ArrowRight
              v-if="!sedangLogin"
              :size="16"
              :stroke-width="2.5"
            />

            <span v-else class="loading-spinner"></span>
          </button>
        </form>
      </section>
    </main>

    <footer class="login-footer">
      <div class="footer-inner">
        <p class="copyright">
          © 2025 Pemerintah Kota Banjarmasin. Seluruh hak cipta dilindungi.
          <br />
          Sistem Layanan Pengaduan & Aspirasi Publik Teman Baik
        </p>

        <nav class="footer-links" aria-label="Tautan informasi">
          <a href="#" @click.prevent="informasi('Hubungi Diskominfotik')">
            Hubungi Diskominfotik
          </a>

          <a href="#" @click.prevent="informasi('Ketentuan Layanan')">
            Ketentuan Layanan
          </a>

          <a href="#" @click.prevent="informasi('Kebijakan Privasi')">
            Kebijakan Privasi
          </a>

          <a href="#" @click.prevent="informasi('Portal Pemko Banjarmasin')">
            Portal Pemko Banjarmasin
          </a>
        </nav>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'

import {
  ShieldCheck,
  Check,
  ContactRound,
  UserRound,
  ArrowRight,
} from 'lucide-vue-next'

import api, {
  ambilCookieCSRF,
  pesanError,
  statusSesi,
} from '../services/api'

const router = useRouter()

const nik = ref('')
const nama = ref('')
const nikError = ref('')
const namaError = ref('')
const pesan = ref('')
const jenisPesan = ref('error')
const sedangLogin = ref(false)

function validasiNik() {
  nik.value = nik.value.replace(/\D/g, '').slice(0, 16)
  nikError.value = ''
  pesan.value = ''
}

async function masuk() {
  nikError.value = ''
  namaError.value = ''
  pesan.value = ''

  // Validasi NIK (backend: required|digits:16)
  if (!/^\d{16}$/.test(nik.value)) {
    nikError.value = 'NIK harus terdiri dari 16 digit angka.'
    return
  }

  // Validasi nama
  if (!nama.value.trim()) {
    namaError.value = 'Nama lengkap wajib diisi.'
    return
  }

  sedangLogin.value = true

  try {
    // 1. Ambil cookie CSRF (URL ini tanpa prefix /api).
    await ambilCookieCSRF()

    // 2. POST /api/pengadu/login
    //    Backend memakai session cookie, jadi TIDAK ada token yang perlu disimpan.
    //    Respons sukses: { pengadu: { id, nama } }
    const { data } = await api.post('/pengadu/login', {
      nik: nik.value,
      nama: nama.value.trim(),
    })

    // Simpan data tampilan (bukan kredensial) selama sesi browser
    sessionStorage.setItem(
      'temanBaikPengguna',
      JSON.stringify(data.pengadu),
    )

    // Beri tahu router guard bahwa sesi sudah aktif
    statusSesi.sudahDicek = true
    statusSesi.aktif = true

    jenisPesan.value = 'sukses'
    pesan.value = 'Login berhasil. Mengalihkan ke beranda...'

    await router.push('/beranda')
  } catch (error) {
    console.error('Kesalahan login:', error.response?.data || error)

    const errors = error.response?.data?.errors

    if (errors?.nik?.length) nikError.value = errors.nik[0]
    if (errors?.nama?.length) namaError.value = errors.nama[0]

    // 422: NIK/nama tidak cocok, 429: terlalu banyak percobaan (diblok 12 jam)
    pesan.value = pesanError(error, 'Login gagal')
    jenisPesan.value = 'error'
  } finally {
    sedangLogin.value = false
  }
}

function informasi(judul) {
  pesan.value = `${judul} belum tersedia pada versi frontend ini.`
  jenisPesan.value = 'error'
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #fff0ef;
  color: #382d2e;
}

.login-header {
  min-height: 36px;
  padding: 0 6%;
  display: flex;
  align-items: center;
  background: #fffafa;
  border-bottom: 1px solid #f9e9e8;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #b51220;
  text-decoration: none;
}

.brand-logo {
  width: 20px;
  height: 20px;
  display: grid;
  place-items: center;
  border-radius: 5px;
  background: #b51220;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
}

.brand-name {
  font-size: 13px;
  font-weight: 500;
}

.login-main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px 16px;
  background:
    radial-gradient(
      ellipse at 20% 40%,
      rgb(255 226 225 / 48%),
      transparent 45%
    ),
    radial-gradient(
      ellipse at 80% 70%,
      rgb(255 255 255 / 55%),
      transparent 48%
    ),
    #fff0ef;
}

.login-card {
  width: 100%;
  max-width: 510px;
  padding: 36px 30px 42px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 8px 24px rgb(101 31 31 / 4%);
}

.service-icon {
  position: relative;
  width: 58px;
  height: 58px;
  margin: 0 auto 12px;
  display: grid;
  place-items: center;
  border: 5px solid #ffe8e8;
  border-radius: 50%;
  background: #b51220;
  color: white;
}

.service-badge {
  position: absolute;
  right: -5px;
  bottom: -3px;
  width: 18px;
  height: 18px;
  display: grid;
  place-items: center;
  border: 2px solid white;
  border-radius: 50%;
  background: #b51220;
  color: white;
}

.service-caption {
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: #b51220;
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
}

.caption-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #b51220;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.form-group {
  min-width: 0;
}

.label-row {
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}

.label-row label {
  color: #493d3e;
  font-size: 15px;
  font-weight: 500;
}

.label-row > span {
  color: #8d7d7e;
  font-size: 12px;
  white-space: nowrap;
}

.input-wrapper {
  min-height: 60px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid transparent;
  border-radius: 8px;
  background: #fff0ef;
  transition: border-color 0.2s, background 0.2s;
}

.input-wrapper:focus-within {
  border-color: #d88c92;
  background: #fff8f7;
}

.input-wrapper.invalid {
  border-color: #c62835;
}

.input-icon {
  flex-shrink: 0;
  color: #786466;
}

.input-wrapper input {
  width: 100%;
  min-width: 0;
  padding: 0;
  border: 0;
  outline: none;
  background: transparent;
  color: #493d3e;
  font-size: 14px;
}

.input-wrapper input::placeholder {
  color: #9c8d8e;
  opacity: 1;
}

.input-wrapper input:disabled {
  opacity: 0.65;
}

.error-message,
.form-message {
  margin: 6px 0 0;
  color: #b51220;
  font-size: 12px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.success-message {
  color: #237a43;
}

.login-button {
  width: 100%;
  min-height: 58px;
  margin-top: 2px;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  border: none;
  border-radius: 30px;
  background: #b51220;
  color: white;
  box-shadow: 0 3px 5px rgb(165 14 27 / 16%);
  font-size: 16px;
  font-weight: 600;
  transition: background 0.2s, transform 0.2s;
  cursor: pointer;
}

.login-button:hover:not(:disabled) {
  background: #9f0d1a;
}

.login-button:active:not(:disabled) {
  transform: scale(0.98);
}

.login-button:focus-visible {
  outline: 2px solid #b51220;
  outline-offset: 3px;
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgb(255 255 255 / 40%);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.login-footer {
  min-height: 70px;
  padding: 18px 6%;
  display: flex;
  align-items: center;
  background: #ffe5e3;
}

.footer-inner {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.copyright {
  margin: 0;
  color: #79696a;
  font-size: 12px;
  line-height: 1.8;
}

.footer-links {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 16px;
}

.footer-links a {
  color: #645455;
  font-size: 12px;
  text-decoration: none;
}

.footer-links a:hover {
  color: #b51220;
}

@media (max-width: 600px) {
  .login-header {
    min-height: 48px;
    padding-inline: 20px;
  }

  .login-main {
    padding-block: 28px;
  }

  .login-card {
    max-width: 400px;
    padding: 28px 22px 30px;
  }

  .service-caption {
    font-size: 9px;
    margin-bottom: 24px;
  }

  .login-form {
    gap: 18px;
  }

  .label-row label {
    font-size: 12px;
  }

  .label-row > span {
    font-size: 10px;
  }

  .input-wrapper {
    min-height: 48px;
    padding-inline: 11px;
  }

  .input-wrapper input {
    font-size: 12px;
  }

  .login-button {
    min-height: 48px;
    font-size: 14px;
  }

  .login-footer {
    padding: 18px 20px;
  }

  .footer-inner {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .copyright,
  .footer-links a {
    font-size: 10px;
  }

  .footer-links {
    justify-content: flex-start;
    gap: 10px 14px;
  }
}
</style>
