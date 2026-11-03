
<template>
  <div class="aduan-page">
    <header class="navbar">
      <RouterLink to="/beranda" class="brand">
        <span class="brand-logo">B</span>
        <span>Teman Baik</span>
      </RouterLink>

      <nav class="nav-menu">
        <RouterLink to="/beranda">Beranda</RouterLink>
        <RouterLink to="/aduan" class="active">Buat Aduan</RouterLink>
        <RouterLink to="/cek-laporan">Cek Laporan</RouterLink>
      </nav>

      <button class="profile-button" @click="bukaProfil">
        <UserRound :size="17" />
        <span>{{ namaPengguna }}</span>
        <ChevronDown :size="15" />
      </button>
    </header>

    <main class="main-content">
      <div class="page-heading">
        <RouterLink to="/beranda" class="back-link">
          <ArrowLeft :size="16" />
          Kembali ke Beranda
        </RouterLink>

        <div class="heading-label">
          <span class="heading-dot"></span>
          LAYANAN PENGADUAN MASYARAKAT
        </div>

        <h1>Sampaikan Aduan Anda</h1>

        <p>
          Sampaikan pengaduan atau permohonan Anda kepada Pemerintah
          Kota Banjarmasin. Kami siap mendengarkan dan menindaklanjuti
          laporan Anda.
        </p>
      </div>

      <div class="content-grid">
        <section class="form-card">
          <div class="card-heading">
            <div class="heading-icon">
              <FileText :size="21" />
            </div>

            <div>
              <h2>Formulir Pengaduan</h2>
              <p>Lengkapi informasi laporan Anda dengan benar.</p>
            </div>
          </div>

          <div class="required-note">
            <span>*</span> Wajib diisi
          </div>

          <form class="aduan-form" @submit.prevent="kirimAduan">
            <!-- Jenis Laporan -->
            <div class="form-group">
              <label class="jenis-label">
                Jenis Laporan <span>*</span>
              </label>

              <div class="jenis-pilihan">
                <label
                  class="jenis-option"
                  :class="{
                    terpilih: form.jenis === 'Pengaduan'
                  }"
                >
                  <input
                    type="radio"
                    name="jenis"
                    value="Pengaduan"
                    v-model="form.jenis"
                    required
                  />

                  <span class="jenis-option-content">
                    <strong>Pengaduan</strong>
                    <small>
                      Sampaikan keluhan atau laporkan permasalahan.
                    </small>
                  </span>
                </label>

                <label
                  class="jenis-option"
                  :class="{
                    terpilih:
                      form.jenis === 'Permohonan Informasi'
                  }"
                >
                  <input
                    type="radio"
                    name="jenis"
                    value="Permohonan Informasi"
                    v-model="form.jenis"
                    required
                  />

                  <span class="jenis-option-content">
                    <strong>Permohonan Informasi</strong>
                    <small>
                      Ajukan permintaan informasi publik.
                    </small>
                  </span>
                </label>
              </div>
            </div>

            <!-- Judul Aduan -->
            <div class="form-group">
              <label for="judul">
                Judul Aduan <span>*</span>
              </label>

              <input
                id="judul"
                v-model.trim="form.judul"
                type="text"
                maxlength="150"
                placeholder="Contoh: Kerusakan jalan di lingkungan warga"
                required
              />
            </div>

            <!-- Instansi Tujuan -->
            <div class="form-group">
              <label for="instansi">
                Instansi Tujuan <span>*</span>
              </label>

              <select
                id="instansi"
                v-model="form.instansi_id"
                :disabled="memuatInstansi"
                required
              >
                <option value="" disabled>
                  {{
                    memuatInstansi
                      ? 'Memuat instansi...'
                      : 'Pilih instansi tujuan'
                  }}
                </option>

                <option
                  v-for="instansi in daftarInstansi"
                  :key="instansi.id"
                  :value="String(instansi.id)"
                >
                  {{ instansi.nama_instansi }}
                </option>
              </select>

              <p v-if="errorInstansi" class="field-error">
                {{ errorInstansi }}
              </p>
            </div>

            <!-- Lokasi Kejadian -->
            <div class="form-group">
              <label for="lokasi">
                Lokasi Kejadian <span>*</span>
              </label>

              <div class="input-with-icon">
                <MapPin :size="17" />

                <input
                  id="lokasi"
                  v-model.trim="form.lokasi"
                  type="text"
                  maxlength="255"
                  placeholder="Masukkan alamat atau lokasi kejadian"
                  required
                />
              </div>
            </div>

            <!-- Isi Aduan -->
            <div class="form-group">
              <label for="isi">
                Isi Aduan <span>*</span>
              </label>

              <textarea
                id="isi"
                v-model.trim="form.isi_aduan"
                rows="6"
                maxlength="5000"
                placeholder="Jelaskan permasalahan, lokasi, waktu kejadian, dan informasi pendukung lainnya..."
                required
              ></textarea>

              <div class="character-count">
                {{ form.isi_aduan.length }}/5000 karakter
              </div>
            </div>

            <!-- Lampiran Bukti -->
            <div class="form-group">
              <label for="bukti">Lampiran Bukti</label>

              <label
                class="upload-area"
                for="bukti"
                :class="{ 'has-file': form.bukti }"
              >
                <div class="upload-icon">
                  <Upload :size="22" />
                </div>

                <div class="upload-text">
                  <strong>
                    {{
                      form.bukti
                        ? form.bukti.name
                        : 'Pilih berkas untuk diunggah'
                    }}
                  </strong>

                  <span>Format gambar atau PDF, maksimal 5 MB</span>
                </div>

                <span class="choose-file">Pilih File</span>
              </label>

              <input
                id="bukti"
                class="file-input"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                @change="pilihBukti"
              />

              <p v-if="errorBukti" class="field-error">
                {{ errorBukti }}
              </p>
            </div>

            <!-- Privasi -->
            <div class="privacy-option">
              <input
                id="samarkan"
                v-model="form.samarkan"
                type="checkbox"
              />

              <label for="samarkan">
                <strong>Samarkan identitas saya</strong>
                <span>
                  Aktifkan jika Anda ingin identitas disamarkan
                  dalam penanganan laporan, sesuai kebijakan layanan.
                </span>
              </label>
            </div>

            <!-- Pesan Kesalahan -->
            <div v-if="pesan" class="form-alert" role="alert">
              <AlertCircle :size="18" />
              <span>{{ pesan }}</span>
            </div>

            <!-- Tombol Form -->
            <div class="form-actions">
              <RouterLink to="/beranda" class="cancel-button">
                Batal
              </RouterLink>

              <button
                type="submit"
                class="submit-button"
                :disabled="sedangKirim || memuatInstansi"
              >
                <span>
                  {{ sedangKirim ? 'Mengirim...' : 'Kirim Aduan' }}
                </span>

                <Send v-if="!sedangKirim" :size="17" />

                <span v-else class="spinner"></span>
              </button>
            </div>
          </form>
        </section>

        <!-- Informasi Samping -->
        <aside class="information-column">
          <section class="info-card">
            <div class="info-icon red">
              <ShieldCheck :size="22" />
            </div>

            <h3>Privasi Terjaga</h3>

            <p>
              Laporan Anda akan diproses sesuai ketentuan layanan.
              Gunakan fitur penyamaran identitas bila diperlukan.
            </p>
          </section>

          <section class="info-card">
            <div class="info-icon orange">
              <ClipboardCheck :size="22" />
            </div>

            <h3>Proses Transparan</h3>

            <p>
              Setelah laporan berhasil dikirim, simpan nomor tiket
              untuk memantau perkembangan penanganannya.
            </p>
          </section>

          <section class="info-card help-card">
            <div class="info-icon green">
              <MessageCircle :size="22" />
            </div>

            <h3>Tips Membuat Aduan</h3>

            <ul>
              <li>Gunakan judul yang jelas dan singkat.</li>
              <li>Jelaskan kejadian secara lengkap.</li>
              <li>Pilih instansi tujuan yang sesuai.</li>
              <li>Sertakan bukti jika tersedia.</li>
            </ul>
          </section>

          <div class="help-note">
            <Info :size="17" />

            <p>
              Pastikan informasi yang Anda sampaikan benar
              dan dapat dipertanggungjawabkan.
            </p>
          </div>
        </aside>
      </div>
    </main>

    <footer class="page-footer">
      <div>
        <strong>Teman Baik</strong>
        <span>
          © 2025 Pemerintah Kota Banjarmasin.
          Seluruh hak cipta dilindungi.
        </span>
      </div>

      <span>Layanan Pengaduan &amp; Aspirasi Publik</span>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import {
  UserRound,
  ChevronDown,
  ArrowLeft,
  FileText,
  MapPin,
  Upload,
  ShieldCheck,
  ClipboardCheck,
  MessageCircle,
  Info,
  AlertCircle,
  Send,
} from 'lucide-vue-next'

