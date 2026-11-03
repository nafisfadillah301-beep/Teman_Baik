
<template>
  <div class="cek-page">
    <!-- NAVBAR -->
    <header class="navbar">
      <div class="navbar-inner">
        <RouterLink to="/beranda" class="brand">
          <span class="brand-logo">B</span>
          <span>Teman Baik</span>
        </RouterLink>

        <nav class="nav-menu">
          <RouterLink to="/beranda">Beranda</RouterLink>
          <RouterLink to="/aduan">Layanan Laporan</RouterLink>
          <RouterLink to="/cek-laporan" class="active">
            Cek Laporan
          </RouterLink>

          <NotifikasiBell />

          <button class="logout-button" type="button" @click="keluar">
            ↪ Keluar
          </button>
        </nav>
      </div>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="main-content">
      <section class="search-section">
        <h1>Lacak Laporan Anda</h1>
        <p>
          Masukkan nomor tiket laporan Anda untuk melihat progres
          tindak lanjut dari instansi terkait.
        </p>

        <form class="search-form" @submit.prevent="cariLaporan">
          <span class="search-icon">⌕</span>

          <input
            v-model.trim="nomorInput"
            type="text"
            placeholder="Contoh: PGD-2026-0001A"
            aria-label="Nomor laporan"
            required
          />

          <button type="submit" :disabled="memuat">
            {{ memuat ? 'Mencari...' : 'Cari' }} <span>➜</span>
          </button>
        </form>

        <p v-if="pesan" class="search-message" :class="{ error: !laporan }">
          {{ pesan }}
        </p>
      </section>

      <!-- HASIL LAPORAN -->
      <section v-if="laporan" class="result-card">
        <div class="result-header">
          <div>
            <span class="eyebrow">STATUS LAPORAN</span>
            <h2>{{ laporan.nomor }}</h2>
          </div>

          <span class="status-badge" :class="statusClass">
            <span class="status-dot"></span>
            {{ laporan.status || 'Laporan Diterima' }}
          </span>
        </div>

        <div v-if="laporan.statusKode === 'ditolak'" class="rejected-note">
          <strong>Laporan tidak dapat ditindaklanjuti</strong>
          <p>{{ laporan.alasanTolak || 'Laporan ini ditolak oleh petugas.' }}</p>
        </div>

        <div v-else class="timeline">
          <div
            v-for="(tahap, index) in tahapan"
            :key="tahap.judul"
            class="timeline-step"
            :class="{
              completed: index < tahapAktif,
              current: index === tahapAktif,
              pending: index > tahapAktif,
            }"
          >
            <div class="step-icon">
              <span v-if="index < tahapAktif">✓</span>
              <span v-else>{{ tahap.nomor }}</span>
            </div>

            <div class="step-content">
              <strong>{{ tahap.judul }}</strong>
              <small>{{ tahap.keterangan }}</small>
            </div>

            <span v-if="index < tahapAktif" class="step-check">✓</span>
          </div>
        </div>

        <div class="report-details">
          <h3>Detail Laporan</h3>

          <div class="detail-row">
            <span>Judul</span>
            <strong>{{ laporan.judul }}</strong>
          </div>

          <div class="detail-row">
            <span>Jenis</span>
            <strong>{{ laporan.jenis }}</strong>
          </div>

          <div class="detail-row">
            <span>Instansi Tujuan</span>
            <strong>{{ laporan.instansi }}</strong>
          </div>

          <div class="detail-row">
            <span>Tanggal Pengiriman</span>
            <strong>{{ formatTanggal(laporan.tanggal) }}</strong>
          </div>

          <div class="detail-row">
            <span>Lokasi</span>
            <strong>{{ laporan.lokasi }}</strong>
          </div>
        </div>

        <div class="result-actions">
          <RouterLink
            :to="{
              path: '/detail-laporan',
              query: { nomor: laporan.nomor },
            }"
            class="detail-link"
          >
            Lihat Detail Laporan <span>➜</span>
          </RouterLink>

          <button class="reset-button" type="button" @click="resetPencarian">
            Cari Laporan Lain
          </button>
        </div>
      </section>

      <section v-else-if="sudahMencari" class="empty-result">
        <div class="empty-icon">⌕</div>
        <h2>Laporan Tidak Ditemukan</h2>
        <p>
          Periksa kembali nomor tiket yang dimasukkan. Pastikan nomor
          tersebut sesuai dengan nomor laporan yang diterima.
        </p>
      </section>

      <!-- LAPORAN SAYA (GET /api/pengaduan) -->
      <section v-if="laporanSaya.length" class="my-reports">
        <h2>Laporan Saya</h2>

        <button
          v-for="item in laporanSaya"
          :key="item.no_tiket"
          type="button"
          class="my-report-item"
          @click="pilihLaporan(item.no_tiket)"
        >
          <span class="my-report-ticket">{{ item.no_tiket }}</span>
          <span class="my-report-title">{{ item.judul }}</span>
          <span class="my-report-status">
            {{ labelStatus[item.status?.status] || item.status?.status }}
          </span>
        </button>
      </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-about">
          <div class="footer-brand">
            <span class="brand-logo">B</span>
            <strong>Teman Baik</strong>
          </div>

          <p>
            Layanan pengaduan publik yang modern, aman, dan transparan
            untuk mewujudkan akuntabilitas layanan pemerintah.
          </p>
        </div>

        <div class="footer-links">
          <strong>Tautan Cepat</strong>
          <RouterLink to="/beranda">Beranda</RouterLink>
          <RouterLink to="/aduan">Layanan Laporan</RouterLink>
          <RouterLink to="/cek-laporan">Cek Laporan</RouterLink>
        </div>

        <div class="footer-links">
          <strong>Informasi</strong>
          <a href="#privasi">Kebijakan Privasi</a>
          <a href="#syarat">Syarat &amp; Ketentuan</a>
          <a href="#bantuan">Bantuan</a>
        </div>
      </div>

      <div class="footer-bottom">
        © 2025 Pemerintah Kota Banjarmasin. Seluruh hak cipta dilindungi.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import api, { keluarDariSistem, pesanError } from '../services/api'
