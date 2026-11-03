<template>
  <AdminLayout>
    <p class="adm-breadcrumb">
      Beranda &rsaquo; Admin Instansi &rsaquo; <b>Laporan Masuk</b>
    </p>

    <!-- ================= JUDUL ================= -->
    <div class="adm-head">
      <div>
        <div class="adm-title-row">
          <h1 class="adm-title bar">Laporan Masuk Instansi</h1>

          <span class="adm-live" :class="{ 'adm-live-red': ringkasanInstansi.error }">
            {{ ringkasanInstansi.error ? 'Offline' : 'Realtime Sinkron' }}
          </span>
        </div>

        <p class="adm-subtitle">
          Laporan warga yang diteruskan Admin Teman Baik ke
          <b>{{ nama || 'instansi Anda' }}</b>. Kirim balasan tindak lanjut
          dan unggah dokumentasi pendukung.
        </p>
      </div>

      <div class="adm-head-actions">
        <button type="button" class="adm-btn" @click="muatUlang">
          <RotateCcw :size="13" /> Muat Ulang
        </button>
      </div>
    </div>

    <p v-if="ringkasanInstansi.error" class="adm-alert pad">
      {{ ringkasanInstansi.error }} Pastikan backend Laravel berjalan.
    </p>

    <!-- ================= KPI ================= -->
    <section class="adm-kpis tiga">
      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Menunggu Balasan
          <span class="adm-kpi-icon"><Clock :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(menunggu) }}</div>
        <div class="adm-kpi-sub">
          <span class="merah">Perlu ditindaklanjuti instansi</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Sudah Dijawab
          <span class="adm-kpi-icon"><CircleCheck :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(selesai) }}</div>
        <div class="adm-kpi-sub">
          <span class="hijau">Dipublikasikan ke pelapor</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Total Laporan Instansi
          <span class="adm-kpi-icon"><FileText :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(menunggu + selesai) }}</div>
        <div class="adm-kpi-sub">
          <span>Laporan yang diteruskan ke instansi Anda</span>
        </div>
      </div>
    </section>

    <!-- ================= TABEL ================= -->
    <section class="adm-card">
      <div class="adm-card-head">
        <div>
          <h2>Daftar Laporan</h2>
          <p>
            Menampilkan {{ angka(dari) }}&ndash;{{ angka(ke) }} dari
            {{ angka(total) }} laporan
          </p>
        </div>
      </div>

      <div class="id-toolbar">
        <div class="id-search">
          <Search :size="13" />
          <input
            v-model.trim="filter.q"
            type="text"
            placeholder="Cari nomor tiket atau judul laporan..."
            aria-label="Cari laporan"
          />
        </div>

        <select v-model="filter.status" class="adm-select id-select" aria-label="Filter status">
          <option value="">Semua Status</option>
          <option value="dikirim">Menunggu Balasan</option>
          <option value="dijawab">Selesai</option>
        </select>

        <button type="button" class="adm-btn adm-btn-sm" @click="resetFilter">
          <RotateCcw :size="12" /> Reset
        </button>
      </div>

      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>No. Tiket &amp; Waktu</th>
              <th>Pelapor</th>
              <th>Jenis &amp; Judul Laporan</th>
              <th>Status</th>
              <th>Balasan</th>
              <th>Lampiran</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="memuat">
              <td colspan="7" class="adm-empty">Memuat laporan...</td>
            </tr>

            <tr v-else-if="error">
              <td colspan="7" class="adm-empty galat">{{ error }}</td>
            </tr>

            <tr v-else-if="!baris.length">
              <td colspan="7" class="adm-empty">
                {{ adaFilter ? 'Tidak ada laporan yang cocok dengan filter.' : 'Belum ada laporan yang diteruskan ke instansi Anda.' }}
              </td>
            </tr>

            <tr v-for="p in baris" v-else :key="p.no_tiket">
              <td>
                <span class="adm-cell-strong">#{{ p.no_tiket }}</span>
                <span class="adm-cell-muted">{{ waktuRelatif(p.dibuat_pada) }}</span>
              </td>

              <td>
                <span v-if="p.samarkan" class="adm-cell-strong"><i>Identitas Disamarkan</i></span>
                <span v-else class="adm-cell-strong">{{ p.nama_pelapor || '-' }}</span>
              </td>

              <td>
                <span class="adm-tag" :class="p.jenis">
                  {{ p.jenis === 'pengaduan' ? 'PENGADUAN' : 'INFORMASI' }}
                </span>
                <span class="adm-cell-title">{{ p.judul }}</span>
              </td>

              <td>
                <span class="adm-badge" :class="KELAS_STATUS_INSTANSI[p.status]">
                  {{ LABEL_STATUS_INSTANSI[p.status] || p.status }}
                </span>
              </td>

              <td>
                <span v-if="p.sudah_ada_balasan" class="adm-badge st-green">Sudah dikirim</span>
                <span v-else-if="p.status === 'dikirim'" class="adm-badge st-amber">Belum</span>
                <span v-else>-</span>
              </td>

              <td>{{ p.jumlah_lampiran }} berkas</td>

              <td>
                <div class="adm-actions">
                  <button
                    type="button"
                    class="adm-btn adm-btn-sm"
                    @click="tiketDipilih = p.no_tiket"
                  >
                    Detail
                  </button>

                  <button
                    v-if="p.status === 'dikirim'"
                    type="button"
                    class="adm-btn adm-btn-primary adm-btn-sm"
                    @click="tiketDipilih = p.no_tiket"
                  >
                    Balas
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="adm-table-foot">
        <span>
          15 per halaman &bull; Halaman {{ halaman }} dari {{ halamanTerakhir }}
        </span>

        <div class="adm-pager">
          <button
            type="button"
            :disabled="halaman <= 1 || memuat"
            aria-label="Sebelumnya"
            @click="gantiHalaman(halaman - 1)"
          >
            <ChevronLeft :size="13" />
          </button>

          <template v-for="(n, i) in daftarHalaman" :key="i">
            <span v-if="n === '…'">…</span>

            <button
              v-else
              type="button"
              :class="{ aktif: n === halaman }"
              :disabled="memuat"
              @click="gantiHalaman(n)"
            >
              {{ n }}
            </button>
          </template>

          <button
            type="button"
            :disabled="halaman >= halamanTerakhir || memuat"
            aria-label="Berikutnya"
            @click="gantiHalaman(halaman + 1)"
          >
            <ChevronRight :size="13" />
          </button>
        </div>
      </div>
    </section>

    <div class="id-note">
      <Info :size="14" />
      <p>
        <b>Alur kerja instansi:</b> laporan diteruskan oleh Admin Teman Baik
        &rarr; instansi mengirim balasan &amp; dokumentasi &rarr; Admin Teman
        Baik meninjau dan memublikasikan tanggapan kepada pelapor. Setelah
        dipublikasikan, balasan tidak dapat diubah lagi.
      </p>
    </div>

    <InstansiPengaduanModal
      v-if="tiketDipilih"
      :tiket="tiketDipilih"
      @tutup="tiketDipilih = ''"
      @berubah="muatUlang"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import {
  ChevronLeft,
  ChevronRight,
  CircleCheck,
  Clock,
  FileText,
  Info,
  RotateCcw,
  Search,
} from 'lucide-vue-next'

