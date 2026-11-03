<template>
  <section class="bl" :class="`bl-${sumber}`">
    <header v-if="kepala" class="bl-head">
      <span class="bl-chip">{{ teksSumber }}</span>
      <h4>{{ judul }}</h4>
      <span class="bl-count">{{ items.length }} berkas</span>
    </header>

    <p v-if="!items.length" class="bl-kosong">{{ kosong }}</p>

    <ul v-else class="bl-grid">
      <li v-for="b in items" :key="b.kunci" class="bl-item">
        <button
          type="button"
          class="bl-thumb"
          :disabled="jenisItem(b) === 'lain'"
          :aria-label="`Lihat ${b.label || b.nama}`"
          @click="lihat(b)"
        >
          <img
            v-if="thumb[b.kunci]"
            :src="thumb[b.kunci]"
            :alt="b.label || b.nama"
            loading="lazy"
          />

          <span v-else-if="jenisItem(b) === 'gambar'" class="bl-ph">
            {{ statusThumb[b.kunci] === 'gagal' ? 'Gagal dimuat' : 'Memuat...' }}
          </span>

          <span v-else class="bl-doc">
            <FileText :size="30" />
            <b>{{ jenisItem(b) === 'pdf' ? 'PDF' : 'BERKAS' }}</b>
          </span>
        </button>

        <div class="bl-info">
          <strong :title="b.nama">{{ b.label || b.nama }}</strong>
          <small v-if="b.keterangan">{{ b.keterangan }}</small>

          <div class="bl-actions">
            <button
              v-if="jenisItem(b) !== 'lain'"
              type="button"
              class="bl-btn bl-btn-main"
              @click="lihat(b)"
            >
              <Eye :size="12" /> Lihat
            </button>

            <button type="button" class="bl-btn" :disabled="mengunduh === b.kunci" @click="unduh(b)">
              <Download :size="12" /> {{ mengunduh === b.kunci ? '...' : 'Unduh' }}
            </button>

            <button
              v-if="bisaHapus"
              type="button"
              class="bl-btn bl-btn-danger"
              :disabled="sibuk"
              @click="emit('hapus', b)"
            >
              <Trash2 :size="12" /> Hapus
            </button>
          </div>
        </div>
      </li>
    </ul>

    <p v-if="galat" class="bl-galat">{{ galat }}</p>

    <BerkasPreview
      v-if="terpilih"
      :path="terpilih.path"
      :nama="terpilih.nama"
      @tutup="terpilih = null"
    />
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'

import { Download, Eye, FileText, Trash2 } from 'lucide-vue-next'

import BerkasPreview from './BerkasPreview.vue'

import {
  ambilBlobBerkas,
  jenisBerkas,
  unduhBerkas,
} from '../services/berkas'

/*
  items: [{ kunci, nama, label?, keterangan?, path }]
  sumber: 'pelapor' | 'instansi'  -> menentukan warna & label asal berkas
*/
const props = defineProps({
  items: { type: Array, default: () => [] },
  sumber: { type: String, default: 'pelapor' },
  judul: { type: String, default: '' },
  kosong: { type: String, default: 'Belum ada berkas.' },
  kepala: { type: Boolean, default: true },
  bisaHapus: { type: Boolean, default: false },
  sibuk: { type: Boolean, default: false },
})

const emit = defineEmits(['hapus'])

const thumb = reactive({}) // kunci -> object URL
const statusThumb = reactive({}) // kunci -> 'gagal'
const terpilih = ref(null)
const mengunduh = ref('')
const galat = ref('')

const cacheUrl = new Map() // path -> object URL

const teksSumber = computed(() =>
  props.sumber === 'instansi' ? 'DARI INSTANSI' : 'DARI PELAPOR',
)

const jenisItem = (b) => jenisBerkas(b.nama)

