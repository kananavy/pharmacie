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

// --- INTERFACES ---

interface Medicament {
  id: number
  nom: string
  code: string
  prix: number
  stock: number
  // Unit fields
  unites_par_boite?: number
  stock_vrac?: number
  prix_unitaire?: number
}

interface CommandeDetail {
  id: number
  medicament: { nom: string, prix?: number }
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
  numero_ticket?: string
  details?: any[]
  vendeur_nom?: string
  mode_paiement?: string
  montant_recu?: number
  date_paiement?: string
  user?: { name: string }
}

interface CartItem {
  medicament: Medicament
  quantite: number
  prix_unitaire?: number
  type_vente?: 'boite' | 'unite'
}

// --- STATE ---

const viewMode = ref<'ticket' | 'direct'>('ticket')
const ticketNumber = ref('')
const commande = ref<Commande | null>(null)
const loading = ref(false)
const modePaiement = ref<'especes' | 'carte' | 'mobile_money'>('especes')
const montantRecu = ref<number | null>(null)
const showSuccess = ref(false)
const lastVente = ref<Vente | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const errorMessage = ref<string | null>(null)

// Scanner
const scannerInitializing = ref(false)
const isScannerActive = ref(false)
let scanner: Html5QrcodeScanner | null = null
let scannerCleanupPromise: Promise<void> | null = null

// Direct Sale
const productSearch = ref('')
const isSearching = ref(false)
const searchResults = ref<Medicament[]>([])
const cart = ref<CartItem[]>([])

// --- COMPUTED ---

const activeItems = computed(() => {
  if (viewMode.value === 'ticket') {
    return commande.value?.details || []
  } else {
    return cart.value
  }
})

const currentTotal = computed(() => {
   if (viewMode.value === 'ticket') {
      return commande.value?.total || 0
   } else {
      return cart.value.reduce((sum, item) => sum + ((item.prix_unitaire || item.medicament.prix) * item.quantite), 0)
   }
})

const montantRendu = computed(() => {
  const recu = montantRecu.value || 0
  if (currentTotal.value === 0) return 0
  return Math.max(0, recu - currentTotal.value)
})

const canPay = computed(() => {
  const total = currentTotal.value
  const recu = montantRecu.value || 0
  
  if (total <= 0) return false
  if (recu < total) return false
  
  return true
})

const quickAmounts = computed(() => {
  const total = currentTotal.value
  if (total <= 0) return []
  
  const rounded = [
    Math.ceil(total / 1000) * 1000,
    Math.ceil(total / 5000) * 5000,
    Math.ceil(total / 10000) * 10000,
  ]
  const bills = [1000, 2000, 5000, 10000, 20000]
  const nextBills = bills.filter(b => b > total).slice(0, 2)
  
  return [...new Set([total, ...rounded, ...nextBills])].sort((a,b) => a-b).slice(0, 5)
})

// --- METHODS ---

const switchMode = (mode: 'ticket' | 'direct') => {
  viewMode.value = mode
  errorMessage.value = null
  showSuccess.value = false
  
  if (mode === 'direct') {
    commande.value = null
    ticketNumber.value = ''
    if (isScannerActive.value) stopScanner()
  } else {
    // Switching to ticket
    nextTick(() => searchInput.value?.focus())
  }
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('fr-MG').format(amount)
}

// Scanner Logic
const cleanupScanner = async (): Promise<void> => {
  if (scannerCleanupPromise) await scannerCleanupPromise
  if (scanner) {
    try { await scanner.clear() } catch (err) { console.warn('Scanner clear error:', err) }
    scanner = null
  }
}

