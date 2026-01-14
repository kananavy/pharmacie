<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import axios from '../api/axios'
import QrcodeVue from 'qrcode.vue'
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
  AlertCircle,
  Loader2
} from 'lucide-vue-next'

interface CommandeDetail {
  id: number
  medicament: { nom: string }
  quantite: number
  prix_unitaire: number
}

interface Commande {
  id: number
  numero_ticket: string
  total: number
  statut: string
  vendeur: { name: string }
  details: CommandeDetail[]
  created_at: string
}

interface Vente {
  id: number
  total: number
  montant_rendu: number
  commande_id?: number
  // Extended fields for receipt
  numero_ticket?: string
  details?: CommandeDetail[]
  vendeur_nom?: string
  mode_paiement?: string
  montant_recu?: number
  date_paiement?: string
}

const ticketNumber = ref('')
const commande = ref<Commande | null>(null)
const loading = ref(false)
const modePaiement = ref<'especes' | 'carte' | 'mobile_money'>('especes')
const montantRecu = ref<number>(0)
const showSuccess = ref(false)
const lastVente = ref<Vente | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const errorMessage = ref<string | null>(null)
const scannerInitializing = ref(false)
const isScannerActive = ref(false)

let scanner: Html5QrcodeScanner | null = null
let scannerCleanupPromise: Promise<void> | null = null

// Cleanup scanner safely
const cleanupScanner = async (): Promise<void> => {
  if (scannerCleanupPromise) {
    await scannerCleanupPromise
  }
  
  if (scanner) {
    // Attempt to clear
    try {
      await scanner.clear()
    } catch (err) {
      console.warn('Scanner clear error (ignored):', err)
    }
    scanner = null
  }
}

const startScanner = async () => {
  if (scannerInitializing.value) return
  scannerInitializing.value = true

  await cleanupScanner()
  
  // Set flag to true to render the container
  isScannerActive.value = true
  
  // Wait for DOM
  await nextTick()
  
  try {
     // Extra safety delay for rendering
    await new Promise(r => setTimeout(r, 100))

    const element = document.getElementById('qr-reader')
    if (!element) throw new Error('Element scanner introuvable')

    scanner = new Html5QrcodeScanner(
      'qr-reader',
      { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        showTorchButtonIfSupported: true
      },
      false
    )
    
    scanner.render(onScanSuccess, onScanFailure)
  } catch (err: any) {
    console.error('Scanner init error:', err)
    errorMessage.value = `Erreur caméra: ${err.message || 'Impossible de démarrer'}`
    isScannerActive.value = false
  } finally {
    scannerInitializing.value = false
  }
}

const stopScanner = async () => {
  await cleanupScanner()
  isScannerActive.value = false
}

const toggleScanner = async () => {
  if (isScannerActive.value) {
    await stopScanner()
    // Focus manual input when closing scanner
    setTimeout(() => searchInput.value?.focus(), 100)
  } else {
    await startScanner()
  }
}

const onScanSuccess = (decodedText: string) => {
  if (decodedText !== ticketNumber.value) {
    ticketNumber.value = decodedText
    searchCommande()
  }
}

const onScanFailure = (error: any) => {
  // Ignore scan errors
}

onMounted(() => {
  // Default: Scanner OFF, Manual Focus
  searchInput.value?.focus()
})

onUnmounted(() => {
  stopScanner()
})

const montantRendu = computed(() => {
  if (!commande.value) return 0
  return Math.max(0, montantRecu.value - commande.value.total)
})

