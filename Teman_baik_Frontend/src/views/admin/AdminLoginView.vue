<template>
  <div class="login-admin">
    <header class="la-header">
      <RouterLink to="/admin/login" class="la-brand">
        <span class="la-brand-logo"><Landmark :size="16" /></span>
        <span class="la-brand-name">TEMAN<b>BAIK</b></span>
      </RouterLink>
    </header>

    <main class="la-main">
      <section class="la-card">
        <div class="la-icon">
          <ShieldCheck :size="26" />
          <span class="la-icon-badge"><Lock :size="9" /></span>
        </div>

        <span class="la-caption">
          <i></i> PORTAL KHUSUS APARATUR PEMKOT BANJARMASIN
        </span>

        <h1>Masuk Portal Admin</h1>

        <form class="la-form" novalidate @submit.prevent="masuk">
          <div class="la-group">
            <label for="username">Username Kedinasan</label>

            <div class="la-input">
              <IdCard :size="14" />
              <input
                id="username"
                v-model.trim="username"
                type="text"
                autocomplete="username"
                placeholder="Masukkan username admin"
                :disabled="sedang"
                @input="pesan = ''"
              />
            </div>
          </div>

          <div class="la-group">
            <div class="la-label-row">
              <label for="password">Password</label>

              <a href="#" class="la-forgot" @click.prevent="lupaSandi">
                Lupa Kata Sandi?
              </a>
            </div>

            <div class="la-input">
              <KeyRound :size="14" />
              <input
                id="password"
                v-model="password"
                :type="lihatSandi ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Masukkan kata sandi kedinasan Anda"
                :disabled="sedang"
                @input="pesan = ''"
              />

              <button
                type="button"
                class="la-eye"
                :aria-label="lihatSandi ? 'Sembunyikan' : 'Tampilkan'"
                @click="lihatSandi = !lihatSandi"
              >
                <EyeOff v-if="lihatSandi" :size="14" />
                <Eye v-else :size="14" />
              </button>
            </div>
          </div>

          <p v-if="pesan" class="la-message" :class="jenisPesan" role="alert">
            {{ pesan }}
          </p>

          <button type="submit" class="la-submit" :disabled="sedang">
            <span>{{ sedang ? 'Memverifikasi...' : 'Masuk ke Sistem Kedinasan' }}</span>
            <ArrowRight v-if="!sedang" :size="14" />
            <span v-else class="la-spinner"></span>
          </button>
        </form>

        <div class="la-notice">
          <span class="la-notice-icon"><ShieldAlert :size="14" /></span>

          <div>
            <strong>Akses Terbatas &bull; Khusus Aparatur</strong>
            <p>
              Percobaan login dibatasi demi keamanan. Gunakan hanya
              perangkat dan akun dinas resmi.
            </p>
          </div>
        </div>
      </section>

      <div class="la-below">
        <RouterLink to="/" class="la-back">
          <ArrowLeft :size="12" />
          Kembali ke Portal Publik Warga
        </RouterLink>

        <span class="la-help">
          <i></i> Helpdesk TI: <b>Ext. 204 / Diskominfo</b>
        </span>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import {
  ArrowLeft,
  ArrowRight,
  Eye,
  EyeOff,
  IdCard,
  KeyRound,
  Landmark,
  Lock,
  ShieldAlert,
  ShieldCheck,
} from 'lucide-vue-next'

import api, {
  PERAN_ADMIN,
  ambilCookieCSRF,
  berandaPeran,
  keluarAdmin,
  pesanError,
  statusSesiAdmin,
} from '../../services/api'

const router = useRouter()

const username = ref('')
const password = ref('')
const lihatSandi = ref(false)
const sedang = ref(false)
const pesan = ref('')
const jenisPesan = ref('error')

function lupaSandi() {
  jenisPesan.value = 'info'
  pesan.value =
    'Reset kata sandi dilakukan melalui Helpdesk TI Diskominfo (Ext. 204).'
}

async function masuk() {
  pesan.value = ''
  jenisPesan.value = 'error'

  if (!username.value || !password.value) {
    pesan.value = 'Username dan password wajib diisi.'
    return
  }

  sedang.value = true

  try {
    // 1. Cookie CSRF (tanpa prefix /api)
    await ambilCookieCSRF()

    // 2. POST /api/admin/login -> { user: { id, nama, username, email, role, id_instansi } }
    const { data } = await api.post('/admin/login', {
      username: username.value,
      password: password.value,
    })

    // Portal ini melayani Super Admin dan Admin Instansi
    if (!PERAN_ADMIN.includes(data.user?.role)) {
      await keluarAdmin()

      pesan.value = 'Akun ini tidak memiliki akses ke portal admin.'
      return
    }

    // Admin instansi wajib terhubung ke satu instansi
    if (data.user.role === 'admin_instansi' && !data.user.id_instansi) {
      await keluarAdmin()

      pesan.value =
        'Akun ini belum terhubung ke instansi. Hubungi Super Admin.'
      return
    }

    statusSesiAdmin.sudahDicek = true
    statusSesiAdmin.aktif = true
    statusSesiAdmin.user = data.user

    // Super Admin -> /admin, Admin Instansi -> /instansi
    await router.push(berandaPeran(data.user.role))
  } catch (error) {
    console.error('Login admin gagal:', error.response?.data || error)

    // Backend membatasi 3 percobaan per 5 menit
    pesan.value =
      error.response?.status === 429
        ? 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'
        : pesanError(error, 'Login gagal')
  } finally {
    sedang.value = false
  }
}
</script>