import api, {
  keluarDariSistem,
  pesanError,
} from '../services/api'

const router = useRouter()

const daftarInstansi = ref([])
const memuatInstansi = ref(false)
const sedangKirim = ref(false)
const errorInstansi = ref('')
const errorBukti = ref('')
const pesan = ref('')
const namaPengguna = ref('Pengguna')

const form = reactive({
  judul: '',
  jenis: 'Pengaduan',
  instansi_id: '',
  lokasi: '',
  isi_aduan: '',
  bukti: null,
  samarkan: false,
})

onMounted(() => {
  bacaPengguna()
  ambilInstansi()
})

// Membaca nama pengguna dari sesi login
function bacaPengguna() {
  try {
    const data = JSON.parse(
      sessionStorage.getItem('temanBaikPengguna') || 'null',
    )

    namaPengguna.value = data?.nama ?? 'Pengguna'
  } catch (error) {
    console.error('Gagal membaca sesi pengguna:', error)
  }
}

// Mengambil daftar instansi dari backend
async function ambilInstansi() {
  memuatInstansi.value = true
  errorInstansi.value = ''

  try {
    const { data } = await api.get('/instansi')

    daftarInstansi.value = Array.isArray(data) ? data : []

    if (daftarInstansi.value.length === 0) {
      errorInstansi.value =
        'Daftar instansi kosong. Periksa data instansi di backend.'
    }
  } catch (error) {
    console.error(
      'Gagal mengambil instansi:',
      error.response?.data || error,
    )

    errorInstansi.value = pesanError(
      error,
      'Daftar instansi gagal dimuat',
    )
  } finally {
    memuatInstansi.value = false
  }
}

