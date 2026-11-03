
<template>
  <div class="detail-page">
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
          <RouterLink to="/cek-laporan" class="active">Cek Laporan</RouterLink>
          <NotifikasiBell />
          <button class="logout-button" type="button" @click="keluar">
            ↪ Keluar
          </button>
        </nav>
      </div>
    </header>

    <main class="main-content">
      <p v-if="memuat" class="load-banner">Memuat detail laporan...</p>

      <p v-else-if="errorMuat" class="load-banner load-error">
        {{ errorMuat }}
      </p>

      <!-- NAVIGASI KEMBALI -->
      <div class="top-actions">
        <RouterLink
          :to="{ path: '/cek-laporan', query: { nomor: laporan.nomor } }"
          class="back-link"
        >
          ← Kembali ke Cek Status Laporan
        </RouterLink>

        <div class="action-buttons">
          <button type="button" class="secondary-button" @click="cetak">
            ▤ Cetak Bukti
          </button>
          <button type="button" class="secondary-button" @click="bagikan">
            ↗ Bagikan
          </button>
        </div>
      </div>

      <!-- INFORMASI UTAMA -->
      <section class="report-heading">
        <div class="report-tags">
          <span class="ticket-tag">No. Tiket: {{ laporan.nomor }}</span>
          <span class="status-tag" :class="statusClass">
            ● {{ laporan.status }}
          </span>
        </div>

        <h1>{{ laporan.judul }}</h1>

        <div class="report-meta">
          <span>◷ {{ formatTanggal(laporan.tanggal) }}</span>
          <span>⌖ {{ laporan.jenis }}</span>
        </div>

        <div class="agency">
          <span class="agency-icon">▤</span>
          <div>
            <small>INSTANSI BERWENANG</small>
            <strong>{{ laporan.instansi }}</strong>
          </div>
        </div>
      </section>

      <!-- KONTEN DUA KOLOM -->
      <div class="content-grid">
        <div class="left-column">
          <!-- TANGGAPAN RESMI -->
          <section class="panel response-panel">
            <div class="panel-heading">
              <div class="heading-icon">B</div>
              <div>
                <h2>Tanggapan Resmi Instansi</h2>
                <span class="subheading">Informasi tindak lanjut laporan</span>
              </div>
              <span class="response-status">● {{ laporan.status }}</span>
            </div>

            <div class="response-content">
              <p>Yth. Bapak/Ibu Pelapor,</p>

              <p v-if="laporan.tanggapan">{{ laporan.tanggapan }}</p>
              <p v-else>{{ keteranganTanpaTanggapan }}</p>

              <p>Terima kasih telah menggunakan layanan Teman Baik.</p>

              <div class="response-signature">
                Hormat kami,<br />
                <strong>{{ laporan.instansi }}</strong>
              </div>
            </div>

            <div class="response-footer">
              <span>◷ Pembaruan terakhir</span>
              <strong>{{ formatTanggal(laporan.pembaruan) }}</strong>
            </div>
          </section>

          <!-- BUKTI YANG DIKIRIM PELAPOR -->
          <section class="panel documentation-panel">
            <div class="panel-heading">
              <div class="heading-icon light-icon">▤</div>
              <div>
                <h2>Bukti yang Anda Kirim</h2>
                <span class="subheading">
                  Berkas yang Anda lampirkan saat membuat laporan
                </span>
              </div>
              <span class="photo-count">{{ buktiPelapor.length }} Berkas</span>
            </div>

            <BerkasList
              sumber="pelapor"
              :kepala="false"
              kosong="Anda tidak melampirkan bukti pada laporan ini."
              :items="buktiPelapor"
            />
          </section>

          <!-- LAMPIRAN DARI INSTANSI -->
          <section class="panel documentation-panel">
            <div class="panel-heading">
              <div class="heading-icon light-icon">▧</div>
              <div>
                <h2>Lampiran dari Instansi</h2>
                <span class="subheading">
                  Dokumentasi dan berita acara tindak lanjut dari instansi
                </span>
              </div>
              <span class="photo-count">{{ lampiranInstansi.length }} Berkas</span>
            </div>

            <BerkasList
              sumber="instansi"
              :kepala="false"
              kosong="Belum ada dokumentasi atau lampiran dari instansi untuk laporan ini."
              :items="lampiranInstansi"
            />
          </section>

          <!-- EVALUASI -->
          <section class="panel evaluation-panel">
            <div class="panel-heading">
              <div class="heading-icon light-icon">☆</div>
              <div>
                <h2>Evaluasi &amp; Kepuasan Penanganan</h2>
                <span class="subheading">
                  Bantu kami meningkatkan kualitas pelayanan
                </span>
              </div>
            </div>

            <!-- Sudah dinilai -->
            <div v-if="sudahDinilai" class="success-message">
              <span>✓</span>
              <div>
                <strong>Terima kasih atas evaluasi Anda!</strong>
                <p>Penilaian Anda: {{ labelRating }}.</p>
              </div>
            </div>

            <!-- Belum bisa dinilai (laporan belum dijawab) -->
            <div v-else-if="!bisaDinilai" class="evaluation-content">
              <p>
                Evaluasi dapat diberikan setelah laporan Anda
                selesai ditanggapi oleh instansi.
              </p>
            </div>

            <!-- Form evaluasi -->
            <div v-else class="evaluation-content">
              <p>Apakah Anda puas dengan tindak lanjut dari laporan ini?</p>

              <div class="rating-options">
                <button
                  v-for="opsi in pilihanRating"
                  :key="opsi.nilai"
                  type="button"
                  :class="{ selected: rating === opsi.nilai }"
                  @click="rating = opsi.nilai"
                >
                  <span>{{ opsi.ikon }}</span>
                  {{ opsi.label }}
                </button>
              </div>

              <p v-if="errorEvaluasi" class="rating-error">
                {{ errorEvaluasi }}
              </p>

              <button
                type="button"
                class="submit-rating"
                :disabled="!rating || mengirimEvaluasi"
                @click="kirimEvaluasi"
              >
                {{ mengirimEvaluasi ? 'Mengirim...' : 'Kirim Evaluasi' }}
              </button>
            </div>
          </section>
        </div>

        <!-- SIDEBAR -->
        <aside class="right-column">
          <section class="panel history-panel">
            <div class="panel-heading">
              <div class="heading-icon light-icon">↗</div>
              <div>
                <h2>Progres Penanganan</h2>
                <span class="subheading">Riwayat status laporan</span>
              </div>
            </div>

            <div class="history-list">
              <div
                v-for="(item, index) in riwayat"
                :key="index"
                class="history-item"
              >
                <div class="history-marker" :class="item.class">
                  {{ item.selesai ? '✓' : index + 1 }}
                </div>

                <div class="history-content">
                  <strong>{{ item.judul }}</strong>
                  <small>{{ item.waktu }}</small>
                  <p>{{ item.keterangan }}</p>
                </div>
              </div>
            </div>
          </section>

          <section class="panel transparency-panel">
            <div class="panel-heading">
              <div class="heading-icon light-icon">♧</div>
              <div>
                <h2>Jaminan Transparansi</h2>
              </div>
            </div>

            <p>
              Setiap laporan diproses melalui tahapan yang tercatat
              agar masyarakat dapat memantau perkembangan penanganannya.
            </p>

            <div class="transparency-note">
              <strong>Informasi Pelayanan</strong>
              <span>Status diperbarui langsung dari sistem pengaduan.</span>
            </div>
          </section>
        </aside>
      </div>
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
import BerkasList from '../components/BerkasList.vue'

