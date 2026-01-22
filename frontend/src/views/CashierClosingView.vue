<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '../api/axios'
import { formatCurrency } from '../utils/formatting'
import { Banknote, Wallet, ReceiptText, CalendarCheck, Clock } from 'lucide-vue-next'

interface CashClosingData {
  userId: number;
  lastClosingAt: string | null;
  theoreticalTotal: number;
}

interface ClotureResult {
  id: number;
  user_id: number;
  date_ouverture: string;
  date_cloture: string;
  total_theorique: number;
  total_reel: number;
  ecart: number;
  commentaires: string | null;
}

const loading = ref(true)
const theoreticalData = ref<CashClosingData | null>(null)
const actualTotalEspeces = ref(0)
const commentaires = ref('')
const saving = ref(false)
const clotureResult = ref<ClotureResult | null>(null)

const fetchTheoreticalData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/cash-closing/current')
    theoreticalData.value = res.data
  } catch (err) {
    console.error('Erreur lors de la récupération des données théoriques:', err)
    alert('Erreur lors de la récupération des données théoriques de caisse.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchTheoreticalData)

const processCloture = async () => {
  saving.value = true
  try {
    if (!theoreticalData.value) {
      alert('Impossible de clôturer : données théoriques non chargées.')
      return
    }

    const payload = {
      theoretical_total: theoreticalData.value.theoreticalTotal,
      actual_total_especes: actualTotalEspeces.value,
      commentaires: commentaires.value,
    }

    const res = await axios.post('/cash-closing', payload)
    clotureResult.value = res.data
    alert('Clôture de caisse enregistrée avec succès!')
    // Reset form and data
    actualTotalEspeces.value = 0
    commentaires.value = ''
    theoreticalData.value = null // Force re-fetch next time
  } catch (err: any) {
    console.error('Erreur lors de la clôture de caisse:', err)
    let errorMessage = 'Erreur lors de la clôture de caisse.'
    if (err.response && err.response.data && err.response.data.message) {
        errorMessage += '\n' + err.response.data.message;
    }
    alert(errorMessage)
  } finally {
    saving.value = false
  }
}

const getEcartClass = (ecart: number) => {
  if (ecart === 0) return 'text-emerald-600'
  if (Math.abs(ecart) < 500) return 'text-orange-500' // Small discrepancy
  return 'text-rose-600' // Large discrepancy
}

const refreshPage = () => {
  clotureResult.value = null;
  fetchTheoreticalData();
}

</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <Banknote class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Clôture de Caisse</h1>
          <p class="text-xs text-slate-500">
            Rapprochement des ventes théoriques et de l'argent réel en caisse.
          </p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-10 text-slate-500 text-sm">
      Chargement des données...
    </div>

    <div v-else-if="clotureResult" class="max-w-xl mx-auto bg-white p-6 rounded-md shadow-lg border border-slate-200 space-y-4 text-xs">
        <div class="flex items-center gap-2 text-emerald-600">
            <CalendarCheck class="w-5 h-5"/>
            <h2 class="text-lg font-semibold">Clôture effectuée avec succès !</h2>
        </div>
        <div class="space-y-1">
            <p class="text-slate-600">Caissier: <strong>{{ $auth.user?.name }}</strong></p>
            <p class="text-slate-600">Date et heure: <strong>{{ new Date(clotureResult.date_cloture).toLocaleString() }}</strong></p>
            <p class="text-slate-600">Total théorique: <strong>{{ formatCurrency(clotureResult.total_theorique) }}</strong></p>
            <p class="text-slate-600">Total réel: <strong>{{ formatCurrency(clotureResult.total_reel) }}</strong></p>
            <p :class="['text-lg font-bold', getEcartClass(clotureResult.ecart)]">
                Écart: {{ formatCurrency(clotureResult.ecart) }}
            </p>
            <p v-if="clotureResult.commentaires" class="text-slate-500">Commentaires: {{ clotureResult.commentaires }}</p>
        </div>
        <div class="flex justify-end">
            <button @click="refreshPage" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-semibold">
                Nouvelle clôture
            </button>
        </div>
    </div>

    <div v-else class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden max-w-xl mx-auto">
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between text-xs">
        <span class="font-semibold text-slate-800">Détails de la clôture</span>
      </div>

      <div v-if="theoreticalData" class="flex-1 overflow-auto p-4 space-y-4 text-xs">
        <div class="border border-slate-200 rounded p-3 space-y-2">
          <div class="flex items-center gap-2 text-[11px] text-slate-700">
            <Wallet class="w-3.5 h-3.5 text-slate-500" />
            <span class="font-semibold">Ventes théoriques</span>
          </div>
          <div class="space-y-1 text-[11px] text-slate-600">
            <div class="flex items-center justify-between">
              <span>Dernière clôture:</span>
              <span class="font-semibold">
                {{ theoreticalData.lastClosingAt ? new Date(theoreticalData.lastClosingAt).toLocaleString() : 'Jamais' }}
              </span>
            </div>
            <div class="flex items-center justify-between text-base font-bold text-emerald-700">
              <span>Total ventes théoriques:</span>
              <span>{{ formatCurrency(theoreticalData.theoreticalTotal) }}</span>
            </div>
          </div>
        </div>

        <div class="border border-slate-200 rounded p-3 space-y-2">
          <div class="flex items-center gap-2 text-[11px] text-slate-700">
            <Banknote class="w-3.5 h-3.5 text-slate-500" />
            <span class="font-semibold">Montants réels en caisse</span>
          </div>
          <div class="space-y-2">
            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Total Espèces compté</label>
              <input
                v-model.number="actualTotalEspeces"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
              />
            </div>
            <!-- TODO: Add fields for other payment methods (Carte, Mobile Money) -->
            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Commentaires (facultatif)</label>
              <textarea
                v-model="commentaires"
                rows="2"
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
              ></textarea>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="processCloture"
            :disabled="saving"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <CalendarCheck class="w-4 h-4" />
            {{ saving ? 'Clôture en cours...' : 'Clôturer la caisse' }}
          </button>
        </div>
      </div>
      <div v-else class="flex-1 flex items-center justify-center text-[11px] text-slate-400 py-10">
        Aucune donnée de clôture disponible.
      </div>
    </div>
  </div>
</template>
