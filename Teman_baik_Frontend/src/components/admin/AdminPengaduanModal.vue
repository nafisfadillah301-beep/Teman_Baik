<template>
  <div class="adm-overlay" @click.self="emit('tutup')">
    <div class="adm-modal dm-modal" role="dialog" aria-modal="true">
      <div class="adm-modal-head">
        <div>
          <div class="dm-tags">
            <span class="adm-tag netral">{{ tiket }}</span>

            <span
              v-if="detail"
              class="adm-badge"
              :class="KELAS_STATUS[detail.status]"
            >
              {{ LABEL_STATUS[detail.status] || detail.status }}
            </span>

            <span
              v-if="detail"
              class="adm-tag"
              :class="detail.jenis"
            >
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
          <!-- ===== INFORMASI UTAMA ===== -->
          <dl class="dm-meta">
            <div>
              <dt>Tanggal Masuk</dt>
              <dd>{{ formatWaktu(detail.dibuat_pada) }}</dd>
            </div>

            <div>
              <dt>Pelapor</dt>
              <dd>
                <template v-if="detail.samarkan">
                  <i>Identitas Disamarkan</i>
                </template>
                <template v-else>{{ detail.nama_pelapor || '-' }}</template>
              </dd>
            </div>

            <div>
              <dt>Instansi Tujuan</dt>
              <dd>{{ detail.instansi || '-' }}</dd>
            </div>

            <div>
              <dt>Lokasi</dt>
              <dd>{{ detail.alamat || '-' }}</dd>
            </div>
          </dl>

          <h3 class="dm-h">Isi Laporan</h3>
          <p class="dm-isi">{{ detail.isi_aduan }}</p>

          <!-- ===== BERKAS: dipisah berdasarkan pengirimnya ===== -->
          <h3 class="dm-h">Berkas Laporan</h3>

          <div class="dm-berkas">
            <BerkasList
              sumber="pelapor"
              judul="Bukti dari Pelapor"
              kosong="Pelapor tidak melampirkan bukti."
              :items="buktiPelapor"
            />

            <BerkasList
              sumber="instansi"
              judul="Lampiran dari Instansi"
              kosong="Instansi belum mengunggah dokumentasi atau berita acara."
              :items="lampiranInstansi"
            />
          </div>

          <!-- ===== BALASAN ===== -->
          <template v-if="detail.balasan_instansi">
            <h3 class="dm-h">Balasan dari Instansi</h3>
            <p class="dm-isi">{{ detail.balasan_instansi.isi }}</p>
            <small class="dm-waktu">
              {{ formatWaktu(detail.balasan_instansi.waktu) }}
            </small>
          </template>

          <template v-if="detail.tanggapan">
            <h3 class="dm-h">Tanggapan Resmi kepada Pelapor</h3>
            <p class="dm-isi dm-isi-hijau">{{ detail.tanggapan.isi }}</p>
          </template>

          <!-- ===== RIWAYAT ===== -->
          <h3 class="dm-h">Riwayat Status</h3>

          <ol class="dm-timeline">
            <li v-for="(r, i) in detail.riwayat" :key="i">
              <span class="dm-dot"></span>

              <div>
                <strong>{{ LABEL_STATUS[r.status] || r.status }}</strong>
                <small>{{ formatWaktu(r.waktu) }}</small>
                <p v-if="r.catatan">{{ r.catatan }}</p>
              </div>
            </li>
          </ol>

          <!-- ===== EVALUASI ===== -->
          <h3 class="dm-h">Evaluasi Pelapor</h3>
          <p class="dm-isi">
            <template v-if="detail.evaluasi?.rating">
              {{ detail.evaluasi.rating }} / 4 &mdash;
              {{ LABEL_RATING[detail.evaluasi.rating] }}
            </template>
            <template v-else>Belum ada evaluasi.</template>
          </p>

          <!-- ===== TINDAK LANJUT ===== -->
          <div class="dm-action">
            <h3 class="dm-h dm-h-first">Tindak Lanjut</h3>

            <p v-if="pesan" class="adm-alert ok pad">{{ pesan }}</p>
            <p v-if="galat" class="adm-alert pad">{{ galat }}</p>

            <!-- diterima -> verifikasi / tolak -->
            <template v-if="detail.status === 'diterima'">
              <div class="adm-field">
                <label for="catatan">Catatan Verifikasi (opsional)</label>
                <textarea
                  id="catatan"
                  v-model="catatan"
                  class="adm-textarea"
                  maxlength="500"
                  placeholder="Catatan untuk riwayat laporan..."
                ></textarea>
              </div>

              <div class="dm-buttons">
                <button
                  type="button"
                  class="adm-btn adm-btn-primary"
                  :disabled="bekerja"
                  @click="verifikasi"
                >
                  <CircleCheck :size="13" /> Verifikasi Laporan
                </button>

                <button
                  type="button"
                  class="adm-btn adm-btn-danger"
                  :disabled="bekerja"
                  @click="modeTolak = !modeTolak"
                >
                  Tolak Laporan
                </button>
              </div>
            </template>

            <!-- diverifikasi -> teruskan / tolak -->
            <template v-else-if="detail.status === 'diverifikasi'">
              <div class="adm-field">
                <label for="tujuan">Instansi Tujuan Disposisi</label>
                <select
                  id="tujuan"
                  v-model="instansiTujuan"
                  class="adm-select"
                >
                  <option
                    v-for="i in daftarInstansi"
                    :key="i.id"
                    :value="String(i.id)"
                  >
                    {{ i.nama_instansi }}
                  </option>
                </select>
                <small class="dm-hint">
                  Laporan akan dikirim lewat email ke alamat resmi instansi.
                </small>
              </div>

              <div class="dm-buttons">
                <button
                  type="button"
                  class="adm-btn adm-btn-primary"
                  :disabled="bekerja || !instansiTujuan"
                  @click="teruskan"
                >
                  <Send :size="13" /> Teruskan ke Instansi
                </button>

                <button
                  type="button"
                  class="adm-btn adm-btn-danger"
                  :disabled="bekerja"
                  @click="modeTolak = !modeTolak"
                >
                  Tolak Laporan
                </button>
              </div>
            </template>

            <!-- dikirim -> balas ke pelapor / kirim ulang -->
            <template v-else-if="detail.status === 'dikirim'">
              <div class="adm-field">
                <label for="balasan">Tanggapan untuk Pelapor</label>
                <textarea
                  id="balasan"
                  v-model="isiBalasan"
                  class="adm-textarea"
                  maxlength="5000"
                  placeholder="Tulis tanggapan resmi yang akan dilihat pelapor..."
                ></textarea>
                <small v-if="detail.balasan_instansi" class="dm-hint">
                  Kolom ini sudah diisi dari balasan instansi. Sunting bila perlu.
                </small>
              </div>

              <div class="dm-buttons">
                <button
                  type="button"
                  class="adm-btn adm-btn-primary"
                  :disabled="bekerja || !isiBalasan.trim()"
                  @click="balas"
                >
                  <Send :size="13" /> Kirim Tanggapan
                </button>

                <button
                  type="button"
                  class="adm-btn"
                  :disabled="bekerja"
                  @click="kirimUlang"
                >
                  Kirim Ulang Email ke Instansi
                </button>
              </div>
            </template>

            <p v-else class="dm-hint">
              Laporan berstatus
              <b>{{ LABEL_STATUS[detail.status] }}</b> &mdash; tidak ada
              tindak lanjut yang tersedia.
            </p>

            <!-- form alasan penolakan -->
            <div v-if="modeTolak" class="dm-tolak">
              <div class="adm-field">
                <label for="alasan">Alasan Penolakan (wajib)</label>
                <textarea
                  id="alasan"
                  v-model="alasan"
                  class="adm-textarea"
                  maxlength="500"
                  placeholder="Jelaskan alasan laporan ditolak..."
                ></textarea>
              </div>

              <button
                type="button"
                class="adm-btn adm-btn-primary"
                :disabled="bekerja || !alasan.trim()"
                @click="tolak"
              >
                Konfirmasi Penolakan
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

