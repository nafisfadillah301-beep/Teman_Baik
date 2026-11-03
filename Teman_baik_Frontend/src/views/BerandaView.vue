
<template>
  <div class="home-page">
    <!-- NAVBAR -->
    <header class="navbar">
      <div class="navbar-inner">
        <RouterLink to="/beranda" class="brand">
          <span class="brand-logo">B</span>
          <span>Teman Baik</span>
        </RouterLink>

        <nav class="nav-menu">
          <RouterLink to="/beranda" class="nav-link active">
            Beranda
          </RouterLink>

          <RouterLink to="/aduan" class="nav-link">
            Layanan Laporan
          </RouterLink>

          <RouterLink to="/cek-laporan" class="nav-link">
            Cek Laporan
          </RouterLink>

          <NotifikasiBell />

          <button
            type="button"
            class="logout-button"
            @click="keluar"
          >
            <LogOut :size="15" />
            Keluar
          </button>
        </nav>
      </div>
    </header>

    <main>
      <!-- HERO DASHBOARD -->
      <section class="hero">
        <div class="hero-content">
          <span class="hero-label">
            PORTAL PELAYANAN PUBLIK BANJARMASIN
          </span>

          <h1>
            Teman Baik:<br />
            Sampaikan Laporan<br />
            Anda
          </h1>

          <p>
            Platform pengaduan resmi untuk pelayanan publik di Banjarmasin.
            Laporkan, pantau, dan bantu kami meningkatkan kualitas layanan
            bersama-sama dengan transparansi penuh.
          </p>

          <RouterLink to="/aduan" class="primary-button">
            Sampaikan Laporan
            <ArrowRight :size="16" />
          </RouterLink>
        </div>

        <!-- GAMBAR RATA KANAN -->
        <div class="hero-visual">
          <img
            src="/TEMAN_BAIK.jpeg"
            alt="Ilustrasi pelayanan Teman Baik"
            class="hero-image"
          />
        </div>
      </section>

      <!-- LAYANAN UTAMA -->
      <section class="services">
        <div class="section-heading">
          <h2>Layanan Utama Kami</h2>

          <p>
            Akses fasilitas penyampaian laporan dan permintaan tindak lanjut
            secara mudah, transparan, dan terpercaya.
          </p>

          <p v-if="memuatInstansi" class="api-info">
            Memuat data instansi...
          </p>

          <p v-else-if="errorInstansi" class="api-info api-error">
            {{ errorInstansi }}
          </p>

          <p v-else class="api-info">
            Tersedia {{ daftarInstansi.length }} instansi layanan.
          </p>
        </div>

        <div class="service-grid">
          <RouterLink to="/aduan" class="service-card">
            <div class="service-icon">
              <Megaphone :size="22" />
            </div>

            <h3>Sampaikan Laporan / Informasi</h3>

            <p>
              Sampaikan pengaduan layanan atau ajukan pertanyaan informasi
              sesuai kebutuhan Anda.
            </p>

            <span class="service-link">
              Mulai sekarang
              <ArrowRight :size="14" />
            </span>
          </RouterLink>

          <RouterLink to="/cek-laporan" class="service-card">
            <div class="service-icon">
              <Search :size="22" />
            </div>

            <h3>Cek Status Laporan</h3>

            <p>
              Pantau progres penanganan aduan dan jawaban informasi
              secara berkala.
            </p>

            <span class="service-link">
              Lacak laporan
              <ArrowRight :size="14" />
            </span>
          </RouterLink>
        </div>
      </section>

      <!-- STATISTIK -->
      <section class="statistics">
        <div class="stat-card">
          <strong>{{ angka(statistik.total_laporan) }}</strong>
          <span>TOTAL LAPORAN</span>
        </div>

        <div class="stat-card">
          <strong>{{ angka(statistik.laporan_diproses) }}</strong>
          <span>LAPORAN DIPROSES</span>
        </div>

        <div class="stat-card" :title="keteranganIkm">
          <strong>{{ statistik.ikm?.nilai ?? '-' }}</strong>
          <span>TINGKAT KEPUASAN</span>
        </div>
      </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-main">
        <div class="footer-about">
          <RouterLink to="/beranda" class="footer-brand">
            <span class="brand-logo">B</span>
            <strong>Teman Baik</strong>
          </RouterLink>

          <p>
            Layanan pengaduan publik yang modern, aman, dan transparan
            untuk mewujudkan akuntabilitas layanan pemerintah.
          </p>
        </div>

        <div class="footer-column">
          <h4>Tautan Cepat</h4>
          <RouterLink to="/beranda">Beranda</RouterLink>
          <RouterLink to="/aduan">Layanan Laporan</RouterLink>
          <RouterLink to="/cek-laporan">Cek Laporan</RouterLink>
        </div>

        <div class="footer-column">
          <h4>Informasi</h4>
          <a href="#privasi">Kebijakan Privasi</a>
          <a href="#syarat">Syarat &amp; Ketentuan</a>
          <a href="#bantuan">Bantuan</a>
        </div>
      </div>

      <div class="footer-bottom">
        <span>© 2025 Pemerintah Kota Banjarmasin</span>

        <span>
          Sistem Layanan Pengaduan &amp; Aspirasi Publik Teman Baik
        </span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'

