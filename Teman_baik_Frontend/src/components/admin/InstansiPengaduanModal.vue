<template>
  <div class="adm-overlay" @click.self="emit('tutup')">
    <div class="adm-modal im-modal" role="dialog" aria-modal="true">
      <div class="adm-modal-head">
        <div>
          <div class="im-tags">
            <span class="adm-tag netral">{{ tiket }}</span>

            <span
              v-if="detail"
              class="adm-badge"
              :class="KELAS_STATUS_INSTANSI[detail.status]"
            >
              {{ LABEL_STATUS_INSTANSI[detail.status] || detail.status }}
            </span>

            <span v-if="detail" class="adm-tag" :class="detail.jenis">
              {{ detail.jenis === 'pengaduan' ? 'PENGADUAN' : 'INFORMASI' }}
            </span>
          </div>

          <h2>{{ detail?.judul || 'Memuat detail laporan...' }}</h2>
        </div>

        <button
          type="button"
          class="adm-icon-btn"
          aria-label="Tutup"
          @click="emit('tutup')"
        >
          <X :size="17" />
        </button>
      </div>

      <div class="adm-modal-body">
        <p v-if="memuat" class="adm-empty">Memuat...</p>

        <p v-else-if="error" class="adm-empty galat">{{ error }}</p>

        <template v-else-if="detail">
          <p v-if="pesan" class="adm-alert ok pad">{{ pesan }}</p>
          <p v-if="galat" class="adm-alert pad">{{ galat }}</p>

          <!-- ===== INFORMASI UTAMA ===== -->
          <dl class="im-meta">
            <div>
              <dt>Tanggal Masuk</dt>
              <dd>{{ formatWaktu(detail.dibuat_pada) }}</dd>
            </div>

            <div>
              <dt>Pelapor</dt>
              <dd>
                <i v-if="detail.samarkan">Identitas Disamarkan</i>
                <template v-else>{{ detail.nama_pelapor || '-' }}</template>
              </dd>
            </div>

            <div class="im-full">
              <dt>Lokasi</dt>
              <dd>{{ detail.alamat || '-' }}</dd>
            </div>
          </dl>

          <h3 class="im-h">Isi Laporan</h3>
          <p class="im-isi">{{ detail.isi_aduan }}</p>

          <!-- ===== BUKTI DARI PELAPOR ===== -->
          <BerkasList
            class="im-blok"
            sumber="pelapor"
            judul="Bukti dari Pelapor"
            kosong="Pelapor tidak melampirkan bukti."
            :items="buktiPelapor"
          />

          <!-- ===== BALASAN INSTANSI ===== -->
          <h3 class="im-h">Balasan Instansi</h3>

          <template v-if="detail.status === 'dikirim'">
            <div class="adm-field">
              <label for="im-balasan">Balasan untuk Admin Teman Baik</label>
              <textarea
                id="im-balasan"
                v-model="isiBalasan"
                class="adm-textarea"
                maxlength="5000"
                placeholder="Tulis tindak lanjut atau jawaban instansi atas laporan ini..."
              ></textarea>

              <small class="im-hint">
                Balasan dikirim ke Admin Teman Baik sebagai draf. Admin akan
                meninjau lalu memublikasikannya kepada pelapor.
                <template v-if="detail.balasan_instansi">
                  Terakhir dikirim: {{ formatWaktu(detail.balasan_instansi.waktu) }}.
                </template>
              </small>
            </div>

            <div class="im-buttons">
              <button
                type="button"
                class="adm-btn adm-btn-primary"
                :disabled="bekerja || !isiBalasan.trim()"
                @click="kirimBalasan"
              >
                <Send :size="13" />
                {{ detail.balasan_instansi ? 'Perbarui Balasan' : 'Kirim Balasan' }}
              </button>
            </div>
          </template>

          <template v-else>
            <p v-if="detail.balasan_instansi" class="im-isi">
              {{ detail.balasan_instansi.isi }}
            </p>
            <p v-else class="im-isi">Tidak ada balasan tercatat.</p>

            <p class="im-hint">
              Laporan sudah dijawab dan dipublikasikan oleh Admin Teman Baik,
              sehingga balasan tidak dapat diubah lagi.
            </p>

            <template v-if="detail.tanggapan">
              <h3 class="im-h">Tanggapan Resmi kepada Pelapor</h3>
              <p class="im-isi im-isi-hijau">{{ detail.tanggapan.isi }}</p>
            </template>
          </template>

          <!-- ===== LAMPIRAN DARI INSTANSI ===== -->
          <BerkasList
            class="im-blok"
            sumber="instansi"
            judul="Lampiran Tindak Lanjut dari Instansi Anda"
            kosong="Belum ada lampiran. Unggah foto dokumentasi atau berita acara sebagai bukti tindak lanjut."
            :items="lampiranInstansi"
            bisa-hapus
            :sibuk="bekerja"
            @hapus="hapusLampiran"
          />

          <!-- form unggah -->
          <div class="im-unggah">
            <h4>Unggah Lampiran Baru ({{ detail.lampiran.length }} / {{ MAKS_LAMPIRAN }})</h4>

            <div class="im-grid">
              <div class="adm-field">
                <label for="u-jenis">Jenis Lampiran</label>
                <select
                  id="u-jenis"
                  v-model="unggah.jenis"
                  class="adm-select"
                  @change="resetFile"
                >
                  <option value="dokumentasi">Foto Dokumentasi (JPG/PNG)</option>
                  <option value="berita_acara">Berita Acara (PDF)</option>
                </select>
              </div>

              <div class="adm-field">
                <label for="u-tanggal">Tanggal Kegiatan</label>
                <input
                  id="u-tanggal"
                  v-model="unggah.tanggal"
                  class="adm-input"
                  type="date"
                />
              </div>

              <div class="adm-field im-full">
                <label for="u-label">Keterangan (opsional)</label>
                <input
                  id="u-label"
                  v-model.trim="unggah.label"
                  class="adm-input"
                  type="text"
                  maxlength="100"
                  placeholder="Contoh: Perbaikan jalan tahap 1"
                />
              </div>

              <div class="adm-field im-full">
                <label for="u-file">Berkas (maks. 5 MB)</label>
                <input
                  id="u-file"
                  ref="inputFile"
                  class="adm-input"
                  type="file"
                  :accept="unggah.jenis === 'berita_acara' ? '.pdf,application/pdf' : '.jpg,.jpeg,.png,image/jpeg,image/png'"
                  @change="pilihFile"
                />
                <span v-if="galatFile" class="adm-field-error">{{ galatFile }}</span>
              </div>
            </div>

            <div class="im-buttons">
              <button
                type="button"
                class="adm-btn adm-btn-primary"
                :disabled="bekerja || !unggah.file || penuh"
                @click="unggahLampiran"
              >
                <Upload :size="13" /> Unggah Lampiran
              </button>

              <small v-if="penuh" class="im-hint">
                Jumlah lampiran sudah mencapai batas.
              </small>
            </div>
          </div>

          <!-- ===== RIWAYAT ===== -->
          <h3 class="im-h">Riwayat Status</h3>

          <ol class="im-timeline">
            <li v-for="(r, i) in detail.riwayat" :key="i">
              <span class="im-dot"></span>

              <div>
                <strong>{{ LABEL_STATUS[r.status] || r.status }}</strong>
                <small>{{ formatWaktu(r.waktu) }}</small>
                <p v-if="r.catatan">{{ r.catatan }}</p>
              </div>
            </li>
          </ol>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