import { CircleCheck, Send, X } from 'lucide-vue-next'

import BerkasList from '../BerkasList.vue'

import { pesanError } from '../../services/api'
import {
  KELAS_STATUS,
  LABEL_STATUS,
  ambilDetailPengaduan,
  ambilInstansi,
  balasPengaduan,
  formatTanggal,
  formatWaktu,
  teruskanPengaduan,
  tolakPengaduan,
  verifikasiPengaduan,
} from '../../services/admin'

const props = defineProps({
  tiket: { type: String, required: true },
})

const emit = defineEmits(['tutup', 'berubah'])

const LABEL_RATING = {
  1: 'Tidak Puas',
  2: 'Kurang Puas',
  3: 'Puas',
  4: 'Sangat Puas',
}

const detail = ref(null)
const memuat = ref(true)
const error = ref('')
const daftarInstansi = ref([])

const catatan = ref('')
const alasan = ref('')
const isiBalasan = ref('')
const instansiTujuan = ref('')
const modeTolak = ref(false)

const bekerja = ref(false)
const pesan = ref('')
const galat = ref('')

// Bukti yang dikirim PELAPOR saat membuat laporan
const buktiPelapor = computed(() => {
  const bukti = detail.value?.bukti_pengadu

  if (!bukti) return []

  return [
    {
      kunci: 'bukti',
      nama: bukti.nama_file,
      keterangan: 'Dikirim pelapor saat membuat laporan',
      path: `/admin/pengaduan/${encodeURIComponent(props.tiket)}/bukti`,
    },
  ]
})