import AdminLayout from '../../components/admin/AdminLayout.vue'
import InstansiPengaduanModal from '../../components/admin/InstansiPengaduanModal.vue'

import { statusSesiAdmin } from '../../services/api'
import { angka, waktuRelatif } from '../../services/admin'
import {
  KELAS_STATUS_INSTANSI,
  LABEL_STATUS_INSTANSI,
  ambilLaporanInstansi,
  muatRingkasanInstansi,
  namaInstansi,
  ringkasanInstansi,
} from '../../services/instansi'

const route = useRoute()

const nama = ref('')

const filter = reactive({
  q: String(route.query.q || ''),
  status: ['dikirim', 'dijawab'].includes(route.query.status)
    ? route.query.status
    : '',
})

const halaman = ref(1)
const baris = ref([])
const total = ref(0)
const halamanTerakhir = ref(1)
const dari = ref(0)
const ke = ref(0)
const memuat = ref(true)
const error = ref('')

const tiketDipilih = ref('')

let timer = null
let jedaCari = null

const menunggu = computed(() => ringkasanInstansi.data?.menunggu_balasan || 0)
const selesai = computed(() => ringkasanInstansi.data?.sudah_dijawab || 0)
const adaFilter = computed(() => !!(filter.q || filter.status))

const daftarHalaman = computed(() => {
  const t = halamanTerakhir.value
  const c = halaman.value

  if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1)

  const set = [...new Set([1, 2, c - 1, c, c + 1, t - 1, t])]
    .filter((n) => n >= 1 && n <= t)
    .sort((a, b) => a - b)

  const hasil = []

  set.forEach((n, i) => {
    if (i && n - set[i - 1] > 1) hasil.push('…')
    hasil.push(n)
  })

  return hasil
})

