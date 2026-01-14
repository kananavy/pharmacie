<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '../api/axios'
import { formatCurrency } from '../utils/formatting'
import { useAuthStore } from '../stores/auth'
import { 
  TrendingUp, 
  AlertTriangle, 
  Calendar,
  ChevronRight,
  ArrowUpRight,
  Pill,
  Clock,
  DollarSign,
  ClipboardList,
  LayoutDashboard,
  PackageSearch,
  Timer
} from 'lucide-vue-next'

interface Medicament {
  id: number;
  nom: string;
  stock: number;
  prix: number;
  date_expiration: string;
}

interface TopMed {
  medicament_id: number;
  total_qty: number;
  medicament: Medicament;
}

const stats = ref({
  total_revenue: 0,
  sales_count: 0,
  stock_valuation: 0,
  top_medications: [] as TopMed[]
})

const alerts = ref({
  stock_alerts: [] as Medicament[],
  expiration_alerts: [] as Medicament[]
})

const loading = ref(true)
const auth = useAuthStore()

onMounted(async () => {
  try {
    const [statsRes, alertsRes] = await Promise.all([
      axios.get('/reports/daily'),
      axios.get('/medicaments/alerts/all')
    ])
    stats.value = statsRes.data
    alerts.value = alertsRes.data
  } catch (err) {
    console.error('Error fetching dashboard data', err)
  } finally {
    loading.value = false
  }
})