import {
  ArrowRight,
  LogOut,
  Megaphone,
  Search,
} from 'lucide-vue-next'

import api, { keluarDariSistem } from '../services/api'
import NotifikasiBell from '../components/NotifikasiBell.vue'

const router = useRouter()

const daftarInstansi = ref([])
const memuatInstansi = ref(false)
const errorInstansi = ref('')

const statistik = ref({
  total_laporan: null,
  laporan_diproses: null,
  ikm: null,
})

const keteranganIkm = computed(() => {
  const ikm = statistik.value.ikm

  return ikm
    ? `IKM ${ikm.bulan ?? ''} ${ikm.tahun ?? ''}`.trim()
    : ''
})

// Format angka Indonesia
function angka(nilai) {
  return nilai === null || nilai === undefined
    ? '-'
    : Number(nilai).toLocaleString('id-ID')
}

// Mengambil data instansi
async function muatInstansi() {
  memuatInstansi.value = true
  errorInstansi.value = ''

  try {
    const { data } = await api.get('/instansi')
    daftarInstansi.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Gagal mengambil data instansi:', error)

    errorInstansi.value =
      error.response?.data?.message ||
      'Data instansi belum dapat dimuat. Periksa koneksi server.'
  } finally {
    memuatInstansi.value = false
  }
}

// Mengambil statistik
async function muatStatistik() {
  try {
    const { data } = await api.get('/statistik')
    statistik.value = data
  } catch (error) {
    console.error('Gagal mengambil statistik:', error)
  }
}

// Logout
async function keluar() {
  await keluarDariSistem()
  router.push('/')
}

// Jalankan saat halaman dibuka
onMounted(() => {
  muatInstansi()
  muatStatistik()
})
</script>

<style scoped>
/* ========================================
   DASAR
======================================== */
.home-page {
  min-height: 100vh;
  background: #ffffff;
  color: #302526;
  font-family: Arial, Helvetica, sans-serif;
}

/* ========================================
   NAVBAR
======================================== */
.navbar {
  height: 66px;
  background: #ffffff;
  border-bottom: 1px solid #f2eeee;
}

.navbar-inner {
  width: calc(100% - 100px);
  max-width: 1480px;
  height: 100%;
  margin: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand,
.footer-brand {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: #b51220;
  text-decoration: none;
  font-size: 15px;
}

.brand-logo {
  width: 27px;
  height: 27px;
  display: grid;
  place-items: center;
  border-radius: 5px;
  background: #b51220;
  color: #ffffff;
  font-size: 15px;
  font-weight: 700;
}

.nav-menu {
  height: 100%;
  display: flex;
  align-items: center;
  gap: 30px;
}

.nav-link {
  position: relative;
  color: #5c5051;
  text-decoration: none;
  font-size: 12px;
  transition: color 0.2s ease;
}

.nav-link:hover,
.nav-link.active {
  color: #b51220;
}

.nav-link.active::after {
  position: absolute;
  content: "";
  bottom: -25px;
  left: 0;
  right: 0;
  height: 2px;
  background: #b51220;
}

.logout-button {
  display: flex;
  align-items: center;
  gap: 6px;
  border: 0;
  background: transparent;
  color: #b51220;
  font-size: 12px;
  cursor: pointer;
}

/* ========================================
   HERO
======================================== */
.hero {
  position: relative;
  width: calc(100% - 100px);
  max-width: 1480px;
  min-height: 440px;
  margin: 26px auto 0;
  padding: 40px 0 40px 54px;
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  align-items: stretch;
  gap: 24px;
  overflow: hidden;
  border-radius: 12px;
  background: #faf9f9;
}

.hero-content {
  position: relative;
  z-index: 2;
  align-self: center;
  max-width: 590px;
  padding-right: 10px;
}

.hero-label {
  display: inline-block;
  padding: 8px 13px;
  border: 1px solid #f0e9e9;
  border-radius: 20px;
  background: #ffffff;
  color: #a5101d;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.4px;
}

.hero h1 {
  margin: 22px 0 16px;
  color: #2e2324;
  font-family: Georgia, "Times New Roman", serif;
  font-size: clamp(34px, 3.2vw, 49px);
  line-height: 1.08;
  letter-spacing: -1px;
}

.hero-content > p {
  max-width: 480px;
  margin: 0;
  color: #786d6e;
  font-size: 12px;
  line-height: 1.9;
}

.primary-button {
  width: fit-content;
  margin-top: 22px;
  padding: 14px 22px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border-radius: 30px;
  background: #b51220;
  box-shadow: 0 3px 7px #b512201e;
  color: #ffffff;
  text-decoration: none;
  font-size: 11px;
  font-weight: 600;
  transition: background 0.2s ease, transform 0.2s ease;
}

.primary-button:hover {
  background: #950d19;
  transform: translateY(-2px);
}

/* ========================================
   GAMBAR: RATA KANAN DAN TINGGI PENUH
======================================== */
.hero-visual {
  position: relative;
  width: 100%;
  min-width: 0;
  min-height: 440px;
  height: 100%;
  align-self: stretch;
  justify-self: stretch;
  overflow: hidden;
  background: transparent;
}

.hero-image {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: right center;
}

/* ========================================
   LAYANAN UTAMA
======================================== */
.services {
  padding: 74px 30px 0;
}

.section-heading {
  max-width: 600px;
  margin: 0 auto 28px;
  text-align: center;
}

.section-heading h2 {
  margin: 0 0 12px;
  font-family: Georgia, "Times New Roman", serif;
  font-size: 27px;
}

.section-heading p {
  margin: auto;
  color: #817476;
  font-size: 11px;
  line-height: 1.8;
}

.section-heading .api-info {
  margin-top: 10px;
  font-size: 10px;
}

.section-heading .api-error {
  color: #b51220;
}

.service-grid {
  width: 100%;
  max-width: 700px;
  margin: auto;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.service-card {
  min-height: 190px;
  padding: 22px;
  display: flex;
  flex-direction: column;
  align-items: center;
  border: 1px solid #eee8e8;
  border-radius: 10px;
  box-shadow: 0 3px 9px #30202005;
  color: inherit;
  text-align: center;
  text-decoration: none;
  transition: border-color 0.2s ease, transform 0.2s ease;
}

.service-card:hover {
  border-color: #dca7ac;
  transform: translateY(-3px);
}

.service-icon {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #faf1f2;
  color: #b51220;
}

.service-card h3 {
  margin: 14px 0 8px;
  font-size: 12px;
}

.service-card p {
  max-width: 260px;
  margin: 0;
  color: #817476;
  font-size: 10px;
  line-height: 1.8;
}

.service-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: auto;
  padding-top: 13px;
  color: #b51220;
  font-size: 10px;
  font-weight: 600;
}

/* ========================================
   STATISTIK
======================================== */
.statistics {
  width: calc(100% - 100px);
  max-width: 1100px;
  margin: 78px auto 80px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}

.stat-card {
  min-height: 105px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  border-radius: 8px;
  background: #b51220;
  color: #ffffff;
}

.stat-card strong {
  font-family: Georgia, "Times New Roman", serif;
  font-size: 27px;
}

.stat-card span {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.4px;
}

/* ========================================
   FOOTER
======================================== */
.footer {
  border-top: 1px solid #f1eaea;
  background: #ffffff;
}

.footer-main {
  width: calc(100% - 100px);
  max-width: 1480px;
  min-height: 145px;
  margin: auto;
  padding: 32px 0;
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 45px;
}

.footer-brand {
  font-size: 12px;
}

.footer-brand .brand-logo {
  width: 22px;
  height: 22px;
  font-size: 12px;
}

.footer-about p {
  max-width: 300px;
  color: #817476;
  font-size: 10px;
  line-height: 1.8;
}

.footer-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
}