const startScanner = async () => {
  if (scannerInitializing.value) return
  scannerInitializing.value = true
  await cleanupScanner()
  isScannerActive.value = true
  await nextTick()
  
  try {
    await new Promise(r => setTimeout(r, 100))
    const element = document.getElementById('qr-reader')
    if (!element) throw new Error('Element scanner introuvable')

    scanner = new Html5QrcodeScanner(
      'qr-reader',
      { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0, showTorchButtonIfSupported: true },
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

const onScanFailure = (error: any) => { /* ignore */ }

onMounted(() => {
  searchInput.value?.focus()
})

onUnmounted(() => {
  stopScanner()
})

// Ticket Search
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
      if (isScannerActive.value) await stopScanner()
    } else {
      errorMessage.value = 'Commande non trouvée ou déjà payée'
      commande.value = null
      ticketNumber.value = ''
      if (!isScannerActive.value) searchInput.value?.focus()
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors de la recherche'
  } finally {
    loading.value = false
  }
}

const cancelSearch = () => {
  commande.value = null
  ticketNumber.value = ''
  montantRecu.value = 0
  errorMessage.value = null
  if (!isScannerActive.value) searchInput.value?.focus()
}

// Direct Sale Logic
let searchTimer: any
const handleProductSearch = () => {
  clearTimeout(searchTimer)
  if (productSearch.value.length < 2) {
    searchResults.value = []
    return
  }
  
  isSearching.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await axios.get('/medicaments', { params: { search: productSearch.value } })
      searchResults.value = res.data
    } catch (e) {
      console.error(e)
    } finally {
      isSearching.value = false
    }
  }, 300)
}

const addToCart = (product: Medicament) => {
   const existing = cart.value.find(i => i.medicament.id === product.id && i.type_vente === 'boite')
   
   if (existing) {
      if (existing.quantite < product.stock) {
         existing.quantite++
      } else {
         errorMessage.value = `Stock global insuffisant pour ${product.nom}`
         setTimeout(() => errorMessage.value = null, 3000)
      }
   } else {
      cart.value.push({ 
        medicament: product, 
        quantite: 1,
        type_vente: 'boite',
        prix_unitaire: product.prix
      })
   }
   productSearch.value = ''
   searchResults.value = []
}

const toggleUnit = (index: number) => {
   const item = cart.value[index]
   if (!item) return
   
   // Check if product allows units
   if (!item.medicament.unites_par_boite || item.medicament.unites_par_boite <= 1 || !item.medicament.prix_unitaire) {
      errorMessage.value = "Ce produit ne se vend pas à l'unité"
      setTimeout(() => errorMessage.value = null, 2000)
      return
   }

   const newType = item.type_vente === 'boite' ? 'unite' : 'boite'
   
   // Reset qty to 1 when switching to avoid stock issues
   item.quantite = 1 
   item.type_vente = newType as 'boite' | 'unite'
   item.prix_unitaire = newType === 'unite' ? item.medicament.prix_unitaire : item.medicament.prix
}

const updateQty = (index: number, change: number) => {
   const item = cart.value[index]
   if (!item) return
   
   const newQty = item.quantite + change
   
   if (newQty <= 0) {
      cart.value.splice(index, 1)
      return
   }

   // Validate Stock
   let maxQty = 0
   if (item.type_vente === 'unite') {
       // Total units = (Full Boxes * UnitsPerBox) + LooseUnits
       const stockBoxes = item.medicament.stock || 0
       const unitsPerBox = item.medicament.unites_par_boite || 1
       const looseUnits = item.medicament.stock_vrac || 0
       maxQty = (stockBoxes * unitsPerBox) + looseUnits
   } else {
       // Box mode
       maxQty = item.medicament.stock
   }

   if (newQty <= maxQty) {
      item.quantite = newQty
   } else {
      errorMessage.value = `Stock insuffisant (${maxQty} disponible)`
      setTimeout(() => errorMessage.value = null, 3000)
   }
}

const removeFromCart = (index: number) => {
   cart.value.splice(index, 1)
}

const clearCart = () => {
   cart.value = []
   montantRecu.value = null
   modePaiement.value = 'especes'
   searchResults.value = []
   productSearch.value = ''
}

// Payment Processing
const processTransaction = async () => {
  if (viewMode.value === 'ticket') {
    await processTicketPayment()
  } else {
    await processDirectSale()
  }
}

const processTicketPayment = async () => {
  if (!commande.value || !canPay.value) return

  loading.value = true
  errorMessage.value = null
  
  try {
    const res = await axios.post(`/commandes/${commande.value.id}/pay`, {
      mode_paiement: modePaiement.value,
      montant_recu: montantRecu.value
    })

    lastVente.value = {
        ...res.data,
        numero_ticket: commande.value.numero_ticket,
        details: commande.value.details,
        vendeur_nom: commande.value.vendeur.name,
        mode_paiement: modePaiement.value,
        montant_recu: montantRecu.value,
        date_paiement: new Date().toLocaleString('fr-FR')
    }
    
    showSuccess.value = true
    commande.value = null
    ticketNumber.value = ''
    montantRecu.value = 0
    modePaiement.value = 'especes'

  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Erreur lors du paiement'
  } finally {
    loading.value = false
  }
}

