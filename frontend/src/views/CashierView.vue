<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from '../api/axios'
import { 
  CreditCard, 
  Search, 
  Printer,
  Check,
  X,
  Banknote,
  Smartphone
} from 'lucide-vue-next'

interface Commande {
  id: number
  numero_ticket: string
  total: number
  statut: string
  vendeur: { name: string }
  details: Array<{
    id: number
    medicament: { nom: string }
    quantite: number
    prix_unitaire: number
  }>
  created_at: string
}

const ticketNumber = ref('')
const commande = ref<Commande | null>(null)
const loading = ref(false)
const modePaiement = ref<'especes' | 'carte' | 'mobile_money'>('especes')
const montantRecu = ref<number>(0)
const showSuccess = ref(false)
const lastVente = ref<any>(null)

const montantRendu = computed(() => {
  if (!commande.value) return 0
  return Math.max(0, montantRecu.value - commande.value.total)
})

const canPay = computed(() => {
  return commande.value && montantRecu.value >= commande.value.total
})

const searchCommande = async () => {
  if (!ticketNumber.value.trim()) {
    alert('Veuillez saisir un numéro de ticket')
    return
  }

  loading.value = true
  try {
    // Search by ticket number in pending orders
    const res = await axios.get('/commandes/pending/all')
    const found = res.data.find((c: Commande) => c.numero_ticket === ticketNumber.value.trim())
    
    if (found) {
      commande.value = found
      montantRecu.value = found.total
    } else {
      alert('Commande non trouvée ou déjà payée')
      commande.value = null
    }
  } catch (err) {
    alert('Erreur lors de la recherche')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const processPayment = async () => {
  if (!commande.value || !canPay.value) return

  loading.value = true
  try {
    const res = await axios.post(`/commandes/${commande.value.id}/pay`, {
      mode_paiement: modePaiement.value,
      montant_recu: montantRecu.value
    })

    lastVente.value = res.data
    showSuccess.value = true
    
    // Reset form
    commande.value = null
    ticketNumber.value = ''
    montantRecu.value = 0
    
    // Auto-hide after 5s
    setTimeout(() => {
      showSuccess.value = false
    }, 5000)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors du paiement')
  } finally {
    loading.value = false
  }
}

const printReceipt = () => {
  window.print()
}

const cancelSearch = () => {
  commande.value = null
  ticketNumber.value = ''
  montantRecu.value = 0
}
</script>

<template>
  <div class="space-y-6 h-[calc(100vh-140px)] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <CreditCard class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Caisse</h1>
          <p class="text-xs text-slate-500">Encaissement et validation des commandes</p>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccess" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-start gap-3">
      <Check class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
      <div class="flex-1">
        <p class="text-sm font-semibold text-emerald-900">Paiement validé avec succès!</p>
        <p class="text-xs text-emerald-700 mt-1">
          Vente N° <span class="font-mono font-bold">{{ lastVente?.id }}</span> - 
          Rendu: <span class="font-bold">{{ lastVente?.montant_rendu }} Ar</span>
        </p>
      </div>
      <button @click="printReceipt" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700 flex items-center gap-1.5">
        <Printer class="w-3.5 h-3.5" />
        Imprimer
      </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 grid grid-cols-2 gap-6 min-h-0">
      <!-- Search & Order Details -->
      <div class="flex flex-col gap-6">
        <!-- Search -->
        <div class="bg-white border border-slate-200 rounded-lg p-6">
          <h2 class="text-sm font-semibold text-slate-900 mb-4">Rechercher une commande</h2>
          
          <div class="flex gap-3">
            <div class="flex-1 flex items-center gap-3 bg-slate-50 px-4 py-3 rounded-lg border border-slate-200">
              <Search class="w-5 h-5 text-slate-400" />
              <input
                v-model="ticketNumber"
                @keyup.enter="searchCommande"
                type="text"
                placeholder="Ex: CMD-20260114-001"
                class="bg-transparent border-none outline-none w-full text-sm text-slate-700 font-mono"
              />
            </div>
            <button
              @click="searchCommande"
              :disabled="loading"
              class="px-6 py-3 bg-emerald-600 text-white rounded-lg font-medium text-sm hover:bg-emerald-700 disabled:opacity-50"
            >
              {{ loading ? 'Recherche...' : 'Rechercher' }}
            </button>
          </div>
        </div>

        <!-- Order Details -->
        <div v-if="commande" class="flex-1 bg-white border border-slate-200 rounded-lg overflow-hidden flex flex-col">
          <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Détails de la commande</h2>
            <button @click="cancelSearch" class="text-slate-400 hover:text-slate-600">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="p-4 border-b border-slate-200 space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-600">N° Ticket:</span>
              <span class="font-mono font-semibold text-slate-900">{{ commande.numero_ticket }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-600">Vendeur:</span>
              <span class="font-semibold text-slate-900">{{ commande.vendeur.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-600">Date:</span>
              <span class="text-slate-900">{{ new Date(commande.created_at).toLocaleString('fr-FR') }}</span>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div v-for="detail in commande.details" :key="detail.id" class="flex justify-between text-sm p-2 border border-slate-200 rounded">
              <span class="text-slate-700">{{ detail.medicament.nom }} <span class="text-slate-400">×{{ detail.quantite }}</span></span>
              <span class="font-semibold text-slate-900">{{ detail.prix_unitaire * detail.quantite }} Ar</span>
            </div>
          </div>

          <div class="p-4 border-t border-slate-200 bg-slate-50">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-slate-600">Total</span>
              <span class="text-2xl font-bold text-slate-900">{{ commande.total }} Ar</span>
            </div>
          </div>
        </div>

        <div v-else class="flex-1 bg-white border border-slate-200 rounded-lg flex items-center justify-center">
          <div class="text-center text-slate-400">
            <Search class="w-16 h-16 mx-auto mb-3 opacity-30" />
            <p class="text-sm">Recherchez une commande pour commencer</p>
          </div>
        </div>
      </div>

      <!-- Payment -->
      <div v-if="commande" class="flex flex-col bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50">
          <h2 class="text-sm font-semibold text-slate-900">Paiement</h2>
        </div>

        <div class="flex-1 p-6 space-y-6">
          <!-- Mode de paiement -->
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-2">Mode de paiement</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                @click="modePaiement = 'especes'"
                :class="[
                  'p-3 rounded-lg border-2 transition-all flex flex-col items-center gap-2',
                  modePaiement === 'especes' 
                    ? 'border-emerald-500 bg-emerald-50' 
                    : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <Banknote class="w-6 h-6" :class="modePaiement === 'especes' ? 'text-emerald-600' : 'text-slate-400'" />
                <span class="text-xs font-medium">Espèces</span>
              </button>

              <button
                @click="modePaiement = 'carte'"
                :class="[
                  'p-3 rounded-lg border-2 transition-all flex flex-col items-center gap-2',
                  modePaiement === 'carte' 
                    ? 'border-emerald-500 bg-emerald-50' 
                    : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <CreditCard class="w-6 h-6" :class="modePaiement === 'carte' ? 'text-emerald-600' : 'text-slate-400'" />
                <span class="text-xs font-medium">Carte</span>
              </button>

              <button
                @click="modePaiement = 'mobile_money'"
                :class="[
                  'p-3 rounded-lg border-2 transition-all flex flex-col items-center gap-2',
                  modePaiement === 'mobile_money' 
                    ? 'border-emerald-500 bg-emerald-50' 
                    : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <Smartphone class="w-6 h-6" :class="modePaiement === 'mobile_money' ? 'text-emerald-600' : 'text-slate-400'" />
                <span class="text-xs font-medium">Mobile</span>
              </button>
            </div>
          </div>

          <!-- Montant reçu -->
          <div>
            <label class="block text-xs font-medium text-slate-600 mb-2">Montant reçu</label>
            <div class="relative">
              <input
                v-model.number="montantRecu"
                type="number"
                step="100"
                class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg text-lg font-semibold text-slate-900 outline-none focus:border-emerald-500"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">Ar</span>
            </div>
          </div>

          <!-- Montant à rendre -->
          <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-slate-600">Montant à rendre</span>
              <span class="text-2xl font-bold" :class="montantRendu >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                {{ montantRendu }} Ar
              </span>
            </div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-200">
          <button
            @click="processPayment"
            :disabled="!canPay || loading"
            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg font-bold text-base hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-500/30"
          >
            {{ loading ? 'Traitement...' : 'Valider le Paiement' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Print Template (hidden) -->
    <div v-if="lastVente" class="hidden print:block print:p-8">
      <div class="max-w-sm mx-auto border-2 border-slate-300 p-6 font-mono text-sm">
        <div class="text-center mb-4 border-b-2 border-dashed border-slate-300 pb-4">
          <h1 class="text-xl font-bold">PHARMACIE PRO</h1>
          <p class="text-xs">Reçu de Paiement</p>
        </div>

        <div class="space-y-1 mb-4 text-xs">
          <p><strong>N° Vente:</strong> VTE-{{ lastVente.id }}</p>
          <p><strong>N° Commande:</strong> {{ lastVente.commande?.numero_ticket }}</p>
          <p><strong>Date:</strong> {{ new Date(lastVente.created_at).toLocaleString('fr-FR') }}</p>
          <p><strong>Caissier:</strong> {{ lastVente.user?.name }}</p>
        </div>

        <div class="border-t border-b border-slate-300 py-3 mb-3">
          <div v-for="detail in lastVente.details" :key="detail.id" class="flex justify-between text-xs mb-1">
            <span>{{ detail.medicament.nom }} x{{ detail.quantite }}</span>
            <span>{{ detail.prix_unitaire * detail.quantite }} Ar</span>
          </div>
        </div>

        <div class="space-y-1 text-xs mb-4">
          <div class="flex justify-between">
            <span>TOTAL:</span>
            <span class="font-bold">{{ lastVente.total }} Ar</span>
          </div>
          <div class="flex justify-between">
            <span>Reçu:</span>
            <span>{{ lastVente.montant_recu }} Ar</span>
          </div>
          <div class="flex justify-between">
            <span>Rendu:</span>
            <span>{{ lastVente.montant_rendu }} Ar</span>
          </div>
          <div class="flex justify-between">
            <span>Mode:</span>
            <span class="capitalize">{{ lastVente.mode_paiement }}</span>
          </div>
        </div>

        <div class="text-center text-xs border-t-2 border-dashed border-slate-300 pt-4">
          <p>Merci de votre visite!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  .print\:block, .print\:block * {
    visibility: visible;
  }
  .print\:block {
    position: absolute;
    left: 0;
    top: 0;
  }
}
</style>
