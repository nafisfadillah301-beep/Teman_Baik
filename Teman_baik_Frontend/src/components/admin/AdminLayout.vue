<template>
  <div class="adm-root">
    <!-- ================= SIDEBAR ================= -->
    <aside class="adm-sidebar" :class="{ terbuka: menuTerbuka }">
      <div class="adm-brand">
        <span class="adm-brand-logo"><Landmark :size="16" /></span>

        <div class="adm-brand-text">
          <strong>Teman Baik</strong>
          <small>Kota Banjarmasin</small>
        </div>

        <span class="adm-pill-red">{{ labelPeran }}</span>
      </div>

      <p class="adm-menu-label">MENU UTAMA</p>

      <nav class="adm-nav" @click="menuTerbuka = false">
        <!-- ===== Super Admin ===== -->
        <template v-if="!instansi">
          <RouterLink
            :to="{ name: 'admin-dashboard' }"
            class="adm-nav-item"
            :class="{ aktif: route.name === 'admin-dashboard' }"
          >
            <LayoutDashboard :size="15" />
            Ringkasan &amp; Dashboard
          </RouterLink>

          <RouterLink
            :to="{ name: 'admin-rekap' }"
            class="adm-nav-item"
            :class="{ aktif: route.name === 'admin-rekap' }"
          >
            <ListFilter :size="15" />
            Filter &amp; Rekap Pengaduan
          </RouterLink>

          <RouterLink
            :to="{ name: 'admin-akun' }"
            class="adm-nav-item"
            :class="{
              aktif: ['admin-akun', 'admin-profil'].includes(route.name),
            }"
          >
            <UserCog :size="15" />
            Kelola Akun Admin
          </RouterLink>
        </template>

        <!-- ===== Admin Instansi ===== -->
        <template v-else>
          <RouterLink
            :to="{ name: 'instansi-dashboard' }"
            class="adm-nav-item"
            :class="{ aktif: route.name === 'instansi-dashboard' }"
          >
            <Inbox :size="15" />
            Laporan Masuk
          </RouterLink>

          <RouterLink
            :to="{ name: 'instansi-profil' }"
            class="adm-nav-item"
            :class="{ aktif: route.name === 'instansi-profil' }"
          >
            <UserCog :size="15" />
            Profil &amp; Akun
          </RouterLink>
        </template>
      </nav>

      <div class="adm-sidebar-user">
        <span class="adm-avatar adm-avatar-sm">{{ inisialUser }}</span>

        <div class="adm-sidebar-user-text">
          <strong>{{ namaUser }}</strong>
          <small>{{ subPeran }}</small>
        </div>

        <button
          type="button"
          class="adm-icon-btn"
          title="Keluar"
          aria-label="Keluar"
          @click="keluar"
        >
          <LogOut :size="15" />
        </button>
      </div>
    </aside>

    <div
      v-if="menuTerbuka"
      class="adm-scrim"
      @click="menuTerbuka = false"
    ></div>

    <!-- ================= KONTEN ================= -->
    <div class="adm-main">
      <header class="adm-topbar">
        <button
          type="button"
          class="adm-icon-btn adm-hamburger"
          aria-label="Menu"
          @click="menuTerbuka = !menuTerbuka"
        >
          <Menu :size="18" />
        </button>

        <form class="adm-search" @submit.prevent="cari">
          <Search :size="14" />
          <input
            v-model.trim="kataKunci"
            type="text"
            placeholder="Cari nomor tiket atau judul laporan..."
            aria-label="Cari laporan"
          />
        </form>

        <span class="adm-sistem" :class="{ gagal: galatSistem }">
          <span class="adm-sistem-dot"></span>
          {{ statusSistem }}
        </span>

        <div ref="akarBell" class="adm-bell-wrap">
          <button
            type="button"
            class="adm-icon-btn"
            aria-label="Notifikasi"
            @click="bellTerbuka = !bellTerbuka"
          >
            <Bell :size="17" />
            <span v-if="jumlahMenunggu > 0" class="adm-bell-dot"></span>
          </button>

          <div v-if="bellTerbuka" class="adm-popover">
            <strong>Notifikasi</strong>

            <template v-if="jumlahMenunggu > 0">
              <p>
                <b>{{ angka(jumlahMenunggu) }}</b> {{ teksBell }}
              </p>

              <button
                type="button"
                class="adm-btn adm-btn-primary adm-btn-sm"
                @click="lihatMenunggu"
              >
                Lihat laporan
              </button>
            </template>

            <p v-else>{{ teksBellKosong }}</p>
          </div>
        </div>

        <RouterLink
          :to="{ name: instansi ? 'instansi-profil' : 'admin-profil' }"
          class="adm-profile"
        >
          <span class="adm-avatar">{{ inisialUser }}</span>

          <span class="adm-profile-text">
            <strong>{{ namaUser }}</strong>
            <small>{{ levelPeran }}</small>
          </span>
        </RouterLink>
      </header>

      <main class="adm-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import {
  Bell,
  Inbox,
  LayoutDashboard,
  Landmark,
  ListFilter,
  LogOut,
  Menu,
  Search,
  UserCog,
} from 'lucide-vue-next'

