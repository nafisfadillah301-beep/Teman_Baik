import { createRouter, createWebHistory } from 'vue-router'

import LoginView from '../views/LoginView.vue'
import BerandaView from '../views/BerandaView.vue'
import PengaduanView from '../views/PengaduanView.vue'
import CekLaporanView from '../views/CekLaporanView.vue'
import DetailLaporanView from '../views/DetailLaporanView.vue'

import AdminLoginView from '../views/admin/AdminLoginView.vue'
import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminRekapView from '../views/admin/AdminRekapView.vue'
import AdminAkunView from '../views/admin/AdminAkunView.vue'
import AdminProfilView from '../views/admin/AdminProfilView.vue'
import InstansiDashboardView from '../views/instansi/InstansiDashboardView.vue'

import {
  berandaPeran,
  cekSesi,
  cekSesiAdmin,
  statusSesi,
  statusSesiAdmin,
} from '../services/api'

const routes = [
  {
    path: '/',
    name: 'login',
    component: LoginView,
  },
  {
    path: '/beranda',
    name: 'beranda',
    component: BerandaView,
    meta: { wajibLogin: true },
  },
  {
    path: '/aduan',
    name: 'aduan',
    component: PengaduanView,
    meta: { wajibLogin: true },
  },
  {
    path: '/cek-laporan',
    name: 'cek-laporan',
    component: CekLaporanView,
    meta: { wajibLogin: true },
  },
  {
    // Nomor tiket bisa lewat /detail-laporan/PGD-2026-0001A
    // atau lewat query ?nomor=PGD-2026-0001A
    path: '/detail-laporan/:id?',
    name: 'detail-laporan',
    component: DetailLaporanView,
    meta: { wajibLogin: true },
  },
  // ===== PORTAL SUPER ADMIN =====
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminLoginView,
    meta: { admin: true },
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: AdminDashboardView,
    meta: { admin: true, wajibAdmin: true, peran: 'super_admin' },
  },
  {
    path: '/admin/pengaduan',
    name: 'admin-rekap',
    component: AdminRekapView,
    meta: { admin: true, wajibAdmin: true, peran: 'super_admin' },
  },
  {
    path: '/admin/akun',
    name: 'admin-akun',
    component: AdminAkunView,
    meta: { admin: true, wajibAdmin: true, peran: 'super_admin' },
  },
  {
    path: '/admin/profil',
    name: 'admin-profil',
    component: AdminProfilView,
    meta: { admin: true, wajibAdmin: true, peran: 'super_admin' },
  },
  // ===== PORTAL ADMIN INSTANSI =====
  {
    path: '/instansi',
    name: 'instansi-dashboard',
    component: InstansiDashboardView,
    meta: { admin: true, wajibAdmin: true, peran: 'admin_instansi' },
  },
  {
    path: '/instansi/profil',
    name: 'instansi-profil',
    component: AdminProfilView,
    meta: { admin: true, wajibAdmin: true, peran: 'admin_instansi' },
  },
  {
    path: '/instansi/:pathMatch(.*)*',
    redirect: '/instansi',
  },
  {
    // Alamat /admin/... yang tidak dikenal -> dashboard admin
    path: '/admin/:pathMatch(.*)*',
    redirect: '/admin',
  },
  {
    // Alamat yang tidak dikenal -> kembali ke beranda
    path: '/:pathMatch(.*)*',
    redirect: '/beranda',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

// Status sesi diverifikasi ke server sekali per pemuatan halaman.
// Jika sesi habis di tengah jalan, interceptor di api.js yang menangani (401).
router.beforeEach(async (to) => {
  // ---- Portal admin: memakai sesi guard "web" (terpisah dari pengadu) ----
  if (to.meta.admin) {
    if (!statusSesiAdmin.sudahDicek) {
      await cekSesiAdmin()
    }

    if (to.meta.wajibAdmin && !statusSesiAdmin.aktif) {
      return { name: 'admin-login' }
    }

    const role = statusSesiAdmin.user?.role

    // Peran tidak sesuai halaman -> arahkan ke beranda perannya sendiri
    if (to.meta.peran && statusSesiAdmin.aktif && role !== to.meta.peran) {
      return berandaPeran(role)
    }

    if (to.name === 'admin-login' && statusSesiAdmin.aktif) {
      return berandaPeran(role)
    }

    return true
  }

  // ---- Portal masyarakat (pengadu) ----
  if (!statusSesi.sudahDicek) {
    await cekSesi()
  }

  // Halaman yang butuh login
  if (to.meta.wajibLogin && !statusSesi.aktif) {
    return { name: 'login' }
  }

  // Sudah login tetapi membuka halaman login
  if (to.name === 'login' && statusSesi.aktif) {
    return { name: 'beranda' }
  }

  return true
})

export default router