// Validasi file bukti
function pilihBukti(event) {
  errorBukti.value = ''
  pesan.value = ''

  const file = event.target.files?.[0]

  if (!file) {
    form.bukti = null
    return
  }

  const formatDiizinkan = [
    'image/jpeg',
    'image/png',
    'application/pdf',
  ]

  if (!formatDiizinkan.includes(file.type)) {
    errorBukti.value = 'Gunakan berkas JPG, PNG, atau PDF.'
    form.bukti = null
    event.target.value = ''
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    errorBukti.value = 'Ukuran berkas maksimal 5 MB.'
    form.bukti = null
    event.target.value = ''
    return
  }

  form.bukti = file
}

// Mengirim laporan ke POST /api/pengaduan
async function kirimAduan() {
  pesan.value = ''
  errorBukti.value = ''

  if (!form.judul.trim()) {
    pesan.value = 'Judul aduan wajib diisi.'
    return
  }

  if (!form.jenis) {
    pesan.value = 'Silakan pilih jenis laporan.'
    return
  }

  if (!form.instansi_id) {
    pesan.value = 'Silakan pilih instansi tujuan.'
    return
  }

  if (!form.lokasi.trim()) {
    pesan.value = 'Lokasi kejadian wajib diisi.'
    return
  }

  if (!form.isi_aduan.trim()) {
    pesan.value = 'Isi aduan wajib diisi.'
    return
  }

  sedangKirim.value = true

  try {
    const dataForm = new FormData()

    dataForm.append('judul', form.judul.trim())

    // Sesuai pemetaan jenis yang digunakan backend:
    // Pengaduan = 1
    // Permohonan Informasi = 0
    dataForm.append(
      'jenisAduan',
      form.jenis === 'Pengaduan' ? '1' : '0',
    )

    dataForm.append('instansi', form.instansi_id)
    dataForm.append('alamat', form.lokasi.trim())
    dataForm.append('isi_aduan', form.isi_aduan.trim())
    dataForm.append('samarkan', form.samarkan ? '1' : '0')

    if (form.bukti) {
      dataForm.append('bukti', form.bukti)
    }

    // Axios mengatur Content-Type multipart beserta boundary.
    const { data } = await api.post('/pengaduan', dataForm)

    const nomorTiket = data?.no_tiket

    if (nomorTiket) {
      sessionStorage.setItem(
        'temanBaikTiketTerakhir',
        String(nomorTiket),
      )
    }

    window.alert(
      nomorTiket
        ? `Aduan berhasil dikirim. Nomor tiket: ${nomorTiket}`
        : 'Aduan berhasil dikirim.',
    )

    await router.push(
      nomorTiket
        ? {
            path: '/cek-laporan',
            query: { nomor: nomorTiket },
          }
        : '/cek-laporan',
    )
  } catch (error) {
    console.error(
      'Gagal mengirim aduan:',
      error.response?.data || error,
    )

    pesan.value = pesanError(error, 'Aduan gagal dikirim')
  } finally {
    sedangKirim.value = false
  }
}