import { keluarAdmin, statusSesiAdmin } from '../../services/api'
import {
  angka,
  hitungRingkasan,
  inisial,
  muatRingkasan,
  ringkasan,
} from '../../services/admin'
import {
  muatRingkasanInstansi,
  namaInstansi,
  ringkasanInstansi,
} from '../../services/instansi'

const route = useRoute()
const router = useRouter()

const menuTerbuka = ref(false)
const bellTerbuka = ref(false)
const akarBell = ref(null)
const kataKunci = ref(String(route.query.q || ''))

const user = statusSesiAdmin.user || {}
const instansi = user.role === 'admin_instansi'
const namaUser = user.nama || (instansi ? 'Admin Instansi' : 'Super Admin')
const inisialUser = inisial(namaUser)
const namaInstansiAktif = ref('')

const labelPeran = instansi ? 'ADMIN INSTANSI' : 'SUPER ADMIN'
const levelPeran = instansi ? 'ADMIN OPD' : 'LEVEL 1 ROOT'

const subPeran = computed(() =>
  instansi
    ? namaInstansiAktif.value || 'Admin Instansi'
    : 'Root Admin Level 1',
)

const jumlahMenunggu = computed(() =>
  instansi
    ? ringkasanInstansi.data?.menunggu_balasan || 0
    : hitungRingkasan(ringkasan.data).diterima,
)

const teksBell = instansi
  ? 'laporan menunggu balasan dari instansi Anda.'
  : 'laporan baru menunggu verifikasi.'

const teksBellKosong = instansi
  ? 'Tidak ada laporan yang menunggu balasan.'
  : 'Tidak ada laporan yang menunggu verifikasi.'

const galatSistem = computed(() =>
  instansi ? ringkasanInstansi.error : ringkasan.error,
)

const statusSistem = computed(() => {
  if (galatSistem.value) return 'Server Tidak Terhubung'

  if (instansi ? ringkasanInstansi.data : ringkasan.data) {
    return 'Sistem Operasional Normal'
  }

  return 'Menghubungkan...'
})

watch(
  () => route.query.q,
  (q) => {
    kataKunci.value = String(q || '')
  },
)

function cari() {
  router.push({
    name: instansi ? 'instansi-dashboard' : 'admin-rekap',
    query: kataKunci.value ? { q: kataKunci.value } : {},
  })
}

function lihatMenunggu() {
  bellTerbuka.value = false
  router.push(
    instansi
      ? { name: 'instansi-dashboard', query: { status: 'dikirim' } }
      : { name: 'admin-rekap', query: { status: 'diterima' } },
  )
}

async function keluar() {
  await keluarAdmin()

  router.push('/admin/login')
}

function klikDiLuar(event) {
  if (
    bellTerbuka.value &&
    akarBell.value &&
    !akarBell.value.contains(event.target)
  ) {
    bellTerbuka.value = false
  }
}

onMounted(async () => {
  if (instansi) {
    muatRingkasanInstansi()
    namaInstansiAktif.value = await namaInstansi(user.id_instansi)
  } else if (!ringkasan.data) {
    muatRingkasan()
  }

  document.addEventListener('click', klikDiLuar)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', klikDiLuar)
})
</script>

<!--
  Gaya bersama seluruh halaman admin (tidak scoped, semua kelas diawali
  "adm-" agar tidak bentrok dengan halaman masyarakat).
-->
<style>
.adm-root {
  --adm-red: #b51220;
  --adm-red-dark: #960e1a;
  --adm-pink: #fdeeee;
  --adm-pink-soft: #fff6f6;
  --adm-border: #f1e3e3;
  --adm-text: #2b1f20;
  --adm-muted: #7d6e6f;
  --adm-green: #1c8a4e;
  --adm-green-bg: #e8f7ee;

  display: flex;
  min-height: 100vh;
  background: #fffafa;
  color: var(--adm-text);
  font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system,
    'Segoe UI', Roboto, Arial, sans-serif;
  font-size: 13px;
}

