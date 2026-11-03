<template>
  <AdminLayout>
    <!-- ================= JUDUL ================= -->
    <div class="adm-head">
      <div>
        <div class="adm-title-row">
          <h1 class="adm-title">Pusat Kendali Super Admin</h1>

          <span class="adm-live" :class="{ 'adm-live-red': ringkasan.error }">
            {{ ringkasan.error ? 'Offline' : 'Realtime Sinkron' }}
          </span>
        </div>

        <p class="adm-subtitle">
          Kelola operasional pelaporan warga Kota Banjarmasin, verifikasi
          laporan masuk, dan disposisikan ke instansi terkait.
        </p>
      </div>

      <div class="adm-head-actions">
        <RouterLink :to="{ name: 'admin-rekap' }" class="adm-btn">
          <ListFilter :size="13" /> Filter Pengaduan
        </RouterLink>

        <RouterLink
          :to="{ name: 'admin-akun', query: { tambah: '1' } }"
          class="adm-btn adm-btn-primary"
        >
          <UserPlus :size="13" /> Tambah Akun Admin
        </RouterLink>
      </div>
    </div>

    <p v-if="ringkasan.error" class="adm-alert pad">
      {{ ringkasan.error }} Pastikan backend Laravel berjalan.
    </p>

    <!-- ================= KPI ================= -->
    <section class="adm-kpis">
      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Total Aduan Masuk
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
          Akun Admin Instansi
          <span class="adm-kpi-icon"><Users :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ angka(daftarAkun.length) }}
          <small>/ {{ angka(daftarInstansi.length) }} instansi</small>
        </div>
        <div class="adm-kpi-sub">
          <span class="hijau">{{ angka(instansiBerakun) }} instansi berakun</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Kepuasan Masyarakat
          <span class="adm-kpi-icon"><Star :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ r.rataKepuasan ?? '-' }}
          <small>dari 4,0</small>
        </div>
        <div class="adm-kpi-sub">
          <span>{{ angka(r.responden) }} responden evaluasi</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          Tingkat Penyelesaian
          <span class="adm-kpi-icon"><CircleCheck :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ String(r.persenSelesai).replace('.', ',') }}<small>%</small>
        </div>
        <div class="adm-kpi-sub">
          <span>{{ angka(r.diterima) }} laporan menunggu verifikasi</span>
        </div>
      </div>
    </section>

    <!-- ================= PANTAU PENGADUAN ================= -->
    <section class="adm-card" style="margin-bottom: 20px">
      <div class="adm-card-head">
        <div>
          <h2>Filter &amp; Pantau Pengaduan Lintas OPD</h2>
          <p>
            Pantau laporan masuk warga dari seluruh dinas dan tindak lanjuti
            secara langsung.
          </p>
        </div>

        <button
          type="button"
          class="adm-btn adm-btn-sm"
          :disabled="mengekspor"
          @click="ekspor"
        >
          <Download :size="12" />
          {{ mengekspor ? 'Mengunduh...' : 'Ekspor Data' }}
        </button>
      </div>

      <div class="adm-filters">
        <div class="adm-field">
          <label for="f-instansi">Instansi OPD</label>
          <select id="f-instansi" v-model="filter.instansi" class="adm-select">
            <option value="">Semua Dinas &amp; Instansi</option>
            <option v-for="i in daftarInstansi" :key="i.id" :value="String(i.id)">
              {{ i.nama_instansi }}
            </option>
          </select>
        </div>

        <div class="adm-field">
          <label for="f-jenis">Jenis Laporan</label>
          <select id="f-jenis" v-model="filter.jenis" class="adm-select">
            <option value="">Semua Jenis</option>
            <option value="pengaduan">Pengaduan</option>
            <option value="informasi">Permohonan Informasi</option>
          </select>
        </div>

        <div class="adm-field">
          <label for="f-status">Status Laporan</label>
          <select id="f-status" v-model="filter.status" class="adm-select">
            <option value="">Semua Status</option>
            <option v-for="s in DAFTAR_STATUS" :key="s" :value="s">
              {{ LABEL_STATUS[s] }}
            </option>
          </select>
        </div>

        <div class="adm-field">
          <label for="f-baris">Jumlah Baris</label>
          <select id="f-baris" v-model="filter.baris" class="adm-select">
            <option value="5">5 laporan terbaru</option>
            <option value="10">10 laporan terbaru</option>
            <option value="15">15 laporan terbaru</option>
          </select>
        </div>
      </div>

      <p v-if="galatEkspor" class="adm-alert">{{ galatEkspor }}</p>

      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>ID Tiket &amp; Tanggal</th>
              <th>Instansi OPD</th>
              <th>Judul Laporan</th>
              <th>Jenis</th>
              <th>Status</th>
              <th>Aksi Tindak Lanjut</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="memuatTabel">
              <td colspan="6" class="adm-empty">Memuat laporan...</td>
            </tr>

            <tr v-else-if="errorTabel">
              <td colspan="6" class="adm-empty galat">{{ errorTabel }}</td>
            </tr>

            <tr v-else-if="!tampil.length">
              <td colspan="6" class="adm-empty">
                Tidak ada laporan yang cocok dengan filter.
              </td>
            </tr>

            <tr v-for="p in tampil" v-else :key="p.no_tiket">
              <td>
                <span class="adm-cell-strong">#{{ p.no_tiket }}</span>
                <span class="adm-cell-muted">{{ waktuRelatif(p.dibuat_pada) }}</span>
              </td>

              <td>{{ p.instansi || '-' }}</td>

              <td>
                <span class="adm-cell-title">{{ p.judul }}</span>
              </td>

              <td>
                <span class="adm-tag" :class="p.jenis">
                  {{ p.jenis === 'pengaduan' ? 'PENGADUAN' : 'INFORMASI' }}
                </span>
              </td>

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
          Menampilkan {{ tampil.length }} dari {{ angka(totalTabel) }} laporan
        </span>

        <RouterLink :to="{ name: 'admin-rekap' }" class="adm-link-btn" style="margin-left: 0">
          Lihat semua rekap pengaduan &rarr;
        </RouterLink>
      </div>
    </section>

    <!-- ================= AKUN ADMIN ================= -->
    <section class="adm-card">
      <div class="adm-card-head">
        <div>
          <h2>Kelola Cepat Akun Admin &amp; Verifikator OPD</h2>
          <p>
            Daftar personel penanggung jawab instansi, kelola akun dan akses
            sistem.
          </p>
        </div>

        <span class="adm-cell-muted">
          Total {{ angka(daftarAkun.length) }} Akun Terdaftar
        </span>
      </div>

      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Nama &amp; Email Admin</th>
              <th>Instansi Terkait</th>
              <th>Peran Akses</th>
              <th>Terdaftar</th>
              <th>Status Akun</th>
              <th>Kelola</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="memuatAkun">
              <td colspan="6" class="adm-empty">Memuat akun...</td>
            </tr>

            <tr v-else-if="!akunCepat.length">
              <td colspan="6" class="adm-empty">
                Belum ada akun admin instansi.
              </td>
            </tr>

            <tr v-for="a in akunCepat" v-else :key="a.id">
              <td>
                <span class="adm-cell-strong">{{ a.nama }}</span>
                <span class="adm-cell-muted">{{ a.email }}</span>
              </td>

              <td>{{ a.instansi || '-' }}</td>

              <td><span class="adm-tag netral">ADMIN OPD</span></td>

              <td>{{ formatTanggal(a.dibuat_pada) }}</td>

              <td><span class="adm-badge st-green">Aktif</span></td>

              <td>
                <RouterLink
                  :to="{ name: 'admin-akun', query: { edit: a.id } }"
                  class="adm-icon-btn"
                  title="Ubah akun"
                >
                  <Pencil :size="14" />
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="adm-table-foot">
        <span></span>

        <RouterLink :to="{ name: 'admin-akun' }" class="adm-link-btn" style="margin-left: 0">
          Kelola semua akun &rarr;
        </RouterLink>
      </div>
    </section>

    <AdminPengaduanModal
      v-if="tiketDipilih"
      :tiket="tiketDipilih"
      @tutup="tiketDipilih = ''"
      @berubah="muatSemua"
    />
  </AdminLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

