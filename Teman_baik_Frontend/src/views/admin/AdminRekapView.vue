<template>
  <AdminLayout>
    <p class="adm-breadcrumb">
      Beranda &rsaquo; Super Admin &rsaquo; <b>Filter &amp; Rekap Pengaduan</b>
    </p>

    <!-- ================= JUDUL ================= -->
    <div class="adm-head">
      <div>
        <div class="adm-title-row">
          <h1 class="adm-title bar">Filter &amp; Rekapitulasi Pengaduan</h1>

          <span class="adm-live" :class="{ 'adm-live-red': ringkasan.error }">
            {{ ringkasan.error ? 'Offline' : 'Realtime Sinkron' }}
          </span>
        </div>

        <p class="adm-subtitle">
          Pantau seluruh laporan dan permohonan informasi warga lintas
          instansi. Saring berdasarkan instansi, jenis, status, dan rentang
          waktu, lalu tindak lanjuti langsung dari tabel.
        </p>
      </div>

      <div class="adm-head-actions">
        <button
          type="button"
          class="adm-btn"
          :disabled="mengekspor"
          @click="ekspor"
        >
          <Download :size="13" />
          {{ mengekspor ? 'Mengunduh...' : 'Ekspor Data (CSV)' }}
        </button>
      </div>
    </div>

    <!-- ================= KPI ================= -->
    <section class="adm-kpis">
      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Total Laporan Masuk
          <span class="adm-kpi-icon"><FileText :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(r.total) }}</div>
        <div class="adm-kpi-sub">
          <span class="merah">{{ angka(r.diproses) }} Diproses</span>
          <span class="hijau">{{ angka(r.selesai) }} Selesai</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Pengaduan Warga
          <span class="adm-kpi-icon"><TriangleAlert :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(r.pengaduan) }}</div>
        <div class="adm-kpi-sub">
          <span>{{ persen(r.persenPengaduan) }}% dari total</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Permohonan Informasi
          <span class="adm-kpi-icon"><Info :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(r.informasi) }}</div>
        <div class="adm-kpi-sub">
          <span>{{ persen(r.persenInformasi) }}% dari total</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Tingkat Penyelesaian
          <span class="adm-kpi-icon"><CircleCheck :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ persen(r.persenSelesai) }}<small>%</small>
        </div>
        <div class="adm-kpi-sub">
          <span>{{ angka(r.diterima) }} menunggu verifikasi</span>
        </div>
      </div>
    </section>

    <!-- ================= FILTER ================= -->
    <section class="adm-card" style="margin-bottom: 20px">
      <div class="adm-card-head">
        <div>
          <h2>Filter &amp; Pantau Pengaduan Lintas OPD</h2>
          <p>
            Saring data laporan dari seluruh instansi. Hasil diperbarui
            otomatis saat filter diubah.
          </p>
        </div>

        <button type="button" class="adm-btn adm-btn-sm" @click="resetFilter">
          <RotateCcw :size="12" /> Reset Filter
        </button>
      </div>

      <div class="adm-filters">
        <div class="adm-field">
          <label for="r-instansi">Instansi OPD</label>
          <select id="r-instansi" v-model="filter.instansi" class="adm-select">
            <option value="">Semua Dinas &amp; Instansi</option>
            <option v-for="i in daftarInstansi" :key="i.id" :value="String(i.id)">
              {{ i.nama_instansi }}
            </option>
          </select>
        </div>

        <div class="adm-field">
          <label for="r-jenis">Jenis Laporan</label>
          <select id="r-jenis" v-model="filter.jenis" class="adm-select">
            <option value="">Semua Jenis Laporan</option>
            <option value="pengaduan">Pengaduan</option>
            <option value="informasi">Permohonan Informasi</option>
          </select>
        </div>

        <div class="adm-field">
          <label for="r-status">Status Laporan</label>
          <select id="r-status" v-model="filter.status" class="adm-select">
            <option value="">Semua Status</option>
            <option v-for="s in DAFTAR_STATUS" :key="s" :value="s">
              {{ LABEL_STATUS[s] }}
            </option>
          </select>
        </div>

        <div class="adm-field">
          <label for="r-rentang">Rentang Waktu</label>
          <select id="r-rentang" v-model="filter.rentang" class="adm-select">
            <option v-for="o in OPSI_RENTANG" :key="o.nilai" :value="o.nilai">
              {{ o.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="adm-chips">
        <span><ListFilter :size="11" /> Filter aktif:</span>

        <span v-if="!chipAktif.length">Tidak ada (menampilkan semua)</span>

        <span v-for="c in chipAktif" :key="c.kunci" class="adm-chip">
          {{ c.label }}
          <button type="button" :aria-label="`Hapus ${c.label}`" @click="hapusChip(c.kunci)">
            <X :size="10" />
          </button>
        </span>

        <button
          v-if="chipAktif.length"
          type="button"
          class="adm-link-btn"
          @click="resetFilter"
        >
          Reset Semua Filter
        </button>
      </div>
    </section>

    <!-- ================= TABEL ================= -->
    <section class="adm-card">
      <div class="adm-card-head">
        <div>
          <h2><span class="rk-dot"></span> Data Realtime Rekapitulasi</h2>
          <p>
            Menampilkan {{ angka(dari) }}&ndash;{{ angka(ke) }} dari
            {{ angka(total) }} pengaduan &amp; permohonan
          </p>
        </div>

        <div class="adm-head-actions">
          <div class="rk-massal">
            <button
              type="button"
              class="adm-btn adm-btn-sm"
              @click.stop="menuMassal = !menuMassal"
            >
              Tindakan Massal
              <span v-if="terpilih.length" class="rk-count">{{ terpilih.length }}</span>
              <ChevronDown :size="12" />
            </button>

            <div v-if="menuMassal" class="rk-menu">
              <button type="button" :disabled="sedangMassal" @click="verifikasiTerpilih">
                Verifikasi laporan terpilih
              </button>

              <button type="button" @click="terpilih = []; menuMassal = false">
                Kosongkan pilihan
              </button>
            </div>
          </div>

          <span class="rk-urut"><ArrowUpDown :size="11" /> Urutkan: Waktu Tiket Terbaru</span>
        </div>
      </div>

      <p v-if="pesanMassal" class="adm-alert ok">{{ pesanMassal }}</p>
      <p v-if="galatEkspor" class="adm-alert">{{ galatEkspor }}</p>
      <p v-if="terpotong" class="adm-alert">
        Hanya {{ angka(MAKS_HALAMAN * UKURAN) }} laporan terbaru yang dimuat
        untuk rentang waktu ini. Persempit filter agar hasil lengkap.
      </p>

      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th style="width: 34px">
                <input
                  type="checkbox"
                  :checked="semuaTerpilih"
                  :disabled="!baris.length"
                  aria-label="Pilih semua"
                  @change="pilihSemua"
                />
              </th>
              <th>No. Tiket &amp; Waktu</th>
              <th>Pengadu &amp; Anonimitas</th>
              <th>Jenis &amp; Uraian Laporan</th>
              <th>Instansi OPD</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="memuat">
              <td colspan="7" class="adm-empty">Memuat data laporan...</td>
            </tr>

            <tr v-else-if="error">
              <td colspan="7" class="adm-empty galat">{{ error }}</td>
            </tr>

            <tr v-else-if="!baris.length">
              <td colspan="7" class="adm-empty">
                Tidak ada laporan yang cocok dengan filter.
              </td>
            </tr>

            <tr v-for="p in baris" v-else :key="p.no_tiket">
              <td>
                <input
                  v-model="terpilih"
                  type="checkbox"
                  :value="p.no_tiket"
                  :aria-label="`Pilih ${p.no_tiket}`"
                />
              </td>

              <td>
                <span class="adm-cell-strong">#{{ p.no_tiket }}</span>
                <span class="adm-cell-muted">{{ waktuRelatif(p.dibuat_pada) }}</span>
              </td>

              <td>
                <template v-if="p.samarkan">
                  <span class="adm-cell-strong"><i>Identitas Disamarkan</i></span>
                  <span class="adm-tag netral">ANONIM</span>
                </template>

                <template v-else>
                  <span class="adm-cell-strong">{{ p.nama_pelapor || '-' }}</span>
                  <span class="adm-tag netral">IDENTITAS TERBUKA</span>
                </template>
              </td>

              <td>
                <span class="adm-tag" :class="p.jenis">
                  {{ p.jenis === 'pengaduan' ? 'PENGADUAN' : 'INFORMASI' }}
                </span>
                <span class="adm-cell-title">{{ p.judul }}</span>
              </td>

              <td>{{ p.instansi || '-' }}</td>

              <td>
                <span class="adm-badge" :class="KELAS_STATUS[p.status]">
                  {{ LABEL_STATUS[p.status] || p.status }}
                </span>
              </td>

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
                    v-if="AKSI_UTAMA[p.status]"
                    type="button"
                    class="adm-btn adm-btn-primary adm-btn-sm"
                    @click="tiketDipilih = p.no_tiket"
                  >
                    {{ AKSI_UTAMA[p.status] }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="adm-table-foot">
        <span>
          {{ UKURAN }} per halaman &bull; Halaman {{ halaman }} dari
          {{ halamanTerakhir }}
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

    <!-- ================= ALUR ================= -->
    <div class="rk-note">
      <Info :size="14" />
      <p>
        <b>Alur penanganan laporan:</b> Diterima &rarr; Diverifikasi &rarr;
        Diproses (diteruskan ke instansi lewat email) &rarr; Selesai.
        Laporan hanya dapat ditolak sebelum diteruskan ke instansi.
      </p>
    </div>

    <AdminPengaduanModal
      v-if="tiketDipilih"
      :tiket="tiketDipilih"
      @tutup="tiketDipilih = ''"
      @berubah="muatUlang"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import {
  ArrowUpDown,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  CircleCheck,
  Download,
  FileText,
  Info,
  ListFilter,
  RotateCcw,
  TriangleAlert,
  X,
} from 'lucide-vue-next'

import AdminLayout from '../../components/admin/AdminLayout.vue'
import AdminPengaduanModal from '../../components/admin/AdminPengaduanModal.vue'

import {
  AKSI_UTAMA,
  DAFTAR_STATUS,
  KELAS_STATUS,
  LABEL_STATUS,
  OPSI_RENTANG,
  ambilInstansi,
  ambilPengaduan,
  angka,
  batasRentang,
  hitungRingkasan,
  muatRingkasan,
  ringkasan,
  tglISO,
  unduhEkspor,
  verifikasiPengaduan,
  waktuRelatif,
} from '../../services/admin'

const route = useRoute()

const UKURAN = 15 // jumlah baris per halaman (ditentukan backend)
const MAKS_HALAMAN = 40 // batas pemuatan saat filter rentang waktu

const r = computed(() => hitungRingkasan(ringkasan.data))

const daftarInstansi = ref([])

const filter = reactive({
  instansi: '',
  jenis: '',
  status: String(route.query.status || ''),
  rentang: 'semua',
  q: String(route.query.q || ''),
})

const halaman = ref(1)
const baris = ref([])
const total = ref(0)
const halamanTerakhir = ref(1)
const memuat = ref(true)
const error = ref('')
const terpotong = ref(false)

// Mode rentang waktu: data dimuat sekali lalu dipotong di browser
const semuaKlien = ref(null)

const terpilih = ref([])
const menuMassal = ref(false)
const sedangMassal = ref(false)
const pesanMassal = ref('')

const tiketDipilih = ref('')
const mengekspor = ref(false)
const galatEkspor = ref('')

const dari = computed(() => (total.value ? (halaman.value - 1) * UKURAN + 1 : 0))
const ke = computed(() => (total.value ? dari.value + baris.value.length - 1 : 0))

const semuaTerpilih = computed(
  () =>
    baris.value.length > 0 &&
    baris.value.every((b) => terpilih.value.includes(b.no_tiket)),
)

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

const chipAktif = computed(() => {
  const daftar = []

  if (filter.q) daftar.push({ kunci: 'q', label: `Pencarian: ${filter.q}` })

  if (filter.instansi) {
    const nama = daftarInstansi.value.find(
      (i) => String(i.id) === filter.instansi,
    )?.nama_instansi

    daftar.push({ kunci: 'instansi', label: nama || 'Instansi terpilih' })
  }

  if (filter.jenis) {
    daftar.push({
      kunci: 'jenis',
      label: filter.jenis === 'pengaduan' ? 'Pengaduan' : 'Permohonan Informasi',
    })
  }

  if (filter.status) {
    daftar.push({ kunci: 'status', label: `Status: ${LABEL_STATUS[filter.status]}` })
  }

  if (filter.rentang !== 'semua') {
    daftar.push({
      kunci: 'rentang',
      label: OPSI_RENTANG.find((o) => o.nilai === filter.rentang)?.label,
    })
  }

  return daftar
})

const persen = (n) => String(n ?? 0).replace('.', ',')

function parameter() {
  const params = {}

  if (filter.instansi) params.instansi = filter.instansi
  if (filter.jenis) params.jenis = filter.jenis
  if (filter.status) params.status = filter.status
  if (filter.q) params.q = filter.q

  return params
}

// Potong data klien sesuai halaman aktif
function potongKlien() {
  const semua = semuaKlien.value || []

  total.value = semua.length
  halamanTerakhir.value = Math.max(1, Math.ceil(semua.length / UKURAN))

  if (halaman.value > halamanTerakhir.value) halaman.value = halamanTerakhir.value

  baris.value = semua.slice((halaman.value - 1) * UKURAN, halaman.value * UKURAN)
}

// GET /api/admin/pengaduan?status=&instansi=&jenis=&q=&page=
async function muat() {
  memuat.value = true
  error.value = ''
  terpotong.value = false

  try {
    const batas = batasRentang(filter.rentang)

    if (!batas) {
      // Tanpa rentang waktu: paginasi langsung dari server
      semuaKlien.value = null

      const h = await ambilPengaduan({ ...parameter(), page: halaman.value })

      baris.value = h.data || []
      total.value = h.total || 0
      halamanTerakhir.value = h.last_page || 1

      return
    }

    // Dengan rentang waktu: ambil halaman demi halaman (urut terbaru)
    // dan berhenti begitu data lebih lama dari batas.
    const kumpul = []
    let p = 1
    let last = 1
    let selesai = false

    while (!selesai && p <= MAKS_HALAMAN) {
      const h = await ambilPengaduan({ ...parameter(), page: p })
      const data = h.data || []

      last = h.last_page || 1

      for (const b of data) {
        if (new Date(b.dibuat_pada) >= batas) kumpul.push(b)
      }

      const terakhir = data[data.length - 1]

      if (!terakhir || new Date(terakhir.dibuat_pada) < batas || p >= last) {
        selesai = true
      } else {
        p++
      }
    }

    terpotong.value = !selesai
    semuaKlien.value = kumpul
    potongKlien()
  } catch (e) {
    error.value =
      e.response?.data?.message || 'Data laporan gagal dimuat dari server.'
    baris.value = []
    total.value = 0
  } finally {
    memuat.value = false
  }
}

function gantiHalaman(n) {
  if (n < 1 || n > halamanTerakhir.value || n === halaman.value) return

  halaman.value = n

  if (semuaKlien.value) potongKlien()
  else muat()
}

function muatUlang() {
  muatRingkasan()
  muat()
}

function pilihSemua(event) {
  terpilih.value = event.target.checked ? baris.value.map((b) => b.no_tiket) : []
}

function hapusChip(kunci) {
  filter[kunci] = kunci === 'rentang' ? 'semua' : ''
}

function resetFilter() {
  filter.instansi = ''
  filter.jenis = ''
  filter.status = ''
  filter.rentang = 'semua'
  filter.q = ''
}

// Verifikasi massal: hanya untuk laporan berstatus "diterima"
async function verifikasiTerpilih() {
  menuMassal.value = false
  pesanMassal.value = ''

  const target = baris.value.filter(
    (b) => terpilih.value.includes(b.no_tiket) && b.status === 'diterima',
  )

  if (!target.length) {
    pesanMassal.value =
      'Pilih minimal satu laporan berstatus Diterima untuk diverifikasi.'
    return
  }

  if (!window.confirm(`Verifikasi ${target.length} laporan terpilih?`)) return

  sedangMassal.value = true

  let berhasil = 0
  let gagal = 0

  for (const b of target) {
    try {
      await verifikasiPengaduan(b.no_tiket, 'Verifikasi massal oleh Super Admin')
      berhasil++
    } catch {
      gagal++
    }
  }

  pesanMassal.value =
    `${berhasil} laporan berhasil diverifikasi` +
    (gagal ? `, ${gagal} gagal.` : '.')

  terpilih.value = []
  sedangMassal.value = false

  muatUlang()
}

// GET /api/admin/export (CSV). Backend mendukung: status, instansi, dari, sampai
async function ekspor() {
  mengekspor.value = true
  galatEkspor.value = ''

  try {
    const batas = batasRentang(filter.rentang)

    await unduhEkspor({
      status: filter.status,
      instansi: filter.instansi,
      dari: batas ? tglISO(batas) : '',
      sampai: batas ? tglISO(new Date()) : '',
    })
  } catch (e) {
    galatEkspor.value = 'Ekspor data gagal. Coba lagi.'
    console.error(e)
  } finally {
    mengekspor.value = false
  }
}

// Ubah filter -> kembali ke halaman 1
watch(
  () => [filter.instansi, filter.jenis, filter.status, filter.rentang, filter.q],
  () => {
    halaman.value = 1
    terpilih.value = []
    muat()
  },
)

// Pencarian dari kotak cari di topbar (?q=...) atau lonceng (?status=...)
watch(
  () => [route.query.q, route.query.status],
  ([q, status]) => {
    filter.q = String(q || '')
    filter.status = String(status || '')
  },
)

function tutupMenu() {
  menuMassal.value = false
}

onMounted(async () => {
  muatRingkasan()
  muat()

  document.addEventListener('click', tutupMenu)

  try {
    daftarInstansi.value = await ambilInstansi()
  } catch (e) {
    console.error('Gagal memuat instansi:', e)
  }
})
</script>

<style scoped>
.rk-dot {
  display: inline-block;
  width: 6px;
  height: 6px;
  margin-right: 6px;
  border-radius: 50%;
  background: #b51220;
  vertical-align: middle;
}

.rk-urut {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #7d6e6f;
  font-size: 10px;
}

.rk-massal {
  position: relative;
}

.rk-count {
  display: inline-grid;
  place-items: center;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 99px;
  background: #b51220;
  color: #fff;
  font-size: 9px;
}

.rk-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  z-index: 20;
  display: flex;
  min-width: 210px;
  flex-direction: column;
  padding: 6px;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 12px 28px rgba(60, 20, 20, 0.14);
}

.rk-menu button {
  padding: 9px 10px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #2b1f20;
  font-size: 11px;
  text-align: left;
  cursor: pointer;
}

.rk-menu button:hover:not(:disabled) {
  background: #fff6f6;
}

.rk-note {
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

.rk-note p {
  color: #6b4a4c;
  font-size: 10.5px;
  line-height: 1.6;
}

.rk-note b {
  color: #b51220;
}
</style>