// Muat thumbnail untuk setiap berkas gambar (sekali per path)
async function muatThumb(daftar) {
  for (const b of daftar) {
    if (jenisItem(b) !== 'gambar') continue

    if (cacheUrl.has(b.path)) {
      thumb[b.kunci] = cacheUrl.get(b.path)
      continue
    }

    try {
      const blob = await ambilBlobBerkas(b.path, b.nama)
      const url = URL.createObjectURL(blob)

      cacheUrl.set(b.path, url)
      thumb[b.kunci] = url
    } catch (e) {
      console.error('Gagal memuat thumbnail:', e)
      statusThumb[b.kunci] = 'gagal'
    }
  }
}

watch(() => props.items, (daftar) => muatThumb(daftar), { immediate: true })

function lihat(b) {
  galat.value = ''
  terpilih.value = b
}

async function unduh(b) {
  mengunduh.value = b.kunci
  galat.value = ''

  try {
    await unduhBerkas(b.path, b.nama)
  } catch (e) {
    galat.value = 'Berkas tidak dapat diunduh.'
    console.error(e)
  } finally {
    mengunduh.value = ''
  }
}

onBeforeUnmount(() => {
  cacheUrl.forEach((url) => URL.revokeObjectURL(url))
  cacheUrl.clear()
})
</script>

<style scoped>
.bl {
  --bl-warna: #25579b;
  --bl-latar: #e6f0fd;

  padding: 12px;
  border: 1px solid #f1e3e3;
  border-left: 4px solid var(--bl-warna);
  border-radius: 12px;
  background: #fff;
  font-family: inherit;
}

.bl-instansi {
  --bl-warna: #0f766e;
  --bl-latar: #dff4f0;
}

.bl-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.bl-head h4 {
  margin: 0;
  color: #2b1f20;
  font-size: 12px;
  font-weight: 800;
}

.bl-chip {
  padding: 3px 8px;
  border-radius: 6px;
  background: var(--bl-latar);
  color: var(--bl-warna);
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.bl-count {
  margin-left: auto;
  color: #8c7c7d;
  font-size: 10px;
}

.bl-kosong {
  margin: 0;
  padding: 14px 4px;
  color: #8c7c7d;
  font-size: 11.5px;
  line-height: 1.5;
}

.bl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.bl-item {
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #f1e3e3;
  border-radius: 10px;
  background: #fffafa;
}

.bl-thumb {
  display: grid;
  place-items: center;
  width: 100%;
  height: 140px;
  padding: 0;
  overflow: hidden;
  border: 0;
  background: #f4ecec;
  cursor: zoom-in;
}

.bl-thumb:disabled {
  cursor: default;
}

.bl-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s;
}

.bl-thumb:hover:not(:disabled) img {
  transform: scale(1.04);
}

.bl-ph {
  color: #8c7c7d;
  font-size: 11px;
}

.bl-doc {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  color: var(--bl-warna);
}

.bl-doc b {
  font-size: 11px;
  letter-spacing: 1px;
}

.bl-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 3px;
  padding: 10px 11px 11px;
}

.bl-info strong {
  overflow: hidden;
  color: #2b1f20;
  font-size: 11.5px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bl-info small {
  color: #8c7c7d;
  font-size: 9.5px;
  line-height: 1.45;
}

.bl-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 7px;
}

.bl-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  border: 1px solid #ecdcdc;
  border-radius: 99px;
  background: #fff;
  color: #2b1f20;
  font-family: inherit;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.bl-btn:hover:not(:disabled) {
  background: #fff6f6;
}

.bl-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.bl-btn-main {
  border-color: var(--bl-warna);
  background: var(--bl-warna);
  color: #fff;
}

.bl-btn-main:hover:not(:disabled) {
  background: var(--bl-warna);
  filter: brightness(0.92);
}

.bl-btn-danger {
  border-color: #efc4c4;
  color: #b51220;
}

.bl-galat {
  margin: 10px 0 0;
  color: #b51220;
  font-size: 11px;
}
</style>