const route = useRoute()
const router = useRouter()

const memuat = ref(true)
const errorMuat = ref('')

const laporan = ref({
  nomor: '',
  judul: 'Memuat...',
  statusKode: '',
  status: '',
  jenis: '',
  instansi: '',
  tanggal: null,
  pembaruan: null,
  tanggapan: '',
  riwayat: [],
})

const buktiPelapor = ref([]) // berkas yang dikirim pelapor
const lampiranInstansi = ref([]) // berkas yang diunggah instansi

const rating = ref(0)
const sudahDinilai = ref(false)
const mengirimEvaluasi = ref(false)
const errorEvaluasi = ref('')

// Backend menerima rating 1 - 4 (skala kepuasan)
const pilihanRating = [
  { nilai: 1, ikon: '☹', label: 'Tidak Puas' },
  { nilai: 2, ikon: '😐', label: 'Kurang Puas' },
  { nilai: 3, ikon: '☺', label: 'Puas' },
  { nilai: 4, ikon: '😊', label: 'Sangat Puas' },
]

const labelStatus = {
  diterima: 'Laporan Diterima',
  diverifikasi: 'Diverifikasi',
  dikirim: 'Diproses',
  dijawab: 'Selesai',
  ditolak: 'Ditolak',
}

const statusClass = computed(() => {
  switch (laporan.value.statusKode) {
    case 'dijawab':
      return 'done'
    case 'diverifikasi':
      return 'verify'
    case 'dikirim':
      return 'process'
    default:
      return 'received'
  }
})