.adm-root *,
.adm-root *::before,
.adm-root *::after {
  box-sizing: border-box;
}

.adm-root h1,
.adm-root h2,
.adm-root h3,
.adm-root p {
  margin: 0;
}

.adm-root button {
  font-family: inherit;
}

/* ---------- sidebar ---------- */
.adm-sidebar {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  flex-direction: column;
  width: 232px;
  height: 100vh;
  flex-shrink: 0;
  padding: 16px 12px;
  border-right: 1px solid var(--adm-border);
  background: #fff;
}

.adm-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 4px 16px;
}

.adm-brand-logo {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--adm-red);
  color: #fff;
}

.adm-brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
}

.adm-brand-text strong {
  font-size: 12px;
}

.adm-brand-text small {
  color: var(--adm-muted);
  font-size: 9px;
}

.adm-pill-red {
  margin-left: auto;
  padding: 3px 7px;
  border-radius: 99px;
  background: var(--adm-red);
  color: #fff;
  font-size: 7.5px;
  font-weight: 800;
  letter-spacing: 0.4px;
}

.adm-menu-label {
  margin: 6px 6px 8px;
  color: #a79a9b;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.6px;
}

.adm-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.adm-nav-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 11px;
  border-radius: 9px;
  color: #4d3f40;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s;
}

.adm-nav-item:hover {
  background: var(--adm-pink-soft);
}

.adm-nav-item.aktif {
  background: var(--adm-red);
  color: #fff;
}

.adm-sidebar-user {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-top: auto;
  padding: 11px 8px;
  border-top: 1px solid var(--adm-border);
}

.adm-sidebar-user-text {
  display: flex;
  min-width: 0;
  flex-direction: column;
  line-height: 1.2;
}

.adm-sidebar-user-text strong {
  overflow: hidden;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.adm-sidebar-user-text small {
  color: var(--adm-muted);
  font-size: 9px;
}

.adm-sidebar-user .adm-icon-btn {
  margin-left: auto;
}

/* ---------- topbar ---------- */
.adm-main {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.adm-topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  gap: 14px;
  height: 62px;
  padding: 0 28px;
  border-bottom: 1px solid var(--adm-border);
  background: #fff;
}

.adm-search {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  max-width: 330px;
  padding: 0 13px;
  height: 34px;
  border-radius: 99px;
  background: var(--adm-pink-soft);
  color: var(--adm-muted);
}

.adm-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--adm-text);
  font-size: 11px;
}

.adm-sistem {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
  padding: 5px 11px;
  border-radius: 99px;
  background: var(--adm-green-bg);
  color: var(--adm-green);
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}

.adm-sistem.gagal {
  background: #fdeaea;
  color: var(--adm-red);
}

.adm-sistem-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.adm-icon-btn {
  position: relative;
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #5c4d4e;
  cursor: pointer;
}

.adm-icon-btn:hover {
  background: var(--adm-pink-soft);
}

.adm-bell-wrap {
  position: relative;
}

.adm-bell-dot {
  position: absolute;
  top: 5px;
  right: 6px;
  width: 7px;
  height: 7px;
  border: 1.5px solid #fff;
  border-radius: 50%;
  background: var(--adm-red);
}

.adm-popover {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 60;
  display: flex;
  width: 250px;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 14px;
  border: 1px solid var(--adm-border);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 14px 34px rgba(60, 20, 20, 0.14);
  font-size: 12px;
}

.adm-popover p {
  color: #5c4d4e;
  line-height: 1.5;
}

.adm-profile {
  display: flex;
  align-items: center;
  gap: 9px;
  color: inherit;
  text-decoration: none;
}

.adm-profile-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.adm-profile-text strong {
  font-size: 11px;
}

.adm-profile-text small {
  color: var(--adm-red);
  font-size: 8px;
  font-weight: 700;
}

.adm-avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--adm-red);
  color: #fff;
  font-size: 11px;
  font-weight: 800;
}

.adm-avatar-sm {
  width: 28px;
  height: 28px;
  font-size: 10px;
}

.adm-hamburger,
.adm-scrim {
  display: none;
}

/* ---------- konten ---------- */
.adm-content {
  width: 100%;
  max-width: 1240px;
  margin: 0 auto;
  padding: 26px 28px 44px;
}

.adm-breadcrumb {
  margin-bottom: 8px;
  color: #a79a9b;
  font-size: 9.5px;
}

.adm-breadcrumb b {
  color: var(--adm-red);
  font-weight: 600;
}

.adm-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 20px;
}

.adm-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.adm-title {
  font-size: 22px;
  font-weight: 800;
  letter-spacing: -0.3px;
}