import NotifikasiBell from '../components/NotifikasiBell.vue'

const router = useRouter()
const route = useRoute()

const nomorInput = ref('')
const laporan = ref(null)
const laporanSaya = ref([])
const pesan = ref('')
const sudahMencari = ref(false)
const memuat = ref(false)

// Status dari backend: diterima, diverifikasi, dikirim, dijawab, ditolak
const labelStatus = {
  diterima: 'Laporan Diterima',
  diverifikasi: 'Diverifikasi',
  dikirim: 'Diproses',
  dijawab: 'Selesai',
  ditolak: 'Ditolak',
}

const tahapan = [
  {
    nomor: '1',
    judul: 'Laporan Diterima',
    keterangan: 'Laporan berhasil dikirim ke sistem.',
  },
  {
    nomor: '2',
    judul: 'Diverifikasi',
    keterangan: 'Laporan sedang diperiksa oleh petugas.',
  },
  {
    nomor: '3',
    judul: 'Diproses',
    keterangan: 'Laporan ditindaklanjuti oleh instansi.',
  },
  {
    nomor: '4',
    judul: 'Selesai',
    keterangan: 'Tindak lanjut laporan telah selesai.',
  },
]

// Indeks tahap yang sedang berjalan (4 = semua tahap selesai)
const tahapAktif = computed(() => {
  switch (laporan.value?.statusKode) {
    case 'dijawab':
      return 4
    case 'dikirim':
      return 2
    case 'diverifikasi':
      return 1
    default:
      return 0
  }
})

const statusClass = computed(() => {
  switch (laporan.value?.statusKode) {
    case 'dijawab':
      return 'status-done'
    case 'dikirim':
      return 'status-process'
    case 'diverifikasi':
      return 'status-verify'
    case 'ditolak':
      return 'status-rejected'
    default:
      return 'status-received'
  }
})