import { Send, Upload, X } from 'lucide-vue-next'

import BerkasList from '../BerkasList.vue'

import { pesanError } from '../../services/api'
import {
  LABEL_STATUS,
  formatTanggal,
  formatWaktu,
  tglISO,
} from '../../services/admin'
import {
  KELAS_STATUS_INSTANSI,
  LABEL_STATUS_INSTANSI,
  ambilDetailInstansi,
  hapusLampiranInstansi,
  jalurBuktiInstansi,
  jalurLampiranInstansi,
  kirimBalasanInstansi,
  unggahLampiranInstansi,
} from '../../services/instansi'

const props = defineProps({
  tiket: { type: String, required: true },
})

const emit = defineEmits(['tutup', 'berubah'])

const MAKS_LAMPIRAN = 10 // sama dengan batas di backend

const detail = ref(null)
const memuat = ref(true)
const error = ref('')

const isiBalasan = ref('')
const bekerja = ref(false)
const pesan = ref('')
const galat = ref('')
const galatFile = ref('')
const inputFile = ref(null)

const unggah = reactive({
  jenis: 'dokumentasi',
  label: '',
  tanggal: tglISO(new Date()),
  file: null,
})

// Bukti yang dikirim PELAPOR
const buktiPelapor = computed(() => {
  const bukti = detail.value?.bukti_pengadu

  if (!bukti) return []

  return [
    {
      kunci: 'bukti',
      nama: bukti.nama_file,
      keterangan: 'Dikirim pelapor saat membuat laporan',
      path: jalurBuktiInstansi(props.tiket),
    },
  ]
})