const bisaDinilai = computed(
  () => laporan.value.statusKode === 'dijawab' && !sudahDinilai.value,
)

const labelRating = computed(
  () => pilihanRating.find((o) => o.nilai === rating.value)?.label || '-',
)

const keteranganTanpaTanggapan = computed(() => {
  switch (laporan.value.statusKode) {
    case 'ditolak':
      return 'Mohon maaf, laporan Anda tidak dapat ditindaklanjuti.'
    case 'dikirim':
      return 'Laporan Anda sedang ditindaklanjuti oleh instansi terkait.'
    case 'diverifikasi':
      return 'Laporan Anda telah diverifikasi dan akan diteruskan ke instansi terkait.'
    default:
      return 'Laporan Anda telah diterima dan menunggu verifikasi petugas.'
  }
})

// Progres 4 tahap + tahap "Ditolak" bila ada. Waktu & catatan diambil
// dari riwayat status asli di backend.
const riwayat = computed(() => {
  const kode = laporan.value.statusKode
  const daftarRiwayat = laporan.value.riwayat || []

  const tahap = [
    {
      kode: 'diterima',
      judul: 'Laporan Diterima Sistem',
      keterangan: 'Laporan berhasil diterima oleh sistem.',
    },
    {
      kode: 'diverifikasi',
      judul: 'Verifikasi Laporan',
      keterangan: 'Petugas memeriksa informasi laporan.',
    },
    {
      kode: 'dikirim',
      judul: 'Tindak Lanjut Penanganan',
      keterangan: 'Instansi terkait menindaklanjuti laporan.',
    },
    {
      kode: 'dijawab',
      judul: 'Laporan Selesai',
      keterangan: 'Proses penanganan laporan telah selesai.',
    },
  ]

  // Laporan ditolak: tampilkan tahap yang pernah dilalui + penolakan
  if (kode === 'ditolak') {
    const dilalui = tahap.filter((t) =>
      daftarRiwayat.some((r) => r.status === t.kode),
    )

    dilalui.push({
      kode: 'ditolak',
      judul: 'Laporan Ditolak',
      keterangan: 'Laporan tidak dapat ditindaklanjuti.',
    })

    return dilalui.map((t) => {
      const entri = daftarRiwayat.find((r) => r.status === t.kode)

      return {
        judul: t.judul,
        keterangan: entri?.catatan || t.keterangan,
        waktu: formatTanggal(entri?.waktu),
        selesai: true,
        class: t.kode === 'ditolak' ? 'current' : 'complete',
      }
    })
  }

  const indeksSekarang = tahap.findIndex((t) => t.kode === kode)

  return tahap.map((t, index) => {
    const entri = daftarRiwayat.find((r) => r.status === t.kode)
    const selesai = index <= indeksSekarang && indeksSekarang !== -1

    return {
      judul: t.judul,
      keterangan: entri?.catatan || t.keterangan,
      waktu: entri ? formatTanggal(entri.waktu) : 'Menunggu pembaruan',
      selesai,
      class: selesai
        ? index === indeksSekarang && kode !== 'dijawab'
          ? 'current'
          : 'complete'
        : 'pending',
    }
  })
})

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

