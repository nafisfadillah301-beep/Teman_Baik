<template>
  <div ref="akar" class="notif-wrapper">
    <button
      type="button"
      class="notif-button"
      aria-label="Notifikasi"
      :aria-expanded="terbuka"
      @click="toggle"
    >
      <Bell :size="17" />
      <span v-if="belumDibaca > 0" class="notif-dot"></span>
    </button>

    <div v-if="terbuka" class="notif-panel" role="dialog">
      <div class="notif-head">
        <strong>Notifikasi</strong>

        <button
          v-if="belumDibaca > 0"
          type="button"
          class="notif-readall"
          @click="bacaSemua"
        >
          Tandai semua dibaca
        </button>
      </div>

      <p v-if="memuat" class="notif-empty">Memuat notifikasi...</p>

      <p v-else-if="error" class="notif-empty notif-error">{{ error }}</p>

      <p v-else-if="items.length === 0" class="notif-empty">
        Belum ada notifikasi.
      </p>

      <ul v-else class="notif-list">
        <li v-for="n in items" :key="n.id">
          <button
            type="button"
            class="notif-item"
            :class="{ unread: !n.dibaca }"
            @click="bukaNotifikasi(n)"
          >
            <strong>{{ n.judul || n.no_tiket }}</strong>
            <span>{{ n.pesan }}</span>
            <small>{{ formatWaktu(n.waktu) }}</small>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bell } from 'lucide-vue-next'

import api, { pesanError } from '../services/api'

const router = useRouter()

const akar = ref(null)
const terbuka = ref(false)
const memuat = ref(false)
const error = ref('')
const items = ref([])
const belumDibaca = ref(0)

let timer = null

// GET /api/pengadu/notifikasi -> { belum_dibaca, items: [...] }
async function muat(tampilkanLoading = false) {
  if (tampilkanLoading) memuat.value = true
  error.value = ''

  try {
    const { data } = await api.get('/pengadu/notifikasi')

    items.value = data.items || []
    belumDibaca.value = data.belum_dibaca || 0
  } catch (e) {
    error.value = pesanError(e, 'Notifikasi gagal dimuat')
  } finally {
    memuat.value = false
  }
}

function toggle() {
  terbuka.value = !terbuka.value

  if (terbuka.value) muat(true)
}

// POST /api/pengadu/notifikasi/{id}/baca lalu buka detail laporan
async function bukaNotifikasi(n) {
  try {
    if (!n.dibaca) {
      await api.post(`/pengadu/notifikasi/${n.id}/baca`)
    }
  } catch (e) {
    console.error('Gagal menandai notifikasi:', e)
  }

  terbuka.value = false

  if (n.no_tiket) {
    router.push({ path: '/detail-laporan', query: { nomor: n.no_tiket } })
  }

  muat()
}

// POST /api/pengadu/notifikasi/baca-semua
async function bacaSemua() {
  try {
    await api.post('/pengadu/notifikasi/baca-semua')
    await muat()
  } catch (e) {
    error.value = pesanError(e, 'Gagal menandai notifikasi')
  }
}

function formatWaktu(waktu) {
  if (!waktu) return ''

  const d = new Date(waktu)

  if (Number.isNaN(d.getTime())) return ''

  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function klikDiLuar(event) {
  if (terbuka.value && akar.value && !akar.value.contains(event.target)) {
    terbuka.value = false
  }
}

onMounted(() => {
  muat()
  timer = setInterval(() => muat(), 60000) // segarkan tiap 1 menit
  document.addEventListener('click', klikDiLuar)
})

onBeforeUnmount(() => {
  clearInterval(timer)
  document.removeEventListener('click', klikDiLuar)
})
</script>

<style scoped>
.notif-wrapper {
  position: relative;
}

.notif-button {
  position: relative;
  display: grid;
  place-items: center;
  padding: 5px;
  border: 0;
  background: transparent;
  color: #5c5051;
  cursor: pointer;
}

.notif-dot {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #b51220;
}

.notif-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  z-index: 50;
  width: 320px;
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #f2eeee;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(48, 37, 38, 0.15);
  text-align: left;
}

.notif-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid #f2eeee;
  color: #302526;
  font-size: 13px;
}

.notif-readall {
  border: 0;
  background: transparent;
  color: #b51220;
  font-size: 11px;
  cursor: pointer;
}

.notif-empty {
  margin: 0;
  padding: 22px 14px;
  color: #7a6d6e;
  font-size: 12px;
  text-align: center;
}

.notif-error {
  color: #b51220;
}

.notif-list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.notif-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  width: 100%;
  padding: 11px 14px;
  border: 0;
  border-bottom: 1px solid #f7f3f3;
  background: #fff;
  color: #302526;
  font-size: 12px;
  text-align: left;
  cursor: pointer;
}

.notif-item.unread {
  background: #fff6f6;
}

.notif-item:hover {
  background: #faf2f2;
}

.notif-item span {
  color: #5c5051;
}

.notif-item small {
  color: #9a8c8d;
  font-size: 10px;
}
</style>
