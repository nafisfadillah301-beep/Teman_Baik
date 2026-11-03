<template>
  <Teleport to="body">
    <div class="bp-overlay" @click.self="emit('tutup')">
      <div class="bp-box" role="dialog" aria-modal="true" :aria-label="`Pratinjau ${nama}`">
        <header class="bp-head">
          <strong :title="nama">{{ nama }}</strong>

          <div class="bp-tools">
            <button
              v-if="url"
              type="button"
              class="bp-btn"
              @click="bukaTab"
            >
              <ExternalLink :size="13" /> Buka di tab baru
            </button>

            <button type="button" class="bp-btn" :disabled="mengunduh" @click="unduh">
              <Download :size="13" /> {{ mengunduh ? 'Mengunduh...' : 'Unduh' }}
            </button>

            <button type="button" class="bp-close" aria-label="Tutup" @click="emit('tutup')">
              <X :size="18" />
            </button>
          </div>
        </header>

        <div class="bp-body">
          <p v-if="memuat" class="bp-info">Memuat berkas...</p>

          <p v-else-if="error" class="bp-info bp-error">{{ error }}</p>

          <div v-else-if="jenis === 'gambar'" class="bp-gambar" :class="{ asli: ukuranAsli }">
            <img
              :src="url"
              :alt="nama"
              :title="ukuranAsli ? 'Klik untuk memperkecil' : 'Klik untuk ukuran asli'"
              @click="ukuranAsli = !ukuranAsli"
            />
          </div>

          <iframe
            v-else-if="jenis === 'pdf'"
            class="bp-pdf"
            :src="url"
            :title="`Pratinjau ${nama}`"
          ></iframe>

          <p v-else class="bp-info">
            Pratinjau tidak tersedia untuk jenis berkas ini. Silakan unduh.
          </p>
        </div>

        <footer v-if="jenis === 'pdf' && url" class="bp-foot">
          Jika PDF tidak tampil di perangkat Anda, gunakan
          <b>Buka di tab baru</b> atau <b>Unduh</b>.
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

import { Download, ExternalLink, X } from 'lucide-vue-next'

import {
  ambilBlobBerkas,
  jenisBerkas,
  pesanGalatBerkas,
  unduhBerkas,
} from '../services/berkas'

const props = defineProps({
  path: { type: String, required: true }, // path API (tanpa baseURL)
  nama: { type: String, default: 'Berkas' },
})

const emit = defineEmits(['tutup'])

const memuat = ref(true)
const error = ref('')
const url = ref('')
const jenis = ref(jenisBerkas(props.nama))
const ukuranAsli = ref(false)
const mengunduh = ref(false)

async function muat() {
  try {
    const blob = await ambilBlobBerkas(props.path, props.nama)

    url.value = URL.createObjectURL(blob)
  } catch (e) {
    console.error('Gagal memuat berkas:', e)

    error.value = pesanGalatBerkas(e)
  } finally {
    memuat.value = false
  }
}

function bukaTab() {
  window.open(url.value, '_blank', 'noopener')
}

async function unduh() {
  mengunduh.value = true

  try {
    await unduhBerkas(props.path, props.nama)
  } catch (e) {
    error.value = 'Berkas tidak dapat diunduh.'
    console.error(e)
  } finally {
    mengunduh.value = false
  }
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

  if (url.value) URL.revokeObjectURL(url.value)
})
</script>

<style scoped>
.bp-overlay {
  position: fixed;
  inset: 0;
  z-index: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(24, 12, 14, 0.78);
  font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system,
    'Segoe UI', Roboto, Arial, sans-serif;
}

.bp-box {
  display: flex;
  width: 100%;
  max-width: 960px;
  max-height: 100%;
  flex-direction: column;
  overflow: hidden;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 24px 70px rgba(0, 0, 0, 0.45);
}

.bp-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #f1e3e3;
}

.bp-head strong {
  overflow: hidden;
  color: #2b1f20;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bp-tools {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: 8px;
}

.bp-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 12px;
  border: 1px solid #ecdcdc;
  border-radius: 99px;
  background: #fff;
  color: #2b1f20;
  font-family: inherit;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.bp-btn:hover:not(:disabled) {
  background: #fff6f6;
}

.bp-btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

.bp-close {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #5c4d4e;
  cursor: pointer;
}

.bp-close:hover {
  background: #fff6f6;
}

.bp-body {
  display: flex;
  min-height: 220px;
  flex: 1;
  overflow: auto;
  background: #f6f1f1;
}

.bp-info {
  margin: auto;
  padding: 40px 20px;
  color: #7d6e6f;
  font-size: 13px;
  text-align: center;
}

.bp-error {
  color: #b51220;
}

.bp-gambar {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  padding: 14px;
}

.bp-gambar img {
  max-width: 100%;
  max-height: calc(100vh - 170px);
  border-radius: 6px;
  background: #fff;
  object-fit: contain;
  cursor: zoom-in;
}

.bp-gambar.asli {
  display: block;
}

.bp-gambar.asli img {
  max-width: none;
  max-height: none;
  cursor: zoom-out;
}

.bp-pdf {
  width: 100%;
  height: calc(100vh - 170px);
  min-height: 420px;
  border: 0;
  background: #fff;
}

.bp-foot {
  padding: 9px 16px;
  border-top: 1px solid #f1e3e3;
  color: #7d6e6f;
  font-size: 10.5px;
  text-align: center;
}

@media (max-width: 560px) {
  .bp-overlay {
    padding: 0;
  }

  .bp-box {
    max-width: none;
    height: 100%;
    border-radius: 0;
  }

  .bp-head {
    flex-direction: column;
    align-items: stretch;
  }

  .bp-head strong {
    white-space: normal;
  }
}
</style>