.adm-title.bar {
  padding-left: 10px;
  border-left: 4px solid var(--adm-red);
  line-height: 1.15;
}

.adm-subtitle {
  max-width: 560px;
  margin-top: 6px;
  color: var(--adm-muted);
  font-size: 11px;
  line-height: 1.55;
}

.adm-head-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.adm-live {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 99px;
  background: var(--adm-green-bg);
  color: var(--adm-green);
  font-size: 9px;
  font-weight: 700;
}

.adm-live::before {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
  content: '';
}

.adm-live-red {
  background: #fdeaea;
  color: var(--adm-red);
}

/* ---------- tombol ---------- */
.adm-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 15px;
  border: 1px solid var(--adm-border);
  border-radius: 99px;
  background: #fff;
  color: var(--adm-text);
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
  transition: background 0.15s, border-color 0.15s;
}

.adm-btn:hover:not(:disabled) {
  border-color: #e2c7c7;
  background: var(--adm-pink-soft);
}

.adm-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.adm-btn-primary {
  border-color: var(--adm-red);
  background: var(--adm-red);
  color: #fff;
}

.adm-btn-primary:hover:not(:disabled) {
  border-color: var(--adm-red-dark);
  background: var(--adm-red-dark);
}

.adm-btn-danger {
  border-color: #efc4c4;
  color: var(--adm-red);
}

.adm-btn-sm {
  padding: 6px 12px;
  font-size: 10px;
}

/* ---------- kartu & KPI ---------- */
.adm-card {
  border: 1px solid var(--adm-border);
  border-radius: 14px;
  background: #fff;
}

.adm-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 20px 12px;
}

.adm-card-head h2 {
  font-size: 14px;
  font-weight: 800;
}

.adm-card-head p {
  margin-top: 3px;
  color: var(--adm-muted);
  font-size: 10px;
}

.adm-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}

.adm-kpis.tiga {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.adm-kpi {
  padding: 15px 17px;
  border: 1px solid var(--adm-border);
  border-radius: 14px;
  background: #fff;
}

.adm-kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  color: var(--adm-muted);
  font-size: 9.5px;
  font-weight: 600;
}

.adm-kpi-icon {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: var(--adm-pink);
  color: var(--adm-red);
}

.adm-kpi-value {
  margin-top: 8px;
  font-size: 25px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.adm-kpi-value small {
  margin-left: 3px;
  color: var(--adm-muted);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0;
}

.adm-kpi-sub {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 6px;
  color: var(--adm-muted);
  font-size: 9px;
}

.adm-kpi-sub .merah {
  color: var(--adm-red);
  font-weight: 700;
}

.adm-kpi-sub .hijau {
  color: var(--adm-green);
  font-weight: 700;
}

/* ---------- filter ---------- */
.adm-filters {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin: 0 20px 16px;
  padding: 14px;
  border-radius: 12px;
  background: var(--adm-pink-soft);
}

.adm-field {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 5px;
}

.adm-field label {
  color: #6b5c5d;
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.adm-input,
.adm-select,
.adm-textarea {
  width: 100%;
  padding: 9px 11px;
  border: 1px solid var(--adm-border);
  border-radius: 9px;
  background: #fff;
  color: var(--adm-text);
  font-family: inherit;
  font-size: 11.5px;
  outline: none;
}

.adm-textarea {
  min-height: 84px;
  resize: vertical;
  line-height: 1.5;
}

.adm-input:focus,
.adm-select:focus,
.adm-textarea:focus {
  border-color: var(--adm-red);
  box-shadow: 0 0 0 3px rgba(181, 18, 32, 0.1);
}

.adm-field-error {
  color: var(--adm-red);
  font-size: 10px;
}

.adm-chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 7px;
  margin: 0 20px 14px;
  font-size: 10px;
  color: var(--adm-muted);
}

.adm-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 5px 3px 9px;
  border-radius: 99px;
  background: var(--adm-pink);
  color: var(--adm-red);
  font-weight: 700;
}