// GET /api/instansi/pengaduan?status=&q=&page=
async function muat(tampilkanLoading = true) {
  if (tampilkanLoading) memuat.value = true
  error.value = ''

  try {
    const params = { page: halaman.value }

    if (filter.q) params.q = filter.q
    if (filter.status) params.status = filter.status

    const h = await ambilLaporanInstansi(params)

    baris.value = h.data || []
    total.value = h.total || 0
    halamanTerakhir.value = h.last_page || 1
    dari.value = h.from || 0
    ke.value = h.to || 0
  } catch (e) {
    error.value =
      e.response?.data?.message || 'Daftar laporan gagal dimuat dari server.'
    baris.value = []
    total.value = 0
    dari.value = 0
    ke.value = 0
  } finally {
    memuat.value = false
  }
}

function gantiHalaman(n) {
  if (n < 1 || n > halamanTerakhir.value || n === halaman.value) return

  halaman.value = n
  muat()
}

function muatUlang() {
  muatRingkasanInstansi()
  muat(false)
}

function resetFilter() {
  filter.q = ''
  filter.status = ''
}

// Ubah filter -> halaman 1 (pencarian diberi jeda agar tidak memanggil API tiap ketikan)
watch(
  () => [filter.q, filter.status],
  () => {
    clearTimeout(jedaCari)

    jedaCari = setTimeout(() => {
      halaman.value = 1
      muat()
    }, 350)
  },
)

// Pencarian dari topbar (?q=) atau lonceng (?status=dikirim)
watch(
  () => [route.query.q, route.query.status],
  ([q, status]) => {
    filter.q = String(q || '')
    filter.status = ['dikirim', 'dijawab'].includes(status) ? status : ''
  },
)

onMounted(async () => {
  muatRingkasanInstansi()
  muat()

  // "Realtime": segarkan otomatis tiap 60 detik
  timer = setInterval(() => {
    if (!tiketDipilih.value) muatUlang()
  }, 60000)

  nama.value = await namaInstansi(statusSesiAdmin.user?.id_instansi)
})

onBeforeUnmount(() => {
  clearInterval(timer)
  clearTimeout(jedaCari)
})
</script>

<style scoped>
.id-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 0 20px 16px;
}

.id-search {
  display: flex;
  min-width: 220px;
  flex: 1;
  align-items: center;
  gap: 8px;
  max-width: 340px;
  height: 36px;
  padding: 0 12px;
  border: 1px solid #f1e3e3;
  border-radius: 99px;
  background: #fff6f6;
  color: #7d6e6f;
}

.id-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #2b1f20;
  font-size: 11.5px;
}

.id-select {
  width: auto;
  min-width: 170px;
  border-radius: 99px;
}

.id-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 20px;
  padding: 13px 16px;
  border: 1px solid #f1d9d9;
  border-radius: 12px;
  background: #fff3f3;
  color: #b51220;
}

.id-note p {
  color: #6b4a4c;
  font-size: 10.5px;
  line-height: 1.6;
}

.id-note b {
  color: #b51220;
}
</style>