// Menu profil dan keluar dari akun
async function bukaProfil() {
  const keluar = window.confirm(
    `Anda masuk sebagai ${namaPengguna.value}.\n\nKeluar dari akun?`,
  )

  if (keluar) {
    await keluarDariSistem()
    router.push('/')
  }
}
</script>

<style scoped>
/* Halaman utama */
.aduan-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fff8f7;
  color: #382d2e;
}

/* Navbar */
.navbar {
  min-height: 68px;
  padding: 0 6%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  background: #fff;
  border-bottom: 1px solid #f5e5e4;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: #b51220;
  font-size: 16px;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

.brand-logo {
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  border-radius: 7px;
  background: #b51220;
  color: #fff;
  font-size: 20px;
  font-weight: 800;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 30px;
}

.nav-menu a {
  padding: 25px 0;
  border-bottom: 2px solid transparent;
  color: #746466;
  font-size: 13px;
  text-decoration: none;
  transition: color 0.2s;
}

.nav-menu a:hover,
.nav-menu a.active {
  border-bottom-color: #b51220;
  color: #b51220;
}

.profile-button {
  max-width: 190px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1px solid #f1dddd;
  border-radius: 9px;
  background: #fff8f7;
  color: #574748;
  cursor: pointer;
}

.profile-button span {
  overflow: hidden;
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Konten halaman */
.main-content {
  width: 100%;
  max-width: 1180px;
  margin: 0 auto;
  padding: 36px 28px 60px;
  flex: 1;
}

.page-heading {
  max-width: 760px;
  margin-bottom: 30px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 24px;
  color: #79696a;
  font-size: 13px;
  text-decoration: none;
}

.back-link:hover {
  color: #b51220;
}

.heading-label {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  color: #b51220;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.7px;
}

.heading-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #b51220;
}

.page-heading h1 {
  margin: 0 0 12px;
  color: #352728;
  font-size: clamp(28px, 3vw, 38px);
  line-height: 1.2;
}

.page-heading > p {
  max-width: 680px;
  margin: 0;
  color: #857576;
  font-size: 14px;
  line-height: 1.8;
}

/* Layout formulir dan informasi */
.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 290px;
  align-items: start;
  gap: 25px;
}

.form-card {
  min-width: 0;
  padding: 30px;
  border: 1px solid #f4e7e6;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 8px 28px rgb(101 31 31 / 4%);
}

.card-heading {
  display: flex;
  align-items: center;
  gap: 13px;
}

.heading-icon {
  width: 45px;
  height: 45px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: #fff0ef;
  color: #b51220;
}

.card-heading h2 {
  margin: 0 0 5px;
  color: #3c3031;
  font-size: 19px;
}

.card-heading p {
  margin: 0;
  color: #8a7a7b;
  font-size: 12px;
  line-height: 1.6;
}

.required-note {
  margin: 24px 0;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1e8e7;
  color: #857576;
  font-size: 12px;
}

.required-note span,
.form-group > label > span {
  color: #b51220;
}