import {
  CircleCheck,
  Download,
  FileText,
  ListFilter,
  Pencil,
  Star,
  UserPlus,
  Users,
} from 'lucide-vue-next'

import AdminLayout from '../../components/admin/AdminLayout.vue'
import AdminPengaduanModal from '../../components/admin/AdminPengaduanModal.vue'

import {
  AKSI_UTAMA,
  DAFTAR_STATUS,
  KELAS_STATUS,
  LABEL_STATUS,
  ambilAkun,
  ambilInstansi,
  ambilPengaduan,
  angka,
  formatTanggal,
  hitungRingkasan,
  muatRingkasan,
  ringkasan,
  unduhEkspor,
  waktuRelatif,
} from '../../services/admin'

const r = computed(() => hitungRingkasan(ringkasan.data))

const daftarInstansi = ref([])
const daftarAkun = ref([])
const memuatAkun = ref(true)

const semuaBaris = ref([])
const totalTabel = ref(0)
const memuatTabel = ref(true)
const errorTabel = ref('')

const filter = reactive({ instansi: '', jenis: '', status: '', baris: '5' })

const tiketDipilih = ref('')
const mengekspor = ref(false)
const galatEkspor = ref('')

let timer = null

const tampil = computed(() => semuaBaris.value.slice(0, Number(filter.baris)))

