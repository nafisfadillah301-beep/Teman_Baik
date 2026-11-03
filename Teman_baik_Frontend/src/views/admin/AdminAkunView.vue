<template>
  <AdminLayout>
    <p class="adm-breadcrumb">
      Beranda &rsaquo; Super Admin &rsaquo; <b>Kelola Akun Admin</b>
    </p>

    <!-- ================= JUDUL ================= -->
    <div class="adm-head">
      <div>
        <div class="adm-title-row">
          <h1 class="adm-title bar">Kelola Akun Admin</h1>

          <span class="adm-live" :class="{ 'adm-live-red': error }">
            {{ error ? 'Offline' : 'Sinkronisasi Aktif' }}
          </span>
        </div>

        <p class="adm-subtitle">
          Manajemen hak akses dan akun verifikator / admin OPD pengelola
          aduan masyarakat Kota Banjarmasin.
        </p>
      </div>

      <div class="adm-head-actions">
        <button type="button" class="adm-btn" @click="eksporDaftar">
          <Download :size="13" /> Ekspor Daftar Akun
        </button>

        <button type="button" class="adm-btn adm-btn-primary" @click="bukaTambah">
          <UserPlus :size="13" /> Tambah Akun Baru
        </button>
      </div>
    </div>

    <p v-if="pesanSukses" class="adm-alert ok pad">{{ pesanSukses }}</p>
    <p v-if="galatAksi" class="adm-alert pad">{{ galatAksi }}</p>

    <!-- ================= KPI ================= -->
    <section class="adm-kpis tiga">
      <div class="adm-kpi">
        <div class="adm-kpi-top">
          TOTAL AKUN ADMIN OPD
          <span class="adm-kpi-icon"><Users :size="14" /></span>
        </div>
        <div class="adm-kpi-value">{{ angka(akun.length) }} <small>Akun</small></div>
        <div class="adm-kpi-sub"><span>Akun admin instansi yang dapat login</span></div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          INSTANSI BERAKUN
          <span class="adm-kpi-icon"><Building2 :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ angka(instansiBerakun) }} <small>dari {{ angka(instansi.length) }} Dinas</small>
        </div>
        <div class="adm-kpi-sub">
          <span class="hijau">{{ persenBerakun }}% instansi sudah terhubung</span>
        </div>
      </div>

      <div class="adm-kpi">
        <div class="adm-kpi-top">
          BELUM PUNYA AKUN
          <span class="adm-kpi-icon"><TriangleAlert :size="14" /></span>
        </div>
        <div class="adm-kpi-value">
          {{ angka(instansi.length - instansiBerakun) }} <small>Instansi</small>
        </div>
        <div class="adm-kpi-sub"><span>Belum dapat menindaklanjuti laporan</span></div>
      </div>
    </section>

    <!-- ================= TABEL ================= -->
    <section class="adm-card">
      <div class="ak-toolbar">
        <div class="ak-search">
          <Search :size="13" />
          <input
            v-model.trim="cari"
            type="text"
            placeholder="Cari nama admin, username, atau email..."
            aria-label="Cari akun"
          />
        </div>

        <select v-model="filterInstansi" class="adm-select ak-select" aria-label="Filter instansi">
          <option value="">Semua Instansi OPD</option>
          <option v-for="i in instansi" :key="i.id" :value="String(i.id)">
            {{ i.nama_instansi }}
          </option>
        </select>

        <button type="button" class="adm-btn adm-btn-sm" @click="resetFilter">
          <RotateCcw :size="12" /> Reset
        </button>
      </div>

      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Admin / Profil</th>
              <th>Instansi / OPD</th>
              <th>Peran / Hak Akses</th>
              <th>Terdaftar</th>
              <th>Status Akun</th>
              <th>Kelola</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="memuat">
              <td colspan="6" class="adm-empty">Memuat daftar akun...</td>
            </tr>

            <tr v-else-if="error">
              <td colspan="6" class="adm-empty galat">{{ error }}</td>
            </tr>

            <tr v-else-if="!tampilan.length">
              <td colspan="6" class="adm-empty">
                {{ akun.length ? 'Tidak ada akun yang cocok dengan pencarian.' : 'Belum ada akun admin instansi. Klik "Tambah Akun Baru".' }}
              </td>
            </tr>

            <tr v-for="a in tampilan" v-else :key="a.id">
              <td>
                <div class="ak-profil">
                  <span class="ak-avatar">{{ inisial(a.nama) }}</span>

                  <div>
                    <span class="adm-cell-strong">{{ a.nama }}</span>
                    <span class="adm-cell-muted">@{{ a.username }}</span>
                    <span class="adm-cell-muted">{{ a.email }}</span>
                  </div>
                </div>
              </td>

              <td><span class="adm-cell-strong">{{ a.instansi || '-' }}</span></td>

              <td><span class="adm-tag netral">ADMIN OPD / VERIFIKATOR</span></td>

              <td>{{ formatTanggal(a.dibuat_pada) }}</td>

              <td><span class="adm-badge st-green">Aktif</span></td>

              <td>
                <div class="adm-actions">
                  <button
                    type="button"
                    class="adm-icon-btn"
                    title="Ubah akun"
                    :aria-label="`Ubah ${a.nama}`"
                    @click="bukaEdit(a)"
                  >
                    <Pencil :size="14" />
                  </button>

                  <button
                    type="button"
                    class="adm-icon-btn ak-hapus"
                    title="Hapus akun"
                    :aria-label="`Hapus ${a.nama}`"
                    :disabled="menghapus === a.id"
                    @click="hapus(a)"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="adm-table-foot">
        <span>
          Menampilkan {{ tersaring.length ? (halaman - 1) * UKURAN + 1 : 0 }}&ndash;{{
            Math.min(halaman * UKURAN, tersaring.length)
          }}
          dari {{ angka(tersaring.length) }} akun
        </span>

        <div class="adm-pager">
          <button
            type="button"
            :disabled="halaman <= 1"
            aria-label="Sebelumnya"
            @click="halaman--"
          >
            <ChevronLeft :size="13" />
          </button>

          <button
            v-for="n in jumlahHalaman"
            :key="n"
            type="button"
            :class="{ aktif: n === halaman }"
            @click="halaman = n"
          >
            {{ n }}
          </button>

          <button
            type="button"
            :disabled="halaman >= jumlahHalaman"
            aria-label="Berikutnya"
            @click="halaman++"
          >
            <ChevronRight :size="13" />
          </button>
        </div>
      </div>
    </section>

    <!-- ================= KETENTUAN ================= -->
    <div class="ak-note">
      <ShieldCheck :size="15" />

      <div>
        <strong>Ketentuan Keamanan &amp; Hak Akses Tingkat Super Admin</strong>
        <p>
          Setiap instansi hanya dapat memiliki satu akun admin yang bisa
          login. Penghapusan akun bersifat permanen. Kata sandi minimal 8
          karakter; untuk mereset kata sandi, isi kolom kata sandi baru saat
          mengubah akun.
        </p>
      </div>
    </div>

    <!-- ================= MODAL FORM ================= -->
    <div v-if="modal.buka" class="adm-overlay" @click.self="tutupModal">
      <form class="adm-modal ak-modal" novalidate @submit.prevent="simpan">
        <div class="adm-modal-head">
          <div>
            <h2>{{ modal.mode === 'tambah' ? 'Tambah Akun Admin Instansi' : 'Ubah Akun Admin Instansi' }}</h2>
            <p class="adm-cell-muted">
              Akun ini dipakai petugas instansi untuk masuk ke sistem.
            </p>
          </div>

          <button type="button" class="adm-icon-btn" aria-label="Tutup" @click="tutupModal">
            <X :size="17" />
          </button>
        </div>

        <div class="adm-modal-body">
          <p v-if="modal.umum" class="adm-alert pad">{{ modal.umum }}</p>

          <div class="ak-grid">
            <div class="adm-field ak-full">
              <label for="m-nama">Nama Lengkap</label>
              <input id="m-nama" v-model.trim="modal.form.nama" class="adm-input" type="text" maxlength="255" />
              <span v-if="modal.errors.nama" class="adm-field-error">{{ modal.errors.nama }}</span>
            </div>

            <div class="adm-field">
              <label for="m-username">Username</label>
              <input
                id="m-username"
                v-model.trim="modal.form.username"
                class="adm-input"
                type="text"
                maxlength="50"
                autocomplete="off"
              />
              <span v-if="modal.errors.username" class="adm-field-error">{{ modal.errors.username }}</span>
            </div>

            <div class="adm-field">
              <label for="m-email">Email</label>
              <input id="m-email" v-model.trim="modal.form.email" class="adm-input" type="email" maxlength="255" />
              <span v-if="modal.errors.email" class="adm-field-error">{{ modal.errors.email }}</span>
            </div>

            <div class="adm-field ak-full">
              <label for="m-instansi">Instansi / OPD</label>
              <select id="m-instansi" v-model="modal.form.id_instansi" class="adm-select">
                <option value="" disabled>Pilih instansi</option>
                <option v-for="i in instansiTersedia" :key="i.id" :value="String(i.id)">
                  {{ i.nama_instansi }}
                </option>
              </select>
              <span v-if="modal.errors.id_instansi" class="adm-field-error">{{ modal.errors.id_instansi }}</span>
              <span v-else-if="!instansiTersedia.length" class="adm-cell-muted">
                Semua instansi sudah memiliki akun admin.
              </span>
            </div>

            <div class="adm-field ak-full">
              <label for="m-password">
                {{ modal.mode === 'tambah' ? 'Kata Sandi' : 'Kata Sandi Baru (kosongkan bila tidak diubah)' }}
              </label>

              <div class="ak-sandi">
                <input
                  id="m-password"
                  v-model="modal.form.password"
                  class="adm-input"
                  :type="modal.lihatSandi ? 'text' : 'password'"
                  maxlength="100"
                  autocomplete="new-password"
                />

                <button
                  type="button"
                  class="adm-icon-btn"
                  :aria-label="modal.lihatSandi ? 'Sembunyikan' : 'Tampilkan'"
                  @click="modal.lihatSandi = !modal.lihatSandi"
                >
                  <EyeOff v-if="modal.lihatSandi" :size="14" />
                  <Eye v-else :size="14" />
                </button>
              </div>

              <span v-if="modal.errors.password" class="adm-field-error">{{ modal.errors.password }}</span>
            </div>
          </div>
        </div>

        <div class="adm-modal-foot">
          <button type="button" class="adm-btn" :disabled="modal.menyimpan" @click="tutupModal">
            Batal
          </button>

          <button type="submit" class="adm-btn adm-btn-primary" :disabled="modal.menyimpan">
            {{ modal.menyimpan ? 'Menyimpan...' : modal.mode === 'tambah' ? 'Buat Akun' : 'Simpan Perubahan' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  Building2,
  ChevronLeft,
  ChevronRight,
  Download,
  Eye,
  EyeOff,
  Pencil,
  RotateCcw,
  Search,
  ShieldCheck,
  Trash2,
  TriangleAlert,
  UserPlus,
  Users,
  X,
} from 'lucide-vue-next'

import AdminLayout from '../../components/admin/AdminLayout.vue'

import { pesanError } from '../../services/api'
import {
  ambilAkun,
  ambilInstansi,
  angka,
  bangunCsv,
  buatAkun,
  formatTanggal,
  hapusAkun,
  inisial,
  simpanTeks,
  ubahAkun,
} from '../../services/admin'

const route = useRoute()
const router = useRouter()

const UKURAN = 6

const akun = ref([])
const instansi = ref([])
const memuat = ref(true)
const error = ref('')

const cari = ref('')
const filterInstansi = ref('')
const halaman = ref(1)

const pesanSukses = ref('')
const galatAksi = ref('')
const menghapus = ref(null)

const modal = reactive({
  buka: false,
  mode: 'tambah',
  id: null,
  form: kosong(),
  awal: kosong(),
  errors: {},
  umum: '',
  menyimpan: false,
  lihatSandi: false,
})

function kosong() {
  return { nama: '', username: '', email: '', password: '', id_instansi: '' }
}

const instansiBerakun = computed(
  () => instansi.value.filter((i) => i.admin_login).length,
)

const persenBerakun = computed(() =>
  instansi.value.length
    ? Math.round((instansiBerakun.value / instansi.value.length) * 100)
    : 0,
)

// Saat menambah: hanya instansi tanpa akun. Saat mengubah: ditambah instansi milik akun itu
const instansiTersedia = computed(() =>
  instansi.value.filter(
    (i) => !i.admin_login || i.admin_login.id === modal.id,
  ),
)

const tersaring = computed(() => {
  const kata = cari.value.toLowerCase()

  return akun.value.filter((a) => {
    const cocokKata =
      !kata ||
      [a.nama, a.username, a.email, a.instansi]
        .filter(Boolean)
        .some((v) => String(v).toLowerCase().includes(kata))

    const cocokInstansi =
      !filterInstansi.value || String(a.id_instansi) === filterInstansi.value

    return cocokKata && cocokInstansi
  })
})

const jumlahHalaman = computed(() =>
  Math.max(1, Math.ceil(tersaring.value.length / UKURAN)),
)

const tampilan = computed(() =>
  tersaring.value.slice((halaman.value - 1) * UKURAN, halaman.value * UKURAN),
)

watch([cari, filterInstansi], () => {
  halaman.value = 1
})

watch(jumlahHalaman, (n) => {
  if (halaman.value > n) halaman.value = n
})

// GET /api/admin/users  &  GET /api/admin/instansi
async function muat() {
  memuat.value = true
  error.value = ''

  try {
    const [daftarAkun, daftarInstansi] = await Promise.all([
      ambilAkun(),
      ambilInstansi(),
    ])

    akun.value = daftarAkun
    instansi.value = daftarInstansi
  } catch (e) {
    error.value = pesanError(e, 'Daftar akun gagal dimuat')
  } finally {
    memuat.value = false
  }
}

function resetFilter() {
  cari.value = ''
  filterInstansi.value = ''
}

function bukaTambah() {
  modal.mode = 'tambah'
  modal.id = null
  modal.form = kosong()
  modal.awal = kosong()
  modal.errors = {}
  modal.umum = ''
  modal.lihatSandi = false
  modal.buka = true
}

function bukaEdit(a) {
  modal.mode = 'ubah'
  modal.id = a.id
  modal.form = {
    nama: a.nama || '',
    username: a.username || '',
    email: a.email || '',
    password: '',
    id_instansi: String(a.id_instansi ?? ''),
  }
  modal.awal = { ...modal.form }
  modal.errors = {}
  modal.umum = ''
  modal.lihatSandi = false
  modal.buka = true
}

function tutupModal() {
  if (!modal.menyimpan) modal.buka = false
}

// Ambil pesan pertama tiap field dari respons 422 Laravel
function petakanError(errors = {}) {
  const hasil = {}

  for (const [kunci, pesan] of Object.entries(errors)) {
    hasil[kunci] = Array.isArray(pesan) ? pesan[0] : pesan
  }

  return hasil
}

// POST /api/admin/users  atau  PUT /api/admin/users/{id}
async function simpan() {
  modal.errors = {}
  modal.umum = ''

  const f = modal.form
  const tambah = modal.mode === 'tambah'

  // Validasi ringan di browser (aturan lengkap tetap di backend)
  if (!f.nama) modal.errors.nama = 'Nama wajib diisi.'
  if (!f.username) modal.errors.username = 'Username wajib diisi.'
  else if (!/^[A-Za-z0-9._-]+$/.test(f.username)) {
    modal.errors.username = 'Username hanya boleh huruf, angka, titik, strip, dan garis bawah.'
  }
  if (!f.email) modal.errors.email = 'Email wajib diisi.'
  if (!f.id_instansi) modal.errors.id_instansi = 'Instansi wajib dipilih.'
  if (tambah && !f.password) modal.errors.password = 'Kata sandi wajib diisi.'
  if (f.password && f.password.length < 8) {
    modal.errors.password = 'Kata sandi minimal 8 karakter.'
  }

  if (Object.keys(modal.errors).length) return

  // Saat mengubah, kirim hanya field yang berubah
  const payload = {}

  for (const kunci of Object.keys(f)) {
    if (tambah || f[kunci] !== modal.awal[kunci]) {
      if (kunci === 'password' && !f.password) continue
      payload[kunci] = f[kunci]
    }
  }

  if (!tambah && !Object.keys(payload).length) {
    modal.umum = 'Tidak ada perubahan untuk disimpan.'
    return
  }

  modal.menyimpan = true

  try {
    if (tambah) await buatAkun(payload)
    else await ubahAkun(modal.id, payload)

    modal.buka = false
    pesanSukses.value = tambah
      ? 'Akun admin instansi berhasil dibuat.'
      : 'Akun admin instansi berhasil diperbarui.'
    galatAksi.value = ''

    await muat()
  } catch (e) {
    console.error('Simpan akun gagal:', e.response?.data || e)

    if (e.response?.status === 422 && e.response.data?.errors) {
      modal.errors = petakanError(e.response.data.errors)
    } else {
      modal.umum = pesanError(e, 'Akun gagal disimpan')
    }
  } finally {
    modal.menyimpan = false
  }
}

// DELETE /api/admin/users/{id}
async function hapus(a) {
  if (
    !window.confirm(
      `Hapus akun "${a.nama}" (${a.instansi || 'tanpa instansi'})? Tindakan ini tidak dapat dibatalkan.`,
    )
  ) {
    return
  }

  menghapus.value = a.id
  pesanSukses.value = ''
  galatAksi.value = ''

  try {
    await hapusAkun(a.id)

    pesanSukses.value = `Akun "${a.nama}" berhasil dihapus.`

    await muat()
  } catch (e) {
    galatAksi.value = pesanError(e, 'Akun gagal dihapus')
  } finally {
    menghapus.value = null
  }
}

// Ekspor daftar akun (CSV, dibuat di browser)
function eksporDaftar() {
  const csv = bangunCsv(
    [
      { judul: 'Nama', kunci: 'nama' },
      { judul: 'Username', kunci: 'username' },
      { judul: 'Email', kunci: 'email' },
      { judul: 'Instansi', kunci: 'instansi' },
      { judul: 'Terdaftar', kunci: 'terdaftar' },
    ],
    tersaring.value.map((a) => ({
      ...a,
      terdaftar: formatTanggal(a.dibuat_pada),
    })),
  )

  simpanTeks('daftar_akun_admin.csv', csv)
}

onMounted(async () => {
  await muat()

  // Dari dashboard: /admin/akun?tambah=1  atau  /admin/akun?edit=ID
  if (route.query.tambah) {
    bukaTambah()
  } else if (route.query.edit) {
    const target = akun.value.find((a) => String(a.id) === String(route.query.edit))

    if (target) bukaEdit(target)
  }

  if (route.query.tambah || route.query.edit) {
    router.replace({ name: 'admin-akun', query: {} })
  }
})
</script>

<style scoped>
.ak-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
}

.ak-search {
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

.ak-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #2b1f20;
  font-size: 11.5px;
}

.ak-select {
  width: auto;
  min-width: 190px;
  border-radius: 99px;
}

.ak-profil {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ak-avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #fde9e9;
  color: #b51220;
  font-size: 10.5px;
  font-weight: 800;
}

.ak-hapus {
  color: #b51220;
}

.ak-note {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-top: 20px;
  padding: 14px 18px;
  border: 1px solid #f1d9d9;
  border-radius: 12px;
  background: #fff3f3;
  color: #b51220;
}

.ak-note strong {
  display: block;
  margin-bottom: 4px;
  color: #2b1f20;
  font-size: 11px;
}

.ak-note p {
  color: #6b4a4c;
  font-size: 10.5px;
  line-height: 1.6;
}

.ak-modal {
  max-width: 560px;
}

.ak-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.ak-full {
  grid-column: 1 / -1;
}

.ak-sandi {
  display: flex;
  align-items: center;
  gap: 6px;
}

@media (max-width: 560px) {
  .ak-grid {
    grid-template-columns: 1fr;
  }
}
</style>