.footer-column h4 {
  margin: 0 0 3px;
  font-size: 11px;
}

.footer-column a {
  color: #817476;
  text-decoration: none;
  font-size: 10px;
}

.footer-column a:hover {
  color: #b51220;
}

.footer-bottom {
  min-height: 40px;
  padding: 12px 50px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  border-top: 1px solid #f4eeee;
  color: #8b7d7e;
  font-size: 9px;
}

/* ========================================
   TABLET
======================================== */
@media (max-width: 900px) {
  .navbar-inner,
  .hero,
  .footer-main {
    width: calc(100% - 48px);
  }

  .nav-menu {
    gap: 16px;
  }

  .hero {
    min-height: 390px;
    padding: 30px 0 30px 28px;
    grid-template-columns: 1fr 0.95fr;
    gap: 12px;
  }

  .hero-content {
    padding-right: 5px;
  }

  .hero h1 {
    font-size: 34px;
  }

  .hero-visual {
    min-height: 390px;
  }

  .statistics {
    width: calc(100% - 48px);
  }
}

/* ========================================
   HP
======================================== */
@media (max-width: 650px) {
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
    height: auto;
    flex-wrap: wrap;
    gap: 14px;
  }

  .nav-link.active::after {
    bottom: -7px;
  }

  .hero {
    width: calc(100% - 32px);
    min-height: auto;
    padding: 28px 20px 0;
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .hero h1 {
    font-size: 34px;
  }

  .hero-content > p {
    font-size: 12px;
  }

  .primary-button {
    margin-top: 17px;
  }

  .hero-visual {
    width: 100%;
    height: auto;
    min-height: 0;
    aspect-ratio: 1 / 1;
    margin: 0;
  }

  .hero-image {
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
  }

  .services {
    padding: 50px 16px 0;
  }

  .section-heading h2 {
    font-size: 24px;
  }

  .service-grid {
    grid-template-columns: 1fr;
  }

  .statistics {
    width: calc(100% - 32px);
    margin: 45px auto;
    grid-template-columns: 1fr;
  }

  .footer-main {
    width: calc(100% - 32px);
    grid-template-columns: 1fr 1fr;
    gap: 25px;
  }

  .footer-about {
    grid-column: 1 / -1;
  }

  .footer-bottom {
    padding: 12px 16px;
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