const akunCepat = computed(() => daftarAkun.value.slice(0, 4))

const instansiBerakun = computed(
  () => daftarInstansi.value.filter((i) => i.admin_login).length,
)

// GET /api/admin/pengaduan (halaman pertama, urut terbaru)
async function muatTabel() {
  memuatTabel.value = true
  errorTabel.value = ''

  try {
    const params = {}

    if (filter.instansi) params.instansi = filter.instansi
    if (filter.jenis) params.jenis = filter.jenis
    if (filter.status) params.status = filter.status

    const hasil = await ambilPengaduan(params)

    semuaBaris.value = hasil.data || []
    totalTabel.value = hasil.total || 0
  } catch (e) {
    errorTabel.value =
      e.response?.data?.message || 'Daftar laporan gagal dimuat.'
  } finally {
    memuatTabel.value = false
  }
}

// GET /api/admin/users  &  GET /api/admin/instansi
async function muatAkunDanInstansi() {
  try {
    const [akun, instansi] = await Promise.all([ambilAkun(), ambilInstansi()])

    daftarAkun.value = akun
    daftarInstansi.value = instansi
  } catch (e) {
    console.error('Gagal memuat akun/instansi:', e)
  } finally {
    memuatAkun.value = false
  }
}

function muatSemua() {
  muatRingkasan()
  muatTabel()
  muatAkunDanInstansi()
}

async function ekspor() {
  mengekspor.value = true
  galatEkspor.value = ''

  try {
    await unduhEkspor({
      status: filter.status,
      instansi: filter.instansi,
    })
  } catch (e) {
    galatEkspor.value = 'Ekspor data gagal. Coba lagi.'
    console.error(e)
  } finally {
    mengekspor.value = false
  }
}

watch(() => [filter.instansi, filter.jenis, filter.status], muatTabel)

onMounted(() => {
  muatSemua()

  // "Realtime": segarkan otomatis tiap 60 detik
  timer = setInterval(() => {
    if (!tiketDipilih.value) muatSemua()
  }, 60000)
})

onBeforeUnmount(() => clearInterval(timer))
</script>