const processDirectSale = async () => {
   if (cart.value.length === 0) return
   loading.value = true
   errorMessage.value = null
   
   try {
      const payload = {
         items: cart.value.map(i => ({
            medicament_id: i.medicament.id,
            quantite: i.quantite,
            type_vente: i.type_vente
         })),
         mode_paiement: modePaiement.value,
         montant_recu: montantRecu.value || currentTotal.value
      }
      
      const res = await axios.post('/ventes', payload)
      // Assuming response includes details with medicament relations
      
      lastVente.value = {
         ...res.data,
         numero_ticket: null, // No ticket for direct sale
         vendeur_nom: res.data.user?.name || 'Moi',
         mode_paiement: modePaiement.value,
         montant_recu: montantRecu.value || currentTotal.value,
         date_paiement: new Date().toLocaleString('fr-FR'),
         // Ensure details structure matches for print
         details: res.data.details || [] 
      }
      
      showSuccess.value = true
      clearCart()
      
   } catch (err: any) {
      errorMessage.value = err.response?.data?.message || 'Erreur vente'
   } finally {
      loading.value = false
   }
}

const dismissSuccess = async () => {
  showSuccess.value = false
  if (viewMode.value === 'ticket') {
     searchInput.value?.focus()
  } else {
     // maybe focus search
  }
}