// Lampiran yang diunggah ADMIN INSTANSI (dokumentasi & berita acara)
const lampiranInstansi = computed(() =>
  (detail.value?.lampiran || []).map((l) => ({
    kunci: `lampiran-${l.id}`,
    nama: l.nama_file,
    label: l.label,
    keterangan: `${
      l.jenis === 'berita_acara' ? 'Berita acara' : 'Dokumentasi'
    } \u2022 ${formatTanggal(l.tanggal)}`,
    path: `/admin/pengaduan/${encodeURIComponent(props.tiket)}/lampiran/${l.id}`,
  })),
)

// GET /api/admin/pengaduan/{no_tiket}
async function muat(tampilkanLoading = true) {
  if (tampilkanLoading) memuat.value = true
  error.value = ''

  try {
    const data = await ambilDetailPengaduan(props.tiket)

    detail.value = data

    if (data.status === 'diverifikasi' && !daftarInstansi.value.length) {
      daftarInstansi.value = await ambilInstansi()
    }

    instansiTujuan.value = String(data.id_instansi ?? '')

    if (data.status === 'dikirim' && !isiBalasan.value) {
      isiBalasan.value = data.balasan_instansi?.isi || ''
    }
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? 'Laporan tidak ditemukan.'
        : pesanError(e, 'Detail laporan gagal dimuat')
  } finally {
    memuat.value = false
  }
}

// Jalankan aksi, tampilkan hasilnya, lalu muat ulang detail
async function jalankan(aksi, pesanSukses) {
  bekerja.value = true
  pesan.value = ''
  galat.value = ''

  try {
    const { data } = await aksi()

    pesan.value =
      typeof pesanSukses === 'function' ? pesanSukses(data) : pesanSukses

    modeTolak.value = false
    emit('berubah')

    await muat(false)
  } catch (e) {
    galat.value = pesanError(e, 'Aksi gagal dijalankan')
  } finally {
    bekerja.value = false
  }
}

// POST .../verifikasi
function verifikasi() {
  return jalankan(
    () => verifikasiPengaduan(props.tiket, catatan.value.trim()),
    'Laporan berhasil diverifikasi.',
  )
}