// Lampiran yang diunggah INSTANSI ini
const lampiranInstansi = computed(() =>
  (detail.value?.lampiran || []).map((l) => ({
    kunci: `lampiran-${l.id}`,
    id: l.id,
    nama: l.nama_file,
    label: l.label,
    keterangan: `${
      l.jenis === 'berita_acara' ? 'Berita acara (PDF)' : 'Dokumentasi'
    } \u2022 ${formatTanggal(l.tanggal)} \u2022 ${formatUkuran(l.ukuran)}`,
    path: jalurLampiranInstansi(props.tiket, l.id),
  })),
)

const penuh = computed(
  () => (detail.value?.lampiran?.length || 0) >= MAKS_LAMPIRAN,
)

function formatUkuran(byte) {
  const n = Number(byte)

  if (!n) return '-'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${Math.round(n / 1024)} KB`

  return `${(n / 1024 / 1024).toFixed(1).replace('.', ',')} MB`
}

// GET /api/instansi/pengaduan/{no_tiket}
async function muat(tampilkanLoading = true) {
  if (tampilkanLoading) memuat.value = true
  error.value = ''

  try {
    const data = await ambilDetailInstansi(props.tiket)

    detail.value = data

    if (!isiBalasan.value && data.balasan_instansi) {
      isiBalasan.value = data.balasan_instansi.isi
    }
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? 'Laporan tidak ditemukan atau bukan milik instansi Anda.'
        : pesanError(e, 'Detail laporan gagal dimuat')
  } finally {
    memuat.value = false
  }
}

async function jalankan(aksi, pesanSukses) {
  bekerja.value = true
  pesan.value = ''
  galat.value = ''

  try {
    await aksi()

    pesan.value = pesanSukses
    emit('berubah')

    await muat(false)
  } catch (e) {
    galat.value = pesanError(e, 'Aksi gagal dijalankan')
  } finally {
    bekerja.value = false
  }
}

// POST /api/instansi/pengaduan/{no_tiket}/balasan
function kirimBalasan() {
  return jalankan(
    () => kirimBalasanInstansi(props.tiket, isiBalasan.value.trim()),
    'Balasan terkirim ke Admin Teman Baik untuk ditinjau.',
  )
}

function resetFile() {
  unggah.file = null
  galatFile.value = ''

  if (inputFile.value) inputFile.value.value = ''
}

// Aturan sama dengan backend: dokumentasi = jpg/png, berita acara = pdf, maks 5 MB
function pilihFile(event) {
  galatFile.value = ''

  const file = event.target.files?.[0]

  if (!file) {
    unggah.file = null
    return
  }

  const diizinkan =
    unggah.jenis === 'berita_acara'
      ? ['application/pdf']
      : ['image/jpeg', 'image/png']

  if (!diizinkan.includes(file.type)) {
    resetFile()
    galatFile.value =
      unggah.jenis === 'berita_acara'
        ? 'Berita acara harus berformat PDF.'
        : 'Foto dokumentasi harus berformat JPG atau PNG.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    resetFile()
    galatFile.value = 'Ukuran berkas maksimal 5 MB.'
    return
  }

  unggah.file = file
}

// POST /api/instansi/pengaduan/{no_tiket}/lampiran (multipart)
async function unggahLampiran() {
  if (!unggah.file) return

  const form = new FormData()

  form.append('jenis', unggah.jenis)
  form.append('file', unggah.file)
  if (unggah.label) form.append('label', unggah.label)
  if (unggah.tanggal) form.append('tanggal', unggah.tanggal)

  await jalankan(
    () => unggahLampiranInstansi(props.tiket, form),
    'Lampiran berhasil diunggah.',
  )

  if (!galat.value) {
    unggah.label = ''
    resetFile()
  }
}

// DELETE /api/instansi/pengaduan/{no_tiket}/lampiran/{id}
function hapusLampiran(item) {
  if (!window.confirm(`Hapus lampiran "${item.label || item.nama}"?`)) return

  return jalankan(
    () => hapusLampiranInstansi(props.tiket, item.id),
    'Lampiran dihapus.',
  )
}

function tekanTombol(event) {
  if (event.key === 'Escape') emit('tutup')
}

onMounted(() => {
  muat()
  document.addEventListener('keydown', tekanTombol)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', tekanTombol)
})
</script>

<style scoped>
.im-modal {
  max-width: 720px;
}

.im-tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}

.im-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 18px;
  margin: 0 0 18px;
  padding: 14px;
  border-radius: 12px;
  background: #fff6f6;
}

.im-full {
  grid-column: 1 / -1;
}

.im-meta dt {
  color: #8c7c7d;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.4px;
  text-transform: uppercase;
}

.im-meta dd {
  margin: 3px 0 0;
  font-size: 12px;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.im-h {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 22px 0 8px !important;
  color: #7b4d50;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

.im-count {
  color: #8c7c7d;
  font-size: 10px;
  letter-spacing: 0;
}

.im-isi {
  padding: 12px 14px;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.im-isi-hijau {
  border-color: #cfe9da;
  background: #f3fbf6;
}

.im-hint {
  display: block;
  margin-top: 6px;
  color: #8c7c7d;
  font-size: 10px;
  line-height: 1.55;
}

.im-buttons {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-top: 12px;
}

.im-blok {
  margin-top: 20px;
}

.im-file {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
  padding: 10px 12px;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  color: #b51220;
}

.im-file div {
  min-width: 0;
  flex: 1;
  color: #2b1f20;
}

.im-file strong {
  display: block;
  overflow: hidden;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.im-file small {
  color: #8c7c7d;
  font-size: 9.5px;
}

.im-hapus {
  color: #b51220;
}

.im-unggah {
  margin-top: 14px;
  padding: 16px;
  border: 1px dashed #efc4c4;
  border-radius: 12px;
  background: #fffafa;
}

.im-unggah h4 {
  margin: 0 0 12px;
  font-size: 11.5px;
  font-weight: 800;
}

.im-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.im-timeline {
  margin: 0;
  padding: 0;
  list-style: none;
}

.im-timeline li {
  position: relative;
  display: flex;
  gap: 12px;
  padding-bottom: 14px;
}

.im-timeline li:not(:last-child)::before {
  position: absolute;
  top: 14px;
  bottom: 0;
  left: 4px;
  width: 2px;
  background: #f1d9d9;
  content: '';
}

.im-dot {
  width: 10px;
  height: 10px;
  margin-top: 3px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #b51220;
}

.im-timeline strong {
  display: block;
  font-size: 12px;
}

.im-timeline small {
  color: #8c7c7d;
  font-size: 10px;
}

.im-timeline p {
  margin-top: 3px;
  color: #5c4d4e;
  font-size: 11px;
}

@media (max-width: 560px) {
  .im-meta,
  .im-grid {
    grid-template-columns: 1fr;
  }
}
</style>