// GET /api/pengaduan/{no_tiket}  (hanya milik pengadu yang sedang login)
async function cariLaporan() {
  const nomor = nomorInput.value.trim().toUpperCase()

  if (!nomor) return

  sudahMencari.value = true
  memuat.value = true
  pesan.value = ''
  laporan.value = null

  try {
    const { data } = await api.get(
      `/pengaduan/${encodeURIComponent(nomor)}`,
    )

    // Alasan penolakan = catatan pada riwayat berstatus "ditolak"
    const riwayatTolak = (data.riwayat || []).find(
      (r) => r.status === 'ditolak',
    )

    laporan.value = {
      nomor: data.no_tiket,
      judul: data.judul,
      statusKode: data.status_saat_ini,
      status: labelStatus[data.status_saat_ini] || data.status_saat_ini,
      jenis: data.jenis === 'pengaduan' ? 'Pengaduan' : 'Informasi',
      instansi: data.instansi,
      tanggal: data.dibuat_pada,
      lokasi: data.alamat,
      alasanTolak: riwayatTolak?.catatan || '',
    }

    pesan.value = 'Laporan berhasil ditemukan.'
  } catch (error) {
    laporan.value = null

    pesan.value =
      error.response?.status === 404
        ? 'Nomor laporan tidak ditemukan.'
        : pesanError(error, 'Laporan gagal dimuat')
  } finally {
    memuat.value = false
  }
}

// GET /api/pengaduan -> daftar laporan milik pengadu yang login
async function muatLaporanSaya() {
  try {
    const { data } = await api.get('/pengaduan')

    laporanSaya.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Gagal memuat daftar laporan:', error)
  }
}