const formatPrice = (price: number) => formatCurrency(price)
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4 overflow-auto custom-scrollbar pr-1">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <LayoutDashboard class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900 italic">Espace Santé - {{ auth.user?.name }} 👋</h1>
          <p class="text-xs text-slate-500 font-medium">Revue globale de la performance et de l'inventaire en temps réel.</p>
        </div>
      </div>
      <div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded text-[11px] font-semibold text-slate-600 shadow-sm">
        <Calendar class="w-3.5 h-3.5 text-slate-400" />
        {{ new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
      </div>
    </div>

    <!-- KPIs Zone -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Revenu -->
      <div class="bg-white p-4 rounded-md border border-slate-200 shadow-sm hover:border-emerald-500/50 transition-colors">
        <div class="flex items-center justify-between mb-3">
          <div class="w-9 h-9 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <DollarSign class="w-5 h-5" />
          </div>
          <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded uppercase tracking-wider">Jour J</span>
        </div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Chiffre d'Affaires</div>
        <div class="text-xl font-bold text-slate-900 font-mono tracking-tight">{{ formatPrice(stats.total_revenue) }}</div>
      </div>

      <!-- Patrimoine -->
      <div class="bg-white p-4 rounded-md border border-slate-200 shadow-sm hover:border-emerald-500/50 transition-colors">
        <div class="flex items-center justify-between mb-3">
          <div class="w-9 h-9 rounded bg-slate-900 text-emerald-400 flex items-center justify-center">
            <ClipboardList class="w-5 h-5" />
          </div>
          <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded uppercase tracking-wider">Total</span>
        </div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Valeur Stock (Achat)</div>
        <div class="text-xl font-bold text-slate-900 font-mono tracking-tight">{{ formatPrice(stats.stock_valuation) }}</div>
      </div>

      <!-- Ruptures -->
      <div class="bg-white p-4 rounded-md border border-slate-200 shadow-sm hover:border-rose-500/50 transition-colors">
        <div class="flex items-center justify-between mb-3">
          <div class="w-9 h-9 rounded bg-rose-50 text-rose-600 flex items-center justify-center">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <span v-if="alerts.stock_alerts.length > 0" class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded uppercase tracking-wider">ALERTE</span>
        </div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ruptures Stock</div>
        <div class="text-xl font-bold text-slate-900 font-mono tracking-tight">{{ alerts.stock_alerts.length }} produits</div>
      </div>

      <!-- Expirations -->
      <div class="bg-white p-4 rounded-md border border-slate-200 shadow-sm hover:border-amber-500/50 transition-colors">
        <div class="flex items-center justify-between mb-3">
          <div class="w-9 h-9 rounded bg-amber-50 text-amber-600 flex items-center justify-center">
            <Clock class="w-5 h-5" />
          </div>
          <span v-if="alerts.expiration_alerts.length > 0" class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded uppercase tracking-wider">URGENT</span>
        </div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">Vigilance Péremption</div>
        <div class="text-xl font-bold text-slate-900 font-mono tracking-tight">{{ alerts.expiration_alerts.length }} alertes</div>
      </div>
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 flex-1 min-h-0">
      <!-- Section Alertes Sanitaires -->
      <div class="lg:col-span-7 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden min-h-[400px]">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <PackageSearch class="w-4 h-4 text-slate-400" />
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Contrôle Inventaire Critique</span>
          </div>
          <router-link to="/medications" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 uppercase tracking-widest">
            Gérer les stocks
            <ChevronRight class="w-3.5 h-3.5" />
          </router-link>
        </div>

        <div class="flex-1 overflow-auto p-2 custom-scrollbar">
          <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-3">
            <div class="w-8 h-8 border-3 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
            <p class="text-[11px] text-slate-400 font-semibold uppercase tracking-widest">Synchronisation...</p>
          </div>

          <template v-else-if="alerts.stock_alerts.length || alerts.expiration_alerts.length">
            <div class="space-y-1">
              <div v-for="item in alerts.stock_alerts" :key="'s'+item.id" class="flex items-center p-3 bg-white border border-slate-100 rounded hover:bg-slate-50 transition-colors group">
                <div class="w-8 h-8 rounded bg-rose-50 text-rose-600 flex items-center justify-center mr-3">
                  <AlertTriangle class="w-4 h-4" />
                </div>
                <div class="flex-1">
                  <h4 class="text-xs font-bold text-slate-800 group-hover:text-rose-600 transition-colors">{{ item.nom }}</h4>
                  <p class="text-[10px] text-slate-400 font-medium italic">Seuil atteint : {{ item.stock }} unités en réserve</p>
                </div>
                <div class="text-[9px] font-bold uppercase tracking-widest text-rose-500 bg-rose-50 px-2 py-0.5 rounded border border-rose-100">Rupture</div>
              </div>

              <div v-for="item in alerts.expiration_alerts" :key="'e'+item.id" class="flex items-center p-3 bg-white border border-slate-100 rounded hover:bg-slate-50 transition-colors group">
                <div class="w-8 h-8 rounded bg-amber-50 text-amber-600 flex items-center justify-center mr-3">
                  <Timer class="w-4 h-4" />
                </div>
                <div class="flex-1">
                  <h4 class="text-xs font-bold text-slate-800 group-hover:text-amber-600 transition-colors">{{ item.nom }}</h4>
                  <p class="text-[10px] text-slate-400 font-medium font-mono">Date critique : {{ new Date(item.date_expiration).toLocaleDateString() }}</p>
                </div>
                <div class="text-[9px] font-bold uppercase tracking-widest text-amber-500 bg-amber-50 px-2 py-0.5 rounded border border-amber-100">Péremption</div>
              </div>
            </div>
          </template>

          <div v-else class="flex flex-col items-center justify-center py-24 px-10 text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
              <Pill class="w-6 h-6 text-emerald-300" />
            </div>
            <p class="text-xs font-bold text-slate-800 uppercase tracking-widest">État de Stock Optimal</p>
            <p class="text-[11px] text-slate-400 mt-1 max-w-[200px]">Aucune anomalie détectée sur l'inventaire actuel.</p>
          </div>
        </div>
      </div>

      <!-- Section Top Ventes -->
      <div class="lg:col-span-5 flex flex-col bg-emerald-950 rounded-md overflow-hidden shadow-lg shadow-emerald-950/20">
        <div class="px-4 py-3 border-b border-emerald-900 bg-emerald-900/50 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <TrendingUp class="w-4 h-4 text-emerald-400" />
            <span class="text-xs font-bold text-white uppercase tracking-wider">Top Rotation</span>
          </div>
        </div>

        <div class="flex-1 overflow-auto p-4 custom-scrollbar">
          <div v-if="loading" class="animate-pulse space-y-3">
            <div v-for="i in 4" :key="i" class="h-16 bg-emerald-900/50 rounded border border-emerald-800/30"></div>
          </div>

          <template v-else-if="stats.top_medications.length">
            <div class="space-y-3">
              <div v-for="(item, idx) in stats.top_medications" :key="item.medicament_id" class="flex items-center gap-4 group/item p-3 rounded-lg border border-emerald-900/40 bg-emerald-900/20 hover:bg-emerald-900/40 transition-all">
                <div class="w-8 h-8 rounded bg-emerald-800/50 flex items-center justify-center font-bold text-emerald-400 text-xs border border-emerald-700 shadow-inner">
                  {{ idx + 1 }}
                </div>
                <div class="flex-1">
                  <h4 class="text-xs font-bold text-white leading-tight group-hover/item:text-emerald-300 transition-colors uppercase tracking-tight">{{ item.medicament.nom }}</h4>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-[9px] font-bold text-emerald-500/80 uppercase tracking-widest italic">{{ item.total_qty }} unités vendues</span>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-emerald-400 font-mono tracking-tighter">{{ formatPrice(item.total_qty * item.medicament.prix) }}</p>
                </div>
              </div>

              <button class="w-full mt-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white rounded font-bold text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 transition-all shadow-lg shadow-emerald-500/10 active:scale-95 group">
                Détails du rapport
                <ArrowUpRight class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
              </button>
            </div>
          </template>

          <div v-else class="flex flex-col items-center justify-center py-20 px-8 text-center sm:h-full">
            <p class="text-emerald-500/50 font-bold uppercase tracking-widest text-[11px] italic">Données de ventes indisponibles</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f01a;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #e2e8f033;
}
</style>