// POST .../tolak
function tolak() {
  return jalankan(
    () => tolakPengaduan(props.tiket, alasan.value.trim()),
    'Laporan ditolak dan pelapor akan diberi tahu.',
  )
}

// POST .../teruskan
function teruskan() {
  const nama = daftarInstansi.value.find(
    (i) => String(i.id) === instansiTujuan.value,
  )?.nama_instansi

  if (!window.confirm(`Teruskan laporan ini ke ${nama || 'instansi'} lewat email?`)) {
    return
  }

  return jalankan(
    () => teruskanPengaduan(props.tiket, instansiTujuan.value),
    (d) => `Laporan diteruskan ke ${d?.instansi || 'instansi'}.`,
  )
}

// POST .../teruskan (kirim ulang, tanpa mengubah instansi)
function kirimUlang() {
  if (!window.confirm('Kirim ulang email laporan ini ke instansi?')) return

  return jalankan(
    () => teruskanPengaduan(props.tiket),
    'Email berhasil dikirim ulang ke instansi.',
  )
}

// POST .../balasan
function balas() {
  return jalankan(
    () => balasPengaduan(props.tiket, isiBalasan.value.trim()),
    'Tanggapan terkirim. Laporan kini berstatus Selesai.',
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
.dm-modal {
  max-width: 700px;
}

.dm-tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}

.dm-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 18px;
  margin: 0 0 18px;
  padding: 14px;
  border-radius: 12px;
  background: #fff6f6;
}

.dm-meta dt {
  color: #8c7c7d;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.4px;
  text-transform: uppercase;
}

.dm-meta dd {
  margin: 3px 0 0;
  font-size: 12px;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.dm-h {
  margin: 20px 0 8px !important;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.3px;
  text-transform: uppercase;
  color: #7b4d50;
}

.dm-h-first {
  margin-top: 0 !important;
}

.dm-isi {
  padding: 12px 14px;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  font-size: 12px;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.dm-isi-hijau {
  border-color: #cfe9da;
  background: #f3fbf6;
}

.dm-waktu {
  display: block;
  margin-top: 5px;
  color: #8c7c7d;
  font-size: 10px;
}

.dm-berkas {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dm-file {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
  padding: 10px 12px;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  color: #b51220;
}

.dm-file div {
  min-width: 0;
  flex: 1;
  color: #2b1f20;
}

.dm-file strong {
  display: block;
  overflow: hidden;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dm-file small {
  color: #8c7c7d;
  font-size: 9.5px;
}

.dm-timeline {
  margin: 0;
  padding: 0;
  list-style: none;
}

.dm-timeline li {
  position: relative;
  display: flex;
  gap: 12px;
  padding-bottom: 14px;
}

.dm-timeline li:not(:last-child)::before {
  position: absolute;
  top: 14px;
  bottom: 0;
  left: 4px;
  width: 2px;
  background: #f1d9d9;
  content: '';
}

.dm-dot {
  width: 10px;
  height: 10px;
  margin-top: 3px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #b51220;
}

.dm-timeline strong {
  display: block;
  font-size: 12px;
}

.dm-timeline small {
  color: #8c7c7d;
  font-size: 10px;
}

.dm-timeline p {
  margin-top: 3px;
  color: #5c4d4e;
  font-size: 11px;
}

.dm-action {
  margin-top: 22px;
  padding: 16px;
  border: 1px solid #f1d9d9;
  border-radius: 12px;
  background: #fffafa;
}

.dm-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.dm-hint {
  display: block;
  margin-top: 4px;
  color: #8c7c7d;
  font-size: 10px;
  line-height: 1.5;
}

.dm-tolak {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-start;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px dashed #efc4c4;
}

.dm-tolak .adm-field {
  width: 100%;
}

@media (max-width: 560px) {
  .dm-meta {
    grid-template-columns: 1fr;
  }
}
</style>
