<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from '../api/axios'
import { Html5QrcodeScanner } from 'html5-qrcode'
import { 
  CreditCard, 
  Search, 
  Printer,
  Check,
  X,
  Banknote,
  Smartphone,
  ScanLine,
  Keyboard,
  Camera,
  RefreshCw
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

const activeTab = ref<'scan' | 'manual'>('scan')
const ticketNumber = ref('')
const commande = ref<Commande | null>(null)
const loading = ref(false)
const modePaiement = ref<'especes' | 'carte' | 'mobile_money'>('especes')
const montantRecu = ref<number>(0)
const showSuccess = ref(false)
const lastVente = ref<any>(null)
const searchInput = ref<HTMLInputElement | null>(null)
let scanner: any = null

// Initialize Scanner when tab changes to 'scan'
const initScanner = () => {
  // Give DOM time to render the container
  setTimeout(() => {
    if (activeTab.value === 'scan' && !scanner) {
      scanner = new Html5QrcodeScanner(
        "qr-reader",
        { fps: 10, qrbox: { width: 250, height: 250 } },
        /* verbose= */ false
      )
      
      scanner.render(onScanSuccess, onScanFailure)
    }
  }, 100)
}

const onScanSuccess = (decodedText: string) => {
  // Stop scanning after success
  // scanner.clear() -> we keep scanning for "next customer" flow or clear if we want
  // But usually we want to stop to show result
  if (decodedText !== ticketNumber.value) {
    ticketNumber.value = decodedText
    searchCommande()
  }
}

const onScanFailure = (error: any) => {
  // handle scan failure, usually better to ignore and keep scanning.
  // console.warn(`Code scan error = ${error}`);
}

const switchTab = (tab: 'scan' | 'manual') => {
  activeTab.value = tab
  if (tab === 'scan') {
    initScanner()
  } else {
    if (scanner) {
      scanner.clear().catch((err: any) => console.error(err))
      scanner = null
    }
    // Auto-focus manual input
    setTimeout(() => searchInput.value?.focus(), 100)
  }
}

onMounted(() => {
  // Start with scanner
  initScanner()
})

onUnmounted(() => {
  if (scanner) {
    scanner.clear().catch((err: any) => console.error(err))
  }
})

const montantRendu = computed(() => {
  if (!commande.value) return 0
  return Math.max(0, montantRecu.value - commande.value.total)
})

const canPay = computed(() => {
  return commande.value && montantRecu.value >= commande.value.total
})

const searchCommande = async () => {
  if (!ticketNumber.value.trim()) return

  loading.value = true
  try {
    const res = await axios.get('/commandes/pending/all')
    const found = res.data.find((c: Commande) => c.numero_ticket === ticketNumber.value.trim())
    
    if (found) {
      commande.value = found
      montantRecu.value = found.total
      // If found via scan, stop scanner to show details clearly
      if (scanner && activeTab.value === 'scan') {
         scanner.clear()
         scanner = null
      }
    } else {
      alert('Commande non trouvée ou déjà payée')
      commande.value = null
      ticketNumber.value = ''
      if (activeTab.value === 'manual') {
        searchInput.value?.focus()
      }
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
      // Restart scanner for next customer if we are in scan mode
      if (activeTab.value === 'scan') {
        initScanner() 
      }
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
  // Restart scanner if cancelled
  if (activeTab.value === 'scan') {
     initScanner()
  }
}
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col md:gap-6">
    <!-- Header (Desktop only) -->
    <div class="hidden md:flex items-center justify-between mb-2">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <CreditCard class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Caisse Mobile</h1>
          <p class="text-xs text-slate-500">Encaissement via QR ou Manuel</p>
        </div>
      </div>
    </div>

    <!-- Success Overlay -->
    <div v-if="showSuccess" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 animate-in fade-in">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-emerald-500 p-6 text-center text-white">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check class="w-8 h-8" />
          </div>
          <h2 class="text-2xl font-bold mb-1">Paiement Validé!</h2>
          <p class="text-emerald-100">Vente #{{ lastVente?.id }}</p>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-slate-500">Montant Total</span>
            <span class="text-xl font-bold text-slate-900">{{ lastVente?.total }} Ar</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-slate-500">Rendu</span>
            <span class="text-xl font-bold text-emerald-600">{{ lastVente?.montant_rendu }} Ar</span>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-2">
            <button @click="printReceipt" class="py-3 px-4 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 flex items-center justify-center gap-2">
              <Printer class="w-4 h-4" /> Reçu
            </button>
            <button @click="showSuccess = false; if(activeTab === 'scan') initScanner()" class="py-3 px-4 bg-emerald-500 text-white rounded-xl font-semibold hover:bg-emerald-600">
              Prochain Client
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Tabs -->
    <div class="flex bg-slate-100 p-1 rounded-xl mb-4 md:hidden">
      <button 
        @click="switchTab('scan')"
        :class="['flex-1 py-2 text-sm font-semibold rounded-lg flex items-center justify-center gap-2 transition-all', activeTab === 'scan' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500']"
      >
        <ScanLine class="w-4 h-4" /> Scanner
      </button>
      <button 
        @click="switchTab('manual')"
        :class="['flex-1 py-2 text-sm font-semibold rounded-lg flex items-center justify-center gap-2 transition-all', activeTab === 'manual' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500']"
      >
        <Keyboard class="w-4 h-4" /> Manuel
      </button>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 grid md:grid-cols-2 gap-4 md:gap-6 min-h-0 overflow-y-auto">
      
      <!-- Left Panel: Scanner/Search -->
      <div v-if="!commande" class="flex flex-col gap-4">
        <!-- Scanner Area -->
        <div v-show="activeTab === 'scan'" class="bg-white border border-slate-200 rounded-2xl overflow-hidden flex flex-col flex-1 min-h-[300px] relative">
           <!-- Camera Viewport -->
           <div id="qr-reader" class="flex-1 bg-black w-full h-full"></div>
           <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
             <div class="w-64 h-64 border-2 border-white/50 rounded-3xl relative">
               <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-emerald-500 rounded-tl-2xl"></div>
               <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-emerald-500 rounded-tr-2xl"></div>
               <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-emerald-500 rounded-bl-2xl"></div>
               <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-emerald-500 rounded-br-2xl"></div>
             </div>
           </div>
           <div class="bg-white p-4 text-center">
             <p class="text-sm font-medium text-slate-600 flex items-center justify-center gap-2">
               <Camera class="w-4 h-4" /> Placez le QR code dans le cadre
             </p>
           </div>
        </div>

        <!-- Manual Input Area -->
        <div v-show="activeTab === 'manual'" class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-center flex-1">
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-600">
               <Keyboard class="w-8 h-8" />
            </div>
            <h2 class="text-lg font-bold text-slate-900">Saisie Manuelle</h2>
            <p class="text-sm text-slate-500">Entrez le numéro du ticket client</p>
          </div>
          
          <div class="flex gap-2">
            <input
              ref="searchInput"
              v-model="ticketNumber"
              @keyup.enter="searchCommande"
              type="text"
              placeholder="Ex: CMD-2026..."
              class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono text-center text-lg uppercase"
            />
          </div>
          <button
              @click="searchCommande"
              :disabled="loading || !ticketNumber"
              class="mt-4 w-full py-3 bg-emerald-600 text-white rounded-xl font-bold text-base hover:bg-emerald-700 disabled:opacity-50 transition-all flex items-center justify-center gap-2"
            >
              {{ loading ? 'Recherche...' : 'Rechercher Ticket' }}
            </button>
        </div>
      </div>

      <!-- Right Panel: Order & Payment -->
      <div v-else class="flex flex-col h-full bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
        <!-- Order Summary Header -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
          <div>
            <h2 class="font-bold text-slate-900">Commande #{{ commande.numero_ticket.split('-').pop() }}</h2>
            <p class="text-xs text-slate-500">{{ commande.vendeur.name }} • {{ new Date(commande.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
          </div>
          <button @click="cancelSearch" class="p-2 hover:bg-slate-200 rounded-full text-slate-500">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Items List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/50">
          <div v-for="detail in commande.details" :key="detail.id" class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex justify-between items-center">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                x{{ detail.quantite }}
              </div>
              <div>
                <p class="font-medium text-slate-900 text-sm">{{ detail.medicament.nom }}</p>
                <p class="text-xs text-slate-400">{{ detail.prix_unitaire }} Ar/u</p>
              </div>
            </div>
            <span class="font-bold text-slate-700">{{ detail.prix_unitaire * detail.quantite }} Ar</span>
          </div>
        </div>

        <!-- Payment Section -->
        <div class="bg-white border-t border-slate-200 p-4 space-y-4">
          <!-- Total Display -->
          <div class="flex justify-between items-end mb-2">
            <span class="text-sm font-medium text-slate-500">Total à payer</span>
            <span class="text-3xl font-bold text-slate-900">{{ commande.total }} <span class="text-sm font-normal text-slate-400">Ar</span></span>
          </div>

          <!-- Payment Method -->
          <div class="grid grid-cols-3 gap-2">
            <button @click="modePaiement = 'especes'" :class="['py-2 rounded-lg text-xs font-bold border', modePaiement === 'especes' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-slate-200 text-slate-600']">Espèces</button>
            <button @click="modePaiement = 'carte'" :class="['py-2 rounded-lg text-xs font-bold border', modePaiement === 'carte' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-slate-200 text-slate-600']">Carte</button>
            <button @click="modePaiement = 'mobile_money'" :class="['py-2 rounded-lg text-xs font-bold border', modePaiement === 'mobile_money' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-slate-200 text-slate-600']">Mobile</button>
          </div>

          <!-- Amount Input -->
          <div class="relative">
             <input
                v-model.number="montantRecu"
                type="number"
                class="w-full pl-4 pr-12 py-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-xl font-bold text-slate-900 outline-none focus:border-emerald-500 transition-colors"
                placeholder="0"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">Ar</span>
          </div>

          <!-- Change & Action -->
          <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
            <span class="text-xs font-medium text-slate-500 uppercase">Rendu</span>
            <span class="text-lg font-bold" :class="montantRendu >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ montantRendu }} Ar</span>
          </div>

          <button
            @click="processPayment"
            :disabled="!canPay || loading"
            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-emerald-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-[0.98]"
          >
            {{ loading ? 'Validation...' : 'Encaisser' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Print Template (hidden) -->
    <div v-if="lastVente" class="hidden print:block print:p-8">
      <!-- Similar print template as before -->
    </div>
  </div>
</template>

<style>
/* Basic styling for QR Reader to look good */
#qr-reader video {
  object-fit: cover;
  width: 100% !important;
  height: 100% !important;
  border-radius: 1rem;
}
@media print {
  body * { visibility: hidden; }
  .print\:block, .print\:block * { visibility: visible; }
  .print\:block { position: absolute; left: 0; top: 0; }
}
</style>