const printReceipt = () => {
  window.print()
}
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
          <h1 class="text-2xl font-semibold text-slate-900">Caisse & Point de Vente</h1>
          <p class="text-xs text-slate-500">Gestion des encaissements et ventes directes</p>
        </div>
      </div>
      
      <!-- View Mode Torns -->
      <div class="flex bg-slate-100 p-1 rounded-xl">
        <button 
          @click="switchMode('ticket')" 
          :class="['px-4 py-2 text-sm font-medium rounded-lg transition-all', viewMode === 'ticket' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
        >
          Encaissement Ticket
        </button>
        <button 
          @click="switchMode('direct')" 
          :class="['px-4 py-2 text-sm font-medium rounded-lg transition-all', viewMode === 'direct' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700']"
        >
          Vente Directe
        </button>
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
            <p class="text-emerald-100">
               {{ lastVente?.numero_ticket ? 'Ticket #' + lastVente?.numero_ticket : 'Vente #' + lastVente?.id }}
            </p>
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
              <button @click="dismissSuccess" class="py-3 px-4 bg-emerald-500 text-white rounded-xl font-semibold hover:bg-emerald-600 transition-colors">Prochain Client</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- MAIN SPLIT LAYOUT -->
    <div class="flex-1 grid md:grid-cols-12 gap-6 min-h-0">
      
      <!-- LEFT COL: INPUT & SEARCH (Scanning or Product Search) -->
      <div class="md:col-span-5 flex flex-col gap-4">
        
        <!-- MODE: TICKET PAYMENT -->
        <template v-if="viewMode === 'ticket'">
            <!-- Toggle Header -->
            <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
               <div class="flex items-center gap-2">
                 <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                   <component :is="isScannerActive ? ScanLine : Search" class="w-5 h-5" />
                 </div>
                 <span class="font-bold text-slate-700">{{ isScannerActive ? 'Mode Scanner' : 'Recherche Ticket' }}</span>
               </div>
               
               <button 
                @click="toggleScanner"
                class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-bold transition-all border-2 border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
              >
                <component :is="isScannerActive ? Keyboard : Camera" class="w-4 h-4" />
                {{ isScannerActive ? 'Manuel' : 'Scanner' }}
              </button>
            </div>
    
            <!-- CAMERA -->
            <div v-if="isScannerActive" class="bg-black rounded-xl overflow-hidden relative shadow-md aspect-square flex flex-col">
               <div id="qr-reader" class="flex-1 w-full h-full"></div>
               <div class="absolute bottom-4 left-0 right-0 text-center pointer-events-none">
                 <span class="bg-black/60 text-white px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">Scanner le code QR</span>
               </div>
               <div v-if="scannerInitializing" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white">
                  <Loader2 class="w-8 h-8 animate-spin" />
               </div>
            </div>
    
            <!-- MANUAL INPUT -->
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
        </template>

        <!-- MODE: DIRECT SALE -->
        <template v-else>
           <div class="bg-white border border-slate-200 rounded-xl flex flex-col h-full overflow-hidden shadow-sm">
              <div class="p-4 border-b border-slate-100 bg-slate-50">
                  <label class="block text-sm font-medium text-slate-700 mb-2">Rechercher un produit</label>
                  <div class="relative">
                     <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
                     <input 
                        v-model="productSearch"
                        @input="handleProductSearch"
                        type="text"
                        placeholder="Nom ou Code du médicament..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                     >
                     <div v-if="isSearching" class="absolute right-3 top-2.5">
                        <Loader2 class="w-4 h-4 animate-spin text-emerald-500" />
                     </div>
                  </div>
              </div>
              
              <div class="flex-1 overflow-y-auto p-2">
                 <div v-if="searchResults.length > 0" class="space-y-2">
                    <button 
                      v-for="product in searchResults" 
                      :key="product.id"
                      @click="addToCart(product)"
                      class="w-full text-left p-3 hover:bg-indigo-50 border border-slate-100 rounded-lg group transition-colors flex justify-between items-center"
                    >
                       <div>
                          <p class="font-medium text-slate-900">{{ product.nom }}</p>
                          <p class="text-xs text-slate-500">{{ product.code }} • Stock: {{ product.stock }}</p>
                       </div>
                       <div class="text-right">
                          <span class="block font-bold text-emerald-600">{{ formatCurrency(product.prix) }} Ar</span>
                          <span v-if="product.stock <= 0" class="text-[10px] text-rose-500 font-bold uppercase">Rupture</span>
                       </div>
                    </button>
                 </div>
                 <div v-else-if="productSearch.length > 2" class="text-center py-8 text-slate-400">
                    <p>Aucun produit trouvé</p>
                 </div>
                 <div v-else class="text-center py-8 text-slate-400">
                    <Search class="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Commencez la saisie pour rechercher</p>
                 </div>
              </div>
           </div>
        </template>

      </div>

      <!-- RIGHT COL: SUMMARY & PAYMENT (Unified for both modes) -->
      <div class="md:col-span-7 flex flex-col h-full min-h-0 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        
        <!-- 1. CART / DETAILS VIEW -->
        <div class="flex-1 flex flex-col min-h-0">
             <!-- Header -->
             <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
               <h2 class="font-bold text-slate-900 text-lg">
                  {{ viewMode === 'ticket' ? (commande ? `Commande #${commande.numero_ticket.split('-').pop()}` : 'Détails du Ticket') : 'Panier en cours' }}
               </h2>
               <div v-if="viewMode === 'ticket' && commande">
                 <button @click="cancelSearch" class="p-2 hover:bg-slate-200 rounded-full text-slate-500">
                   <X class="w-5 h-5" />
                 </button>
               </div>
               <div v-if="viewMode === 'direct'">
                  <button @click="clearCart" :disabled="cart.length === 0" class="text-xs text-rose-600 font-medium hover:text-rose-800 disabled:opacity-50">Vider le panier</button>
               </div>
             </div>
             
             <!-- Content List -->
             <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-white relative">
                 
                 <!-- Empty State -->
                 <div v-if="!activeItems || activeItems.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                    <div v-if="viewMode === 'ticket'" class="flex flex-col items-center">
                       <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3"><Search class="w-6 h-6 opacity-50"/></div>
                       <p>En attente de ticket...</p>
                    </div>
                    <div v-else class="flex flex-col items-center">
                       <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3"><div class="relative"><Search class="w-5 h-5 opacity-50"/><div class="absolute -right-1 -top-1 w-2 h-2 bg-emerald-400 rounded-full"></div></div></div>
                       <p>Panier vide. Ajoutez des produits à gauche.</p>
                    </div>
                 </div>

                 <!-- Items -->
                 <div v-for="(item, index) in activeItems" :key="index" class="flex justify-between items-center py-2 border-b border-slate-50 last:border-0 group">
                    <div class="flex items-center gap-3 flex-1">
                      <!-- Qty Control for Direct Sale -->
                      <div v-if="viewMode === 'direct'" class="flex items-center border border-slate-200 rounded-lg overflow-hidden shrink-0">
                         <button @click="updateQty(index, -1)" class="px-2 py-1 hover:bg-slate-100 text-slate-500">-</button>
                         <span class="px-2 text-sm font-bold min-w-[32px] text-center">{{ item.quantite }}</span>
                         <button @click="updateQty(index, 1)" class="px-2 py-1 hover:bg-slate-100 text-slate-600">+</button>
                      </div>
                      <span v-else class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2 py-1 rounded">{{ item.quantite }}x</span>
                      
                      <div class="min-w-0">
                        <p class="font-medium text-slate-900 text-sm truncate">{{ item?.medicament?.nom }}</p>
                        <div class="flex items-center gap-2">
                           <p class="text-[10px] text-slate-500">{{ formatCurrency(item.prix_unitaire || item?.medicament?.prix || 0) }} / {{ item.type_vente === 'unite' ? 'unité' : 'boîte' }}</p>
                           <button 
                              v-if="viewMode === 'direct' && item.medicament.unites_par_boite && item.medicament.unites_par_boite > 1"
                              @click="toggleUnit(index)"
                              class="text-[10px] font-bold px-1.5 py-0.5 rounded border border-slate-200 hover:bg-slate-100 transition-colors"
                              :class="item.type_vente === 'unite' ? 'text-blue-600 bg-blue-50 border-blue-200' : 'text-slate-600'"
                           >
                              {{ item.type_vente === 'unite' ? 'Unités (Détail)' : 'Boîte (Standard)' }}
                           </button>
                        </div>
                      </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                       <span class="font-bold text-slate-700">{{ formatCurrency((item.prix_unitaire || item?.medicament?.prix || 0) * item.quantite) }} Ar</span>
                       <button v-if="viewMode === 'direct'" @click="removeFromCart(index)" class="p-1 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity">
                          <X class="w-4 h-4" />
                       </button>
                    </div>
                 </div>
             </div>
        </div>

        <!-- 2. PAYMENT FOOTER -->
        <div class="bg-slate-50 border-t border-slate-200 p-4 space-y-4">
            <div class="flex justify-between items-end">
               <span class="text-sm font-medium text-slate-500">Total à payer</span>
               <span class="text-3xl font-bold text-slate-900">{{ formatCurrency(currentTotal) }} <small class="text-sm font-normal text-slate-500">Ar</small></span>
            </div>

            <!-- Payment Methods -->
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

            <!-- Amount Inputs -->
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

            <!-- Action Button -->
            <button 
              @click="processTransaction" 
              :disabled="!canPay || loading" 
              class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-lg shadow-emerald-500/20"
            >
               <span v-if="loading" class="flex items-center justify-center gap-2"><Loader2 class="w-5 h-5 animate-spin" /> Traitement...</span>
               <span v-else>{{ viewMode === 'ticket' ? 'Encaisser Ticket' : 'Valider la Vente' }}</span>
            </button>
        </div>

      </div>
    </div>

    <!-- Thermal Print Template -->
    <div v-if="lastVente" class="hidden print:block print:p-0">
      <div class="w-[80mm] mx-auto p-2 font-mono text-xs text-black">
        <div class="text-center mb-2">
          <h1 class="text-base font-bold uppercase mb-1">Pharmacie</h1>
           <!-- @ts-ignore -->
           <p class="text-[10px] uppercase mb-1">{{ lastVente.numero_ticket ? 'Ticket #' + lastVente.numero_ticket : 'Vente #' + lastVente.id }}</p>
           
           <!-- QR Code Centered -->
           <div class="flex justify-center my-1">
             <!-- @ts-ignore -->
             <qrcode-vue :value="lastVente.numero_ticket || `VENTE-${lastVente.id}`" :size="70" level="L" render-as="svg" />
           </div>

           <!-- Info Paiement -->
           <div class="text-[9px] mt-1 space-y-0.5">
             <p>Vendeur: {{ lastVente.vendeur_nom || lastVente.user?.name || 'Caissier' }}</p>
             <p>Date: {{ new Date().toLocaleString() }}</p>
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
             <span class="capitalize">Mode: {{ (lastVente.mode_paiement || 'Espèces').replace('_', ' ') }}</span>
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