// GET /api/pengaduan/{no_tiket}
async function muatLaporan() {
  const nomor = String(route.params.id || route.query.nomor || '')
    .trim()
    .toUpperCase()

  if (!nomor) {
    router.replace('/cek-laporan')
    return
  }

  memuat.value = true
  errorMuat.value = ''

  try {
    const { data } = await api.get(
      `/pengaduan/${encodeURIComponent(nomor)}`,
    )

    const riwayatServer = data.riwayat || []
    const terakhir = riwayatServer[riwayatServer.length - 1]

    laporan.value = {
      nomor: data.no_tiket,
      judul: data.judul,
      statusKode: data.status_saat_ini,
      status: labelStatus[data.status_saat_ini] || data.status_saat_ini,
      jenis: data.jenis === 'pengaduan' ? 'Pengaduan' : 'Informasi',
      instansi: data.instansi,
      tanggal: data.dibuat_pada,
      pembaruan: terakhir?.waktu || data.dibuat_pada,
      tanggapan: data.tanggapan?.isi || '',
      riwayat: riwayatServer,
    }

    // Evaluasi yang sudah pernah diberikan
    if (data.evaluasi?.rating) {
      rating.value = data.evaluasi.rating
      sudahDinilai.value = true
    }

    muatBerkas(data)
  } catch (error) {
    console.error('Gagal memuat laporan:', error.response?.data || error)

    errorMuat.value =
      error.response?.status === 404
        ? 'Laporan tidak ditemukan atau bukan milik Anda.'
        : pesanError(error, 'Laporan gagal dimuat')
  } finally {
    memuat.value = false
  }
}

// Pisahkan berkas berdasarkan pengirimnya agar tidak tertukar:
// - buktiPelapor     : dikirim oleh pelapor saat membuat laporan
// - lampiranInstansi : dokumentasi & berita acara dari instansi
function muatBerkas(data) {
  const tiket = encodeURIComponent(data.no_tiket)

  buktiPelapor.value = data.bukti_pengadu
    ? [
        {
          kunci: 'bukti',
          nama: data.bukti_pengadu.nama_file,
          keterangan: 'Dikirim oleh Anda saat membuat laporan',
          path: `/pengaduan/${tiket}/bukti`,
        },
      ]
    : []

  lampiranInstansi.value = (data.lampiran || []).map((l) => ({
    kunci: `lampiran-${l.id}`,
    nama: l.nama_file,
    label: l.label,
    keterangan: `${
      l.jenis === 'berita_acara' ? 'Berita acara' : 'Dokumentasi'
    } \u2022 ${formatTanggal(l.tanggal)}`,
    path: `/pengaduan/${tiket}/lampiran/${l.id}`,
  }))
}

function cetak() {
  window.print()
}

async function bagikan() {
  const url = window.location.href

  try {
    if (navigator.share) {
      await navigator.share({
        title: 'Detail Laporan Teman Baik',
        text: `Nomor laporan: ${laporan.value.nomor}`,
        url,
      })
    } else if (navigator.clipboard) {
      await navigator.clipboard.writeText(url)
      alert('Tautan laporan berhasil disalin.')
    } else {
      alert(`Salin tautan ini secara manual: ${url}`)
    }
  } catch (error) {
    if (error.name !== 'AbortError') {
      alert('Tautan tidak dapat dibagikan.')
    }
  }
}

// POST /api/pengaduan/{no_tiket}/evaluasi  body: { rating: 1..4 }
async function kirimEvaluasi() {
  if (!rating.value) return

  mengirimEvaluasi.value = true
  errorEvaluasi.value = ''

  try {
    await api.post(
      `/pengaduan/${encodeURIComponent(laporan.value.nomor)}/evaluasi`,
      { rating: rating.value },
    )

    sudahDinilai.value = true
  } catch (error) {
    errorEvaluasi.value = pesanError(error, 'Evaluasi gagal dikirim')
  } finally {
    mengirimEvaluasi.value = false
  }
}

async function keluar() {
  await keluarDariSistem()

  router.push('/')
}