.aduan-form {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.form-group {
  min-width: 0;
}

.form-group > label {
  display: block;
  margin-bottom: 9px;
  color: #4a3d3e;
  font-size: 13px;
  font-weight: 600;
}

/* Input formulir */
.form-group input:not([type='file']):not([type='radio']),
.form-group select,
.form-group textarea {
  width: 100%;
  min-width: 0;
  padding: 13px 14px;
  border: 1px solid #f0e2e1;
  border-radius: 8px;
  outline: none;
  background: #fffafa;
  color: #493d3e;
  font-family: inherit;
  font-size: 13px;
  transition: border-color 0.2s, background 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #d88c92;
  background: #fff;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #a59596;
}

.form-group select:disabled {
  opacity: 0.7;
}

.form-group textarea {
  min-height: 145px;
  resize: vertical;
  line-height: 1.7;
}

/* Pilihan jenis laporan: dua kotak sejajar */
.jenis-label {
  display: block;
}

.jenis-pilihan {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.jenis-option {
  display: flex !important;
  align-items: flex-start;
  gap: 10px;
  min-width: 0;
  min-height: 100px;
  padding: 16px;
  margin: 0;
  border: 1px solid #f0e2e1;
  border-radius: 10px;
  background: #fffafa;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}

.jenis-option:hover {
  border-color: #d88c92;
  background: #fff5f4;
}

.jenis-option.terpilih {
  border-color: #b51220;
  background: #fff0ef;
  box-shadow: 0 0 0 1px #b51220;
}

.jenis-option input[type='radio'] {
  width: 17px;
  height: 17px;
  margin: 3px 0 0;
  padding: 0;
  flex-shrink: 0;
  accent-color: #b51220;
  cursor: pointer;
}

.jenis-option-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.jenis-option-content strong {
  color: #493536;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.5;
}

.jenis-option-content small {
  color: #857576;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.7;
}

.jenis-option.terpilih .jenis-option-content strong {
  color: #b51220;
}

/* Lokasi dengan ikon */
.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-icon > svg {
  position: absolute;
  left: 13px;
  color: #8d7d7e;
  pointer-events: none;
}

.input-with-icon input {
  padding-left: 40px !important;
}

.character-count {
  margin-top: 7px;
  color: #958586;
  font-size: 11px;
  text-align: right;
}

/* Upload bukti */
.upload-area {
  min-height: 86px;
  padding: 15px;
  display: flex !important;
  align-items: center;
  gap: 13px;
  border: 1px dashed #e4b7ba;
  border-radius: 9px;
  background: #fffafa;
  cursor: pointer;
}

.upload-area:hover,
.upload-area.has-file {
  border-color: #b51220;
  background: #fff5f4;
}

.upload-icon {
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #ffe8e7;
  color: #b51220;
}

.upload-text {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.upload-text strong {
  overflow: hidden;
  color: #4d3c3d;
  font-size: 12px;
  text-overflow: ellipsis;
}

.upload-text span {
  color: #968687;
  font-size: 11px;
  font-weight: 400;
}

.choose-file {
  flex-shrink: 0;
  padding: 8px 11px;
  border: 1px solid #e8c6c8;
  border-radius: 7px;
  color: #b51220 !important;
  font-size: 11px;
  font-weight: 600;
}

.file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  clip-path: inset(50%);
}

.file-input:focus-visible + .upload-area {
  outline: 2px solid #b51220;
  outline-offset: 3px;
}

/* Penyamaran identitas */
.privacy-option {
  padding: 16px;
  display: flex;
  align-items: flex-start;
  gap: 11px;
  border: 1px solid #f1e3e2;
  border-radius: 9px;
  background: #fffafa;
}

.privacy-option input {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  accent-color: #b51220;
  flex-shrink: 0;
}

.privacy-option label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  cursor: pointer;
}

.privacy-option strong {
  color: #4a3d3e;
  font-size: 12px;
}

.privacy-option span {
  color: #8b7b7c;
  font-size: 11px;
  font-weight: 400;
  line-height: 1.7;
}

/* Pesan kesalahan */
.field-error {
  margin: 7px 0 0;
  color: #b51220;
  font-size: 12px;
}

.form-alert {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 13px;
  border: 1px solid #f1c7ca;
  border-radius: 8px;
  background: #fff0ef;
  color: #a20d1a;
  font-size: 12px;
  line-height: 1.7;
  overflow-wrap: anywhere;
}

.form-alert svg {
  flex-shrink: 0;
  margin-top: 1px;
}

/* Tombol */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 4px;
}

.cancel-button,
.submit-button {
  min-height: 44px;
  padding: 0 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border-radius: 8px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.cancel-button {
  border: 1px solid #eadbda;
  background: #fff;
  color: #716263;
}

.cancel-button:hover {
  background: #fff8f7;
}

.submit-button {
  min-width: 150px;
  border: 1px solid #b51220;
  background: #b51220;
  color: #fff;
  transition: background 0.2s;
}

.submit-button:hover:not(:disabled) {
  background: #9f0d1a;
}

.submit-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgb(255 255 255 / 45%);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Kolom informasi */
.information-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-card {
  padding: 22px;
  border: 1px solid #f3e5e4;
  border-radius: 12px;
  background: #fff;
}

.info-icon {
  width: 43px;
  height: 43px;
  margin-bottom: 15px;
  display: grid;
  place-items: center;
  border-radius: 10px;
}

.info-icon.red {
  background: #fff0ef;
  color: #b51220;
}

.info-icon.orange {
  background: #fff4e7;
  color: #d17a1d;
}

.info-icon.green {
  background: #eaf8ef;
  color: #2d8a51;
}

.info-card h3 {
  margin: 0 0 9px;
  color: #453637;
  font-size: 15px;
}

.info-card p {
  margin: 0;
  color: #8a7a7b;
  font-size: 12px;
  line-height: 1.8;
}

.help-card ul {
  margin: 0;
  padding-left: 18px;
  color: #817172;
  font-size: 12px;
  line-height: 2;
}

.help-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 15px;
  border: 1px solid #f0dfc3;
  border-radius: 9px;
  background: #fff9ed;
  color: #98691d;
}

.help-note svg {
  flex-shrink: 0;
  margin-top: 2px;
}

.help-note p {
  margin: 0;
  font-size: 11px;
  line-height: 1.8;
}

/* Footer */
.page-footer {
  padding: 22px 6%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  background: #ffe5e3;
  color: #79696a;
  font-size: 11px;
}

.page-footer > div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.page-footer strong {
  color: #b51220;
  font-size: 13px;
}

/* Tablet */
@media (max-width: 900px) {
  .navbar {
    padding-inline: 24px;
  }

  .nav-menu {
    gap: 16px;
  }

  .content-grid {
    grid-template-columns: minmax(0, 1fr) 250px;
    gap: 16px;
  }

  .main-content {
    padding-inline: 20px;
  }

  .form-card {
    padding: 23px;
  }
}

/* Mobile */
@media (max-width: 700px) {
  .navbar {
    min-height: auto;
    padding: 15px 20px;
    flex-wrap: wrap;
  }

  .nav-menu {
    order: 3;
    width: 100%;
    justify-content: space-between;
    gap: 10px;
  }

  .nav-menu a {
    padding: 12px 0 8px;
    font-size: 11px;
  }

  .profile-button {
    max-width: 140px;
  }

  .main-content {
    padding: 25px 16px 35px;
  }

  .page-heading {
    margin-bottom: 23px;
  }

  .page-heading h1 {
    font-size: 28px;
  }

  .page-heading > p {
    font-size: 12px;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .form-card {
    padding: 20px 16px;
  }

  .card-heading h2 {
    font-size: 17px;
  }

  .information-column {
    display: grid;
    grid-template-columns: 1fr;
  }

  .page-footer {
    align-items: flex-start;
    flex-direction: column;
    padding-inline: 20px;
  }
}

/* Layar HP kecil */
@media (max-width: 480px) {
  .jenis-pilihan {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 400px) {
  .upload-area {
    flex-wrap: wrap;
  }

  .upload-text {
    flex-basis: calc(100% - 60px);
  }

  .choose-file {
    margin-left: 55px;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .cancel-button,
  .submit-button {
    width: 100%;
  }
}
</style>