.adm-chip button {
  display: grid;
  place-items: center;
  width: 15px;
  height: 15px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.adm-link-btn {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: var(--adm-red);
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

/* ---------- tabel ---------- */
.adm-table-wrap {
  overflow-x: auto;
  margin: 0 20px;
}

.adm-table {
  width: 100%;
  min-width: 720px;
  border-collapse: collapse;
}

.adm-table th {
  padding: 10px 11px;
  background: var(--adm-pink);
  color: #7b4d50;
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-align: left;
  text-transform: uppercase;
  white-space: nowrap;
}

.adm-table th:first-child {
  border-radius: 9px 0 0 9px;
}

.adm-table th:last-child {
  border-radius: 0 9px 9px 0;
}

.adm-table td {
  padding: 12px 11px;
  border-bottom: 1px solid #f7eded;
  font-size: 11px;
  vertical-align: middle;
}

.adm-table tbody tr:last-child td {
  border-bottom: 0;
}

.adm-table tbody tr:hover td {
  background: #fffbfb;
}

.adm-cell-strong {
  display: block;
  font-weight: 800;
}

.adm-cell-muted {
  display: block;
  margin-top: 2px;
  color: var(--adm-muted);
  font-size: 9.5px;
}

.adm-cell-title {
  display: block;
  max-width: 300px;
  font-weight: 700;
  line-height: 1.4;
}

.adm-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.adm-table-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 14px 20px 18px;
  color: var(--adm-muted);
  font-size: 10px;
}

.adm-empty {
  padding: 34px 20px;
  color: var(--adm-muted);
  font-size: 12px;
  text-align: center;
}

.adm-empty.galat {
  color: var(--adm-red);
}

/* ---------- badge ---------- */
.adm-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 99px;
  font-size: 9.5px;
  font-weight: 800;
  white-space: nowrap;
}

.st-amber {
  background: #fff3df;
  color: #a96b0f;
}

.st-blue {
  background: #e6f0fd;
  color: #25579b;
}

.st-red {
  background: #fdeaea;
  color: var(--adm-red);
}

.st-green {
  background: var(--adm-green-bg);
  color: var(--adm-green);
}

.st-gray {
  background: #eeeaea;
  color: #5d5152;
}

.adm-tag {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 5px;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.4px;
}

.adm-tag.pengaduan {
  background: #fdeaea;
  color: var(--adm-red);
}

.adm-tag.informasi {
  background: #e6f0fd;
  color: #25579b;
}

.adm-tag.netral {
  background: #f1ecec;
  color: #6b5c5d;
}

/* ---------- pager ---------- */
.adm-pager {
  display: flex;
  align-items: center;
  gap: 5px;
}

.adm-pager button {
  display: grid;
  place-items: center;
  min-width: 26px;
  height: 26px;
  padding: 0 6px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #5c4d4e;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.adm-pager button:hover:not(:disabled) {
  background: var(--adm-pink);
}

.adm-pager button.aktif {
  background: var(--adm-red);
  color: #fff;
}

.adm-pager button:disabled {
  opacity: 0.4;
  cursor: default;
}

/* ---------- alert ---------- */
.adm-alert {
  margin: 0 20px 14px;
  padding: 10px 13px;
  border-radius: 10px;
  background: #fdeaea;
  color: #8f1420;
  font-size: 11px;
  line-height: 1.5;
}

.adm-alert.ok {
  background: var(--adm-green-bg);
  color: #17693c;
}

.adm-alert.pad {
  margin: 0 0 14px;
}

/* ---------- modal ---------- */
.adm-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  overflow-y: auto;
  padding: 40px 16px;
  background: rgba(43, 20, 22, 0.45);
}

.adm-modal {
  width: 100%;
  max-width: 640px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 24px 60px rgba(40, 10, 12, 0.3);
}

.adm-modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 22px 14px;
  border-bottom: 1px solid var(--adm-border);
}

.adm-modal-head h2 {
  font-size: 16px;
  font-weight: 800;
}

.adm-modal-body {
  padding: 18px 22px;
}

.adm-modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 14px 22px 20px;
  border-top: 1px solid var(--adm-border);
}

/* ---------- responsif ---------- */
@media (max-width: 1100px) {
  .adm-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .adm-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .adm-sidebar {
    position: fixed;
    left: 0;
    transform: translateX(-100%);
    transition: transform 0.2s;
  }

  .adm-sidebar.terbuka {
    transform: none;
    box-shadow: 8px 0 30px rgba(40, 10, 12, 0.18);
  }

  .adm-scrim {
    position: fixed;
    inset: 0;
    z-index: 35;
    display: block;
    background: rgba(43, 20, 22, 0.35);
  }

  .adm-hamburger {
    display: grid;
  }

  .adm-topbar {
    padding: 0 16px;
  }

  .adm-profile-text,
  .adm-sistem {
    display: none;
  }

  .adm-content {
    padding: 20px 16px 40px;
  }

  .adm-head {
    flex-direction: column;
  }
}

@media (max-width: 560px) {
  .adm-kpis,
  .adm-kpis.tiga,
  .adm-filters {
    grid-template-columns: 1fr;
  }
}
</style>