function pilihLaporan(nomor) {
  nomorInput.value = nomor
  cariLaporan()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function resetPencarian() {
  nomorInput.value = ''
  laporan.value = null
  pesan.value = ''
  sudahMencari.value = false
}

function formatTanggal(tanggal) {
  if (!tanggal) return '-'

  const date = new Date(tanggal)

  if (Number.isNaN(date.getTime())) return '-'

  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

async function keluar() {
  await keluarDariSistem()

  router.push('/')
}

onMounted(() => {
  muatLaporanSaya()

  // Dari halaman aduan / detail: ?nomor=PGD-2026-0001A
  const nomorDariUrl = route.query.nomor

  if (typeof nomorDariUrl === 'string' && nomorDariUrl) {
    nomorInput.value = nomorDariUrl
    cariLaporan()
  }
})
</script>

<style scoped>
.cek-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fff;
  color: #35292a;
  font-family: Arial, Helvetica, sans-serif;
}

.navbar {
  height: 65px;
  flex-shrink: 0;
  border-bottom: 1px solid #f0e8e8;
  background: #fff;
}

.navbar-inner {
  width: calc(100% - 100px);
  max-width: 1280px;
  height: 100%;
  margin: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand,
.footer-brand {
  display: flex;
  align-items: center;
  gap: 8px;
}

.brand {
  color: #b51220;
  text-decoration: none;
  font-size: 15px;
}

.brand-logo {
  width: 25px;
  height: 25px;
  display: grid;
  place-items: center;
  border-radius: 5px;
  background: #b51220;
  color: #fff;
  font-weight: 700;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 28px;
}

.nav-menu > a {
  color: #66595a;
  text-decoration: none;
  font-size: 11px;
}

.nav-menu > a.active,
.nav-menu > a:hover {
  color: #b51220;
}

.icon-button,
.logout-button {
  position: relative;
  border: 0;
  background: transparent;
  color: #66595a;
  cursor: pointer;
  font-size: 12px;
}

.notification-dot {
  position: absolute;
  top: 0;
  right: 0;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #b51220;
}

.logout-button {
  color: #b51220;
}

.main-content {
  width: calc(100% - 40px);
  max-width: 850px;
  flex: 1;
  margin: 0 auto;
  padding: 55px 0 70px;
}

.search-section {
  text-align: center;
}

.search-section h1 {
  margin: 0 0 12px;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 30px;
  color: #302526;
}

.search-section > p {
  max-width: 480px;
  margin: 0 auto;
  color: #897b7c;
  font-size: 12px;
  line-height: 1.8;
}

.search-form {
  max-width: 560px;
  height: 48px;
  margin: 25px auto 0;
  padding: 5px 5px 5px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #eee5e5;
  border-radius: 30px;
  background: #fff;
  box-shadow: 0 4px 15px #3c22220a;
}

.search-icon {
  color: #9c898a;
  font-size: 24px;
}

.search-form input {
  min-width: 0;
  flex: 1;
  border: 0;
  outline: 0;
  color: #433536;
  font-size: 12px;
}

.search-form button {
  height: 34px;
  min-width: 90px;
  padding: 0 17px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 0;
  border-radius: 25px;
  background: #b51220;
  color: #fff;
  font-size: 11px;
  cursor: pointer;
}

.search-message {
  margin-top: 12px !important;
  color: #27804b !important;
  font-size: 11px !important;
}

.search-message.error {
  color: #b51220 !important;
}

.result-card {
  margin: 45px auto 0;
  padding: 28px;
  border: 1px solid #eae4e4;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 4px 12px #2c16160b;
}

.result-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding-bottom: 22px;
  border-bottom: 1px solid #f1e9e9;
}

.eyebrow {
  color: #8d7e7f;
  font-size: 9px;
  letter-spacing: 1px;
}

.result-header h2 {
  margin: 8px 0 0;
  color: #302526;
  font-size: 19px;
}

.status-badge {
  padding: 9px 13px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border-radius: 20px;
  font-size: 10px;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-received {
  background: #fff3e6;
  color: #b8792a;
}

.status-verify {
  background: #fff4e2;
  color: #a97520;
}

.status-process {
  background: #fceced;
  color: #b51220;
}

.status-done {
  background: #e8f7ed;
  color: #25834b;
}

.timeline {
  margin: 30px 0;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.timeline-step {
  position: relative;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.timeline-step:not(:last-child)::after {
  position: absolute;
  top: 15px;
  left: 34px;
  right: -1px;
  height: 2px;
  background: #eee6e6;
  content: '';
}

.timeline-step.completed:not(:last-child)::after {
  background: #b51220;
}

.step-icon {
  z-index: 1;
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  border: 2px solid #eee6e6;
  border-radius: 50%;
  background: #fff;
  color: #9a8c8d;
  font-size: 11px;
}

.timeline-step.completed .step-icon,
.timeline-step.current .step-icon {
  border-color: #b51220;
  background: #b51220;
  color: #fff;
}

.timeline-step.current .step-icon {
  box-shadow: 0 0 0 4px #f9e7e9;
}

.step-content {
  padding: 13px 12px 0 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.step-content strong {
  color: #514445;
  font-size: 10px;
}

.step-content small {
  color: #968889;
  font-size: 9px;
  line-height: 1.6;
}

.timeline-step.pending .step-content strong {
  color: #a99d9e;
}

.step-check {
  display: none;
}

.report-details {
  padding-top: 22px;
  border-top: 1px solid #f1e9e9;
}

.report-details h3 {
  margin: 0 0 18px;
  font-size: 13px;
}

.detail-row {
  padding: 12px 0;
  display: flex;
  justify-content: space-between;
  gap: 20px;
  border-bottom: 1px solid #f6f1f1;
  font-size: 11px;
}

.detail-row > span {
  color: #8b7c7d;
}

.detail-row strong {
  max-width: 65%;
  color: #493b3c;
  font-weight: 500;
  text-align: right;
  overflow-wrap: anywhere;
}

.result-actions {
  margin-top: 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.detail-link {
  color: #b51220;
  text-decoration: none;
  font-size: 11px;
  font-weight: 600;
}

.reset-button {
  padding: 10px 15px;
  border: 1px solid #eadadb;
  border-radius: 20px;
  background: white;
  color: #6e5e5f;
  font-size: 10px;
  cursor: pointer;
}

.empty-result {
  max-width: 500px;
  margin: 35px auto 0;
  padding: 35px 25px;
  border: 1px solid #eee5e5;
  border-radius: 12px;
  text-align: center;
}

.empty-icon {
  width: 50px;
  height: 50px;
  margin: 0 auto 15px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #fff1f1;
  color: #b51220;
  font-size: 28px;
}

.empty-result h2 {
  font-size: 16px;
}

.empty-result p {
  color: #8b7c7d;
  font-size: 11px;
  line-height: 1.8;
}

.footer {
  margin-top: auto;
  border-top: 1px solid #f1eaea;
  background: #fff;
}

.footer-inner {
  width: calc(100% - 100px);
  max-width: 1280px;
  min-height: 120px;
  margin: auto;
  padding: 25px 0;
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 40px;
}

.footer-brand {
  color: #b51220;
  font-size: 11px;
}

.footer-brand .brand-logo {
  width: 19px;
  height: 19px;
  font-size: 11px;
}

.footer-about p {
  max-width: 260px;
  color: #837576;
  font-size: 9px;
  line-height: 1.8;
}

.footer-links {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 9px;
  font-size: 10px;
}

.footer-links a {
  color: #837576;
  text-decoration: none;
}

.footer-bottom {
  min-height: 35px;
  padding: 10px 50px;
  border-top: 1px solid #f3eded;
  color: #938586;
  font-size: 9px;
  text-align: right;
}

@media (max-width: 700px) {
  .navbar {
    height: auto;
    min-height: 60px;
  }

  .navbar-inner {
    width: calc(100% - 32px);
    padding: 12px 0;
    align-items: flex-start;
    gap: 15px;
    flex-direction: column;
  }

  .nav-menu {
    width: 100%;
    flex-wrap: wrap;
    gap: 14px;
  }

  .main-content {
    padding-top: 35px;
  }

  .search-section h1 {
    font-size: 26px;
  }

  .result-card {
    padding: 18px;
  }

  .result-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .timeline {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .timeline-step {
    min-height: 48px;
    flex-direction: row;
    align-items: flex-start;
    gap: 13px;
  }

  .timeline-step:not(:last-child)::after {
    top: 30px;
    bottom: -20px;
    left: 15px;
    right: auto;
    width: 2px;
    height: auto;
  }

  .step-content {
    padding-top: 3px;
  }

  .detail-row {
    flex-direction: column;
    gap: 6px;
  }

  .detail-row strong {
    max-width: 100%;
    text-align: left;
  }

  .result-actions {
    align-items: flex-start;
    flex-direction: column;
  }

  .footer-inner {
    width: calc(100% - 32px);
    grid-template-columns: 1fr 1fr;
    gap: 25px;
  }

  .footer-about {
    grid-column: 1 / -1;
  }

  .footer-bottom {
    padding-inline: 16px;
    text-align: left;
  }
}

/* ===== Tambahan untuk integrasi backend ===== */
.status-rejected {
  background: #fdecea;
  color: #a31515;
}

.rejected-note {
  margin: 30px 0;
  padding: 16px 18px;
  border: 1px solid #f3c9c9;
  border-radius: 10px;
  background: #fff6f6;
  color: #7a1c1c;
  font-size: 13px;
}

.rejected-note p {
  margin: 6px 0 0;
}

.search-form button:disabled {
  opacity: 0.7;
  cursor: wait;
}

.my-reports {
  max-width: 760px;
  margin: 45px auto 0;
}

.my-reports h2 {
  margin: 0 0 12px;
  font-size: 15px;
  color: #302526;
}

.my-report-item {
  display: grid;
  grid-template-columns: 150px 1fr auto;
  align-items: center;
  gap: 12px;
  width: 100%;
  margin-bottom: 8px;
  padding: 12px 14px;
  border: 1px solid #eae4e4;
  border-radius: 10px;
  background: #fff;
  color: #302526;
  font-size: 12px;
  text-align: left;
  cursor: pointer;
}

.my-report-item:hover {
  border-color: #b51220;
}

.my-report-ticket {
  font-weight: 700;
  color: #b51220;
}

.my-report-status {
  padding: 3px 10px;
  border-radius: 999px;
  background: #f6f1f1;
  font-size: 11px;
}

@media (max-width: 640px) {
  .my-report-item {
    grid-template-columns: 1fr;
    gap: 4px;
  }
}
</style>