const canPay = computed(() => {
  return commande.value && montantRecu.value >= commande.value.total
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('fr-MG').format(amount)
}

const searchCommande = async () => {
  if (!ticketNumber.value.trim()) return

  loading.value = true
  errorMessage.value = null
  try {
    const res = await axios.get('/commandes/pending/all')
    const found = res.data.find((c: Commande) => c.numero_ticket === ticketNumber.value.trim())
    
    if (found) {
      commande.value = found
      montantRecu.value = found.total
      
      // If we are scanning, you might want to stop to focus on payment
      // For now, let's stop scanner to avoid background resource usage
      if (isScannerActive.value) {
         await stopScanner()
      }
    } else {
      errorMessage.value = 'Commande non trouvée ou déjà payée'
      commande.value = null
      ticketNumber.value = ''
      if (!isScannerActive.value) {
         searchInput.value?.focus()
      }
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la recherche'
    console.error(err)
  } finally {
    loading.value = false
  }
}

const processPayment = async () => {
  if (!commande.value || !canPay.value) return

  loading.value = true
  errorMessage.value = null
  
  try {
    const res = await axios.post(`/commandes/${commande.value.id}/pay`, {
      mode_paiement: modePaiement.value,
      montant_recu: montantRecu.value
    })

    // Capture receipt data BEFORE clearing commande
    const receiptData: Vente = {
        ...res.data,
        numero_ticket: commande.value.numero_ticket,
        details: commande.value.details,
        vendeur_nom: commande.value.vendeur.name,
        mode_paiement: modePaiement.value,
        montant_recu: montantRecu.value,
        date_paiement: new Date().toLocaleString('fr-FR')
    }
    
    lastVente.value = receiptData
    showSuccess.value = true

    // Reset form
    commande.value = null
    ticketNumber.value = ''
    montantRecu.value = 0
    modePaiement.value = 'especes'

    // Auto-hide after 5s
    setTimeout(() => {
      showSuccess.value = false
      // Don't auto-restart scanner in split view, let user decide?
      // Or auto-restart if they were scanning?
      // Let's reset to manual by default to be safe
      if (searchInput.value) searchInput.value.focus()
    }, 5000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors du paiement'
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
  errorMessage.value = null
  
  if (!isScannerActive.value) {
     searchInput.value?.focus()
  }
}

const dismissSuccess = async () => {
  showSuccess.value = false
  // Return focus
  searchInput.value?.focus()
}

// Quick amount buttons for cash payments
const quickAmounts = computed(() => {
  if (!commande.value) return []
  const total = commande.value.total
  const rounded = [
    Math.ceil(total / 1000) * 1000,
    Math.ceil(total / 5000) * 5000,
    Math.ceil(total / 10000) * 10000,
  ]
  return [...new Set([total, ...rounded])].slice(0, 4)
})
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col md:gap-6">
    <!-- Header (Desktop) -->
    <div class="hidden md:flex items-center justify-between mb-2">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <CreditCard class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Caisse Mobile</h1>
          <p class="text-xs text-slate-500">Gestion des encaissements</p>
        </div>
      </div>
    </div>

    <!-- Error Toast -->
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
      <div v-if="errorMessage" class="fixed top-4 left-4 right-4 md:left-auto md:right-4 md:w-96 z-40 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl shadow-lg flex items-start gap-3">
        <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
        <div class="flex-1"><p class="font-medium text-sm">{{ errorMessage }}</p></div>
        <button @click="errorMessage = null" class="p-1 hover:bg-rose-100 rounded-lg"><X class="w-4 h-4" /></button>
      </div>
    </Transition>

    <!-- Success Overlay -->
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showSuccess" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95">
          <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-center text-white">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4"><Check class="w-8 h-8" /></div>
            <h2 class="text-2xl font-bold mb-1">Paiement Validé!</h2>
            <p class="text-emerald-100">Ticket #{{ lastVente?.numero_ticket }}</p>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-slate-100">
              <span class="text-slate-500">Montant Total</span>
              <span class="text-xl font-bold text-slate-900">{{ formatCurrency(lastVente?.total ?? 0) }} Ar</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-slate-100">
              <span class="text-slate-500">Rendu</span>
              <span class="text-xl font-bold text-emerald-600">{{ formatCurrency(lastVente?.montant_rendu ?? 0) }} Ar</span>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button @click="printReceipt" class="py-3 px-4 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 flex items-center justify-center gap-2 transition-colors"><Printer class="w-4 h-4" /> Reçu</button>
              <button @click="showSuccess = false" class="py-3 px-4 bg-emerald-500 text-white rounded-xl font-semibold hover:bg-emerald-600 transition-colors">Prochain Client</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- MAIN SPLIT LAYOUT -->
    <div class="flex-1 grid md:grid-cols-12 gap-6 min-h-0">
      
      <!-- LEFT COL: SEARCH & SCAN (Always Visible) -->
      <div class="md:col-span-5 flex flex-col gap-4">
        
        <!-- Toggle Header -->
        <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
               <component :is="isScannerActive ? ScanLine : Search" class="w-5 h-5" />
             </div>
             <span class="font-bold text-slate-700">{{ isScannerActive ? 'Mode Scanner' : 'Recherche' }}</span>
           </div>
           
           <button 
            @click="toggleScanner"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-bold transition-all border-2 border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
          >
            <component :is="isScannerActive ? Keyboard : Camera" class="w-4 h-4" />
            {{ isScannerActive ? 'Manuel' : 'Scanner' }}
          </button>
        </div>

        <!-- MODE SCANNER -->
        <div v-if="isScannerActive" class="bg-black rounded-xl overflow-hidden relative shadow-md aspect-square flex flex-col">
           <div id="qr-reader" class="flex-1 w-full h-full"></div>
           <div class="absolute bottom-4 left-0 right-0 text-center pointer-events-none">
             <span class="bg-black/60 text-white px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Scanner le code QR</span>
           </div>
           <div v-if="scannerInitializing" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white">
              <Loader2 class="w-8 h-8 animate-spin" />
           </div>
        </div>

        <!-- MODE MANUAL (SIMPLIFIED) -->
        <div v-else class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
          <label class="block text-sm font-medium text-slate-700 mb-2">Numéro du Ticket</label>
          <div class="flex gap-2">
            <input
              ref="searchInput"
              v-model="ticketNumber"
              @keyup.enter="searchCommande"
              type="text"
              placeholder="CMD-..."
              class="flex-1 bg-white border border-slate-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 uppercase font-mono"
            />
            <button
                @click="searchCommande"
                :disabled="loading || !ticketNumber"
                class="px-4 py-2 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800 disabled:opacity-50 flex items-center justify-center"
              >
                <Search v-if="!loading" class="w-4 h-4" />
                <Loader2 v-else class="w-4 h-4 animate-spin" />
              </button>
          </div>
          <p class="text-xs text-slate-500 mt-2">Saisissez la référence complète (ex: CMD-2026-001)</p>
        </div>

      </div>

      <!-- RIGHT COL: PAYMENT DETAILS (Visible Only if Commande) -->
      <div class="md:col-span-7 flex flex-col h-full min-h-0">
        
        <div v-if="commande" class="flex flex-col h-full bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <!-- Order Summary Header -->
          <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <div>
              <h2 class="font-bold text-slate-900 text-lg">Commande #{{ commande.numero_ticket.split('-').pop() }}</h2>
              <p class="text-xs text-slate-500">{{ commande.vendeur.name }} • {{ new Date(commande.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
            </div>
            <button @click="cancelSearch" class="p-2 hover:bg-slate-200 rounded-full text-slate-500">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Items List (Scrollable) -->
          <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-white">
            <div v-for="detail in commande.details" :key="detail.id" class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0">
              <div class="flex items-center gap-3">
                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2 py-1 rounded">{{ detail.quantite }}x</span>
                <div>
                  <p class="font-medium text-slate-900 text-sm">{{ detail.medicament.nom }}</p>
                </div>
              </div>
              <span class="font-bold text-slate-700">{{ formatCurrency(detail.prix_unitaire * detail.quantite) }} Ar</span>
            </div>
          </div>

          <!-- Payment Footer -->
          <div class="bg-slate-50 border-t border-slate-200 p-4 space-y-4">
             <div class="flex justify-between items-end">
               <span class="text-sm font-medium text-slate-500">Total à payer</span>
               <span class="text-3xl font-bold text-slate-900">{{ formatCurrency(commande.total) }} <small class="text-sm font-normal text-slate-500">Ar</small></span>
             </div>

             <!-- Methods -->
             <div class="flex gap-2">
                <button v-for="mode in ['especes', 'carte', 'mobile_money'] as const" :key="mode"
                  @click="modePaiement = mode"
                  :class="['flex-1 py-2 text-sm font-medium rounded-lg border transition-all flex items-center justify-center gap-2', modePaiement === mode ? 'bg-emerald-600 text-white border-emerald-600 shadow-md transform scale-105' : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-500']"
                >
                  <Banknote v-if="mode === 'especes'" class="w-4 h-4" />
                  <CreditCard v-if="mode === 'carte'" class="w-4 h-4" />
                  <Smartphone v-if="mode === 'mobile_money'" class="w-4 h-4" />
                  <span class="capitalize">{{ mode.replace('_', ' ') }}</span>
                </button>
             </div>

             <!-- Cash Amount & Change -->
             <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-xs font-medium text-slate-500">Montant Reçu</label>
                  <div class="relative">
                    <input v-model.number="montantRecu" type="number" class="w-full pl-3 pr-8 py-2 border border-slate-300 rounded-lg text-right font-bold focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="0">
                    <span class="absolute right-3 top-2 text-xs text-slate-400">Ar</span>
                  </div>
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-medium text-slate-500">Rendu</label>
                  <div :class="['w-full px-3 py-2 border rounded-lg text-right font-bold bg-slate-100', montantRendu >= 0 ? 'text-emerald-600 border-emerald-200' : 'text-slate-400 border-slate-200']">
                    {{ formatCurrency(montantRendu) }} Ar
                  </div>
                </div>
             </div>

             <!-- Quick Cash -->
             <div v-if="modePaiement === 'especes'" class="flex gap-2 overflow-x-auto pb-1">
                <button v-for="amount in quickAmounts" :key="amount" @click="montantRecu = amount" class="px-3 py-1 bg-white border border-slate-200 rounded-full text-xs font-medium hover:border-emerald-500 hover:text-emerald-600 whitespace-nowrap">
                  {{ formatCurrency(amount) }}
                </button>
             </div>

             <button @click="processPayment" :disabled="!canPay || loading" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-lg shadow-emerald-500/20">
               {{ loading ? 'Validation...' : 'Encaisser' }}
             </button>
          </div>
        </div>

        <!-- Placeholder when no command -->
        <div v-else class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50 border border-slate-200 rounded-xl border-dashed">
           <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
             <Search class="w-8 h-8 opacity-50" />
           </div>
           <p class="font-medium">En attente de ticket...</p>
           <p class="text-sm opacity-70">Scannez ou saisissez un numéro</p>
        </div>

      </div>
    </div>

    <!-- Thermal Print Template -->
    <div v-if="lastVente" class="hidden print:block print:p-0">
      <div class="w-[80mm] mx-auto p-2 font-mono text-xs text-black">
        <div class="text-center mb-2">
          <h1 class="text-base font-bold uppercase mb-1">Pharmacie</h1>
           <!-- @ts-ignore -->
           <p class="text-[10px] uppercase mb-1">Ticket #{{ lastVente.numero_ticket }}</p>
           
           <!-- QR Code Centered -->
           <div class="flex justify-center my-1">
             <!-- @ts-ignore -->
             <qrcode-vue :value="lastVente.numero_ticket || `VENTE-${lastVente.id}`" :size="70" level="L" render-as="svg" />
           </div>

           <!-- Info Paiement -->
           <div class="text-[9px] mt-1 space-y-0.5">
             <p>Vendeur: {{ lastVente.vendeur_nom }}</p>
             <p>Payé le: {{ lastVente.date_paiement }}</p>
           </div>
        </div>
        
        <div class="border-t border-black border-dashed py-1 my-1">
            <div v-for="item in lastVente.details" :key="item.id" class="flex justify-between text-[10px] my-0.5">
                <span class="truncate pr-2">{{ item.medicament.nom }} (x{{ item.quantite }})</span>
                <span class="whitespace-nowrap">{{ formatCurrency(item.prix_unitaire * item.quantite) }}</span>
            </div>
        </div>
        
        <div class="border-t border-b border-black py-2 my-1 border-dashed relative">
           <!-- Stamp Overlay -->
           <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
              <div class="border-[3px] border-slate-900 px-6 py-1 -rotate-12 rounded-lg text-xl font-black uppercase tracking-widest text-slate-900 opacity-40 mix-blend-multiply" style="color: black; border-color: black;">
                PAYÉ
              </div>
           </div>

           <div class="flex justify-between font-bold mb-0.5">
             <span class="uppercase">Total</span>
             <span>{{ formatCurrency(lastVente.total) }} Ar</span>
           </div>
           <div class="flex justify-between text-[10px]">
             <span class="capitalize">Mode: {{ lastVente.mode_paiement?.replace('_', ' ') }}</span>
             <span>Reçu: {{ formatCurrency(lastVente.montant_recu || 0) }}</span>
           </div>
           <div class="flex justify-between text-[10px] font-semibold mt-0.5">
             <span>Rendu</span>
             <span>{{ formatCurrency(lastVente.montant_rendu) }} Ar</span>
           </div>
        </div>
        
        <div class="text-center mt-2">
          <p class="text-[10px] uppercase font-bold">Merci de votre visite!</p>
          <p class="text-[8px]">Conservez ce ticket pour vérification.</p>
        </div>
      </div>
    </div>
  </div>
</template>


<style>
/* QR Reader Styling Overrides */
#qr-reader {
  border: none !important;
}
#qr-reader video {
  object-fit: cover;
  width: 100% !important;
  height: 100% !important;
  border-radius: 0.75rem; 
}
/* Hide unneeded html5-qrcode elements */
#qr-reader__dashboard_section_csr button {
  background-color: rgb(16 185 129) !important;
  color: white !important;
  padding: 0.5rem 1rem !important;
  border-radius: 0.5rem !important;
  font-weight: 500 !important;
  transition: background-color 0.2s !important;
}
#qr-reader__status_span, 
#qr-reader__filescan_input, 
#qr-reader__camera_permission_button {
  display: none !important;
}

/* Animations */
@keyframes zoom-in-95 {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-in { animation: zoom-in-95 0.2s ease-out; }

/* Input numbers */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type="number"] { -moz-appearance: textfield; }

@media print {
  body * { visibility: hidden; }
  .print\:block, .print\:block * { visibility: visible; }
  .print\:block { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