<style scoped>
.login-admin {
  display: flex;
  min-height: 100vh;
  flex-direction: column;
  background:
    radial-gradient(900px 500px at 85% 0%, #fde8e8 0%, transparent 60%),
    radial-gradient(800px 500px at 0% 100%, #fff0f0 0%, transparent 60%),
    #fffafa;
  color: #2b1f20;
  font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system,
    'Segoe UI', Roboto, Arial, sans-serif;
}

.la-header {
  display: flex;
  align-items: center;
  height: 62px;
  padding: 0 60px;
  border-bottom: 1px solid #f3e6e6;
  background: rgba(255, 255, 255, 0.8);
}

.la-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  color: inherit;
  text-decoration: none;
}

.la-brand-logo {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #b51220;
  color: #fff;
}

.la-brand-name {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: -0.2px;
}

.la-brand-name b {
  color: #b51220;
}

.la-main {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 22px;
  padding: 36px 16px;
}

.la-card {
  display: flex;
  width: 100%;
  max-width: 400px;
  flex-direction: column;
  align-items: center;
  padding: 26px 30px 28px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 10px 40px rgba(120, 30, 36, 0.1);
}

.la-icon {
  position: relative;
  display: grid;
  place-items: center;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #fde9e9;
  color: #c9444f;
}

.la-icon-badge {
  position: absolute;
  right: 0;
  bottom: 2px;
  display: grid;
  place-items: center;
  width: 18px;
  height: 18px;
  border: 2px solid #fff;
  border-radius: 50%;
  background: #b51220;
  color: #fff;
}

.la-caption {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 4px 11px;
  border-radius: 99px;
  background: #fdeeee;
  color: #b51220;
  font-size: 7.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.la-caption i {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #b51220;
}

.la-card h1 {
  margin: 12px 0 20px;
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.4px;
}

.la-form {
  display: flex;
  width: 100%;
  flex-direction: column;
  gap: 14px;
}

.la-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.la-group label {
  font-size: 10px;
  font-weight: 700;
}

.la-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.la-forgot {
  color: #b51220;
  font-size: 9px;
  font-weight: 700;
  text-decoration: none;
}

.la-input {
  display: flex;
  align-items: center;
  gap: 9px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid transparent;
  border-radius: 9px;
  background: #fdf0f0;
  color: #8a7a7b;
}

.la-input:focus-within {
  border-color: #b51220;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(181, 18, 32, 0.1);
}

.la-input input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #2b1f20;
  font-size: 11px;
}

.la-eye {
  display: grid;
  place-items: center;
  padding: 2px;
  border: 0;
  background: transparent;
  color: #8a7a7b;
  cursor: pointer;
}

.la-message {
  margin: 0;
  padding: 9px 12px;
  border-radius: 9px;
  background: #fdeaea;
  color: #8f1420;
  font-size: 10.5px;
  line-height: 1.5;
}

.la-message.info {
  background: #eef4fd;
  color: #25579b;
}

.la-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 40px;
  margin-top: 2px;
  border: 0;
  border-radius: 99px;
  background: #b51220;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 6px 16px rgba(181, 18, 32, 0.28);
}

.la-submit:hover:not(:disabled) {
  background: #960e1a;
}

.la-submit:disabled {
  opacity: 0.75;
  cursor: wait;
}

.la-spinner {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: putar 0.7s linear infinite;
}

@keyframes putar {
  to {
    transform: rotate(360deg);
  }
}

.la-notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  width: 100%;
  margin-top: 18px;
  padding: 12px 13px;
  border-radius: 10px;
  background: #fdeeee;
}

.la-notice-icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #fff;
  color: #b51220;
}

.la-notice strong {
  font-size: 9px;
}

.la-notice p {
  margin: 3px 0 0;
  color: #6b5c5d;
  font-size: 8.5px;
  line-height: 1.55;
}

.la-below {
  display: flex;
  width: 100%;
  max-width: 400px;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #6b5c5d;
  font-size: 9px;
}

.la-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: inherit;
  font-weight: 600;
  text-decoration: none;
}

.la-help {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.la-help i {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #1c8a4e;
}

@media (max-width: 560px) {
  .la-header {
    padding: 0 18px;
  }

  .la-card {
    padding: 22px 20px;
  }

  .la-below {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