onMounted(muatLaporan)

</script>

<style scoped>
.detail-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fffafa;
  color: #382b2c;
  font-family: Arial, Helvetica, sans-serif;
}

.navbar {
  height: 62px;
  flex-shrink: 0;
  background: #fff;
  border-bottom: 1px solid #eee4e4;
}

.navbar-inner,
.footer-inner {
  width: calc(100% - 100px);
  max-width: 1280px;
  margin: auto;
}

.navbar-inner {
  height: 100%;
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
  color: white;
  font-weight: bold;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 25px;
}

.nav-menu a {
  color: #756668;
  font-size: 11px;
  text-decoration: none;
}

.nav-menu a.active,
.nav-menu a:hover {
  color: #b51220;
}

.icon-button,
.logout-button {
  position: relative;
  border: 0;
  background: transparent;
  color: #756668;
  cursor: pointer;
  font-size: 11px;
}

.logout-button {
  color: #b51220;
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

.main-content {
  width: calc(100% - 80px);
  max-width: 1250px;
  margin: 0 auto;
  padding: 30px 0 55px;
  flex: 1;
}

.top-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.back-link {
  color: #766667;
  font-size: 11px;
  text-decoration: none;
}

.back-link:hover {
  color: #b51220;
}

.action-buttons {
  display: flex;
  gap: 10px;
}

.secondary-button {
  padding: 8px 12px;
  border: 1px solid #eadede;
  border-radius: 5px;
  background: white;
  color: #655657;
  font-size: 10px;
  cursor: pointer;
}

.report-heading {
  position: relative;
  padding: 22px 20px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  border-top: 3px solid #c51c2a;
  border-radius: 6px;
  background: white;
  box-shadow: 0 3px 12px #3e222208;
}

.report-tags {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.ticket-tag,
.status-tag {
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 9px;
}

.ticket-tag {
  background: #fff0f0;
  color: #b51220;
}

.status-tag {
  background: #fff4e7;
  color: #aa792e;
}

.status-tag.process {
  background: #fff0e8;
  color: #b66a35;
}

.status-tag.verify {
  background: #fff4e7;
  color: #aa792e;
}

.status-tag.done {
  background: #e8f6ec;
  color: #277e48;
}

.status-tag.received {
  background: #f2eeee;
  color: #74696a;
}

.report-heading h1 {
  max-width: 720px;
  margin: 12px 0;
  font-size: 19px;
  line-height: 1.4;
}

.report-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 17px;
  color: #887879;
  font-size: 10px;
}

.agency {
  position: absolute;
  top: 20px;
  right: 20px;
  max-width: 245px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 7px;
  background: #fff5f5;
}

.agency-icon {
  width: 30px;
  height: 30px;
  display: grid;
  place-items: center;
  border-radius: 6px;
  background: #fce6e7;
  color: #b51220;
}

.agency div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.agency small {
  color: #a38b8c;
  font-size: 8px;
}

.agency strong {
  font-size: 10px;
  line-height: 1.5;
}

.content-grid {
  margin-top: 22px;
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(240px, 0.9fr);
  align-items: start;
  gap: 22px;
}

.left-column,
.right-column {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.panel {
  min-width: 0;
  padding: 20px;
  border: 1px solid #f0e5e5;
  border-radius: 9px;
  background: #fff;
  box-shadow: 0 3px 12px #3e222207;
}

.panel-heading {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.heading-icon {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #b51220;
  color: white;
  font-size: 11px;
  font-weight: bold;
}

.light-icon {
  background: #fff0f0;
  color: #b51220;
}

.panel-heading h2 {
  margin: 0;
  font-size: 12px;
  line-height: 1.5;
}

.subheading {
  display: block;
  margin-top: 4px;
  color: #9a8889;
  font-size: 9px;
}

.response-status {
  margin-left: auto;
  color: #b51220;
  font-size: 9px;
  white-space: nowrap;
}

.response-content {
  margin-top: 18px;
  padding: 17px;
  border-radius: 6px;
  background: #fff7f7;
  color: #665657;
  font-size: 11px;
  line-height: 1.9;
}

.response-content p {
  margin: 0 0 12px;
}

.response-content p:last-of-type {
  margin-bottom: 0;
}

.response-signature {
  margin-top: 17px;
  padding-top: 12px;
  border-top: 1px solid #f0e1e1;
  font-size: 10px;
  line-height: 1.8;
}

.response-footer {
  margin-top: 15px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  color: #8c7c7d;
  font-size: 9px;
}

.documentation-panel .panel-heading {
  margin-bottom: 18px;
}

.photo-count {
  margin-left: auto;
  color: #8e7b7d;
  font-size: 9px;
  white-space: nowrap;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.photo-card {
  overflow: hidden;
  border: 1px solid #eee5e5;
  border-radius: 6px;
  background: #fffafa;
}

.photo-card img {
  width: 100%;
  height: 150px;
  display: block;
  object-fit: cover;
}

.photo-card span {
  display: block;
  padding: 9px;
  color: #766667;
  font-size: 9px;
}

.no-photos {
  padding: 25px 15px;
  border: 1px dashed #e8dada;
  border-radius: 6px;
  color: #9a8889;
  text-align: center;
}

.no-photos span {
  color: #b51220;
  font-size: 25px;
}

.no-photos p {
  font-size: 10px;
}

.attachment {
  margin-top: 16px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #eee5e5;
  border-radius: 6px;
}

.file-icon {
  width: 30px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 4px;
  background: #fff0f0;
  color: #b51220;
}

.file-info {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.file-info strong {
  overflow-wrap: anywhere;
  font-size: 10px;
}

.file-info small {
  color: #988687;
  font-size: 9px;
}

.download-link {
  padding: 7px 10px;
  border: 1px solid #eadede;
  border-radius: 20px;
  color: #6e5d5e;
  text-decoration: none;
  font-size: 9px;
  white-space: nowrap;
}

.evaluation-content {
  margin-top: 18px;
  padding: 16px;
  border-radius: 6px;
  background: #fff7f7;
}

.evaluation-content > p {
  margin: 0 0 15px;
  font-size: 11px;
}

.rating-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.rating-options button {
  padding: 12px 5px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 7px;
  border: 1px solid #eee0e0;
  border-radius: 6px;
  background: white;
  color: #786869;
  font-size: 9px;
  cursor: pointer;
}

.rating-options button span {
  font-size: 19px;
}

.rating-options button.selected {
  border-color: #b51220;
  background: #fff0f0;
  color: #b51220;
}

.evaluation-content textarea {
  width: 100%;
  box-sizing: border-box;
  margin-top: 12px;
  padding: 11px;
  resize: vertical;
  border: 1px solid #ebdfdf;
  border-radius: 6px;
  outline-color: #b51220;
  font: inherit;
  font-size: 10px;
}

.submit-rating {
  margin-top: 10px;
  padding: 10px 15px;
  border: 0;
  border-radius: 20px;
  background: #b51220;
  color: white;
  font-size: 10px;
  cursor: pointer;
}

.submit-rating:disabled {
  background: #c9b9ba;
  cursor: not-allowed;
}

.success-message {
  margin-top: 18px;
  padding: 15px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  border-radius: 6px;
  background: #edf8f0;
  color: #277e48;
}

.success-message > span {
  font-size: 20px;
}

.success-message strong {
  font-size: 11px;
}

.success-message p {
  margin-bottom: 0;
  font-size: 10px;
  line-height: 1.7;
}

.history-panel .panel-heading {
  margin-bottom: 25px;
}

.history-list {
  padding-left: 5px;
}

.history-item {
  position: relative;
  min-height: 91px;
  display: flex;
  gap: 12px;
}

.history-item:not(:last-child)::before {
  position: absolute;
  top: 27px;
  bottom: 0;
  left: 12px;
  width: 2px;
  background: #eee5e5;
  content: '';
}

.history-item:has(.history-marker.complete):not(:last-child)::before {
  background: #c51c2a;
}

.history-marker {
  z-index: 1;
  width: 25px;
  height: 25px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border: 1px solid #e9dede;
  border-radius: 50%;
  background: white;
  color: #a29192;
  font-size: 9px;
}

.history-marker.complete,
.history-marker.current {
  border-color: #b51220;
  background: #b51220;
  color: white;
}

.history-marker.current {
  box-shadow: 0 0 0 4px #f9e8e9;
}

.history-content {
  min-width: 0;
  padding: 3px 0 20px;
}

.history-content strong {
  display: block;
  font-size: 10px;
  line-height: 1.5;
}

.history-content small {
  display: block;
  margin-top: 5px;
  color: #b51220;
  font-size: 9px;
}

.history-content p {
  margin: 7px 0 0;
  color: #8d7d7e;
  font-size: 9px;
  line-height: 1.7;
}

.history-marker.pending + .history-content strong {
  color: #a59697;
}

.transparency-panel > p {
  margin: 17px 0;
  color: #817172;
  font-size: 10px;
  line-height: 1.9;
}

.transparency-note {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  border-radius: 6px;
  background: #fff5f5;
}

.transparency-note strong {
  color: #b51220;
  font-size: 10px;
}

.transparency-note span {
  color: #887778;
  font-size: 9px;
  line-height: 1.7;
}

.footer {
  margin-top: auto;
  border-top: 1px solid #f0e6e6;
  background: #fff;
}

.footer-inner {
  min-height: 115px;
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
  font-size: 10px;
}

.footer-about p {
  max-width: 260px;
  color: #8a7a7b;
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
  color: #837475;
  text-decoration: none;
}

.footer-bottom {
  padding: 12px 50px;
  border-top: 1px solid #f3eaea;
  color: #958586;
  font-size: 9px;
  text-align: right;
}

@media print {
  .navbar,
  .footer,
  .top-actions,
  .evaluation-panel {
    display: none !important;
  }

  .main-content {
    width: 100%;
    max-width: none;
    padding: 0;
  }

  .content-grid {
    grid-template-columns: 1.7fr 0.9fr;
  }

  .panel,
  .report-heading {
    box-shadow: none;
    break-inside: avoid;
  }
}

@media (max-width: 900px) {
  .navbar-inner,
  .footer-inner {
    width: calc(100% - 40px);
  }

  .nav-menu {
    gap: 13px;
  }

  .main-content {
    width: calc(100% - 40px);
  }

  .agency {
    position: static;
    margin-top: 18px;
    max-width: 100%;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .right-column {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
  }
}

@media (max-width: 600px) {
  .navbar {
    height: auto;
  }

  .navbar-inner {
    padding: 12px 0;
    align-items: flex-start;
    flex-direction: column;
    gap: 14px;
  }

  .nav-menu {
    width: 100%;
    flex-wrap: wrap;
  }

  .main-content {
    padding-top: 20px;
  }

  .top-actions {
    align-items: flex-start;
    flex-direction: column;
  }

  .report-heading {
    padding: 16px;
  }

  .report-heading h1 {
    font-size: 17px;
  }

  .report-meta {
    flex-direction: column;
    gap: 9px;
  }

  .panel {
    padding: 15px;
  }

  .response-status {
    font-size: 8px;
  }

  .photo-grid {
    grid-template-columns: 1fr;
  }

  .photo-card img {
    height: 180px;
  }

  .right-column {
    display: flex;
  }

  .footer-inner {
    grid-template-columns: 1fr 1fr;
    gap: 25px;
  }

  .footer-about {
    grid-column: 1 / -1;
  }

  .footer-bottom {
    padding: 12px 20px;
    text-align: left;
  }
}

/* ===== Tambahan untuk integrasi backend ===== */
.load-banner {
  margin: 0 0 18px;
  padding: 12px 16px;
  border-radius: 8px;
  background: #f6f1f1;
  color: #5c5051;
  font-size: 12px;
}

.load-error {
  background: #fdecea;
  color: #a31515;
}

button.download-link {
  background: #fff;
  font-family: inherit;
  cursor: pointer;
}

.rating-options {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.rating-error {
  margin: 10px 0 0;
  color: #b51220;
  font-size: 11px;
}
</style>
