<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '../api/axios'
import { formatCurrency } from '../utils/formatting'
import { useAuthStore } from '../stores/auth'
import { 
  Plus, 
  Minus, 
  Trash2, 
  Search, 
  CheckCircle,
  FileText,
  ShoppingCart,
  Receipt,
  AlertCircle,
  Pill,
  X,
  CreditCard,
  Banknote,
  Loader2,
  PackageSearch,
  User
} from 'lucide-vue-next'

const auth = useAuthStore()

interface Medicament {
  id: number;
  nom: string;
  code: string;
  prix: number;
  stock: number;
  seuil_alerte: number;
  categorie: string;
  ordonnance_requise: boolean;
}

interface CartItem extends Medicament {
  quantity: number;
}

const medications = ref<Medicament[]>([])
const searchQuery = ref('')
const cart = ref<CartItem[]>([])
const prescriptionRequired = computed(() => cart.value.some(item => item.ordonnance_requise))

const prescription = ref({
  numero: '',
  medecin: '',
  date_ordonnance: new Date().toISOString().split('T')[0]
})

const loading = ref(false)
const showReceipt = ref(false)
const lastVente = ref<any>(null)
const lastTotal = ref(0)

onMounted(async () => {
  try {
    const res = await axios.get('/medicaments')
    medications.value = res.data
  } catch (err) {
    console.error('Error fetching medications', err)
  }
})

const filteredMedications = computed(() => {
  if (!searchQuery.value) return medications.value
  const q = searchQuery.value.toLowerCase()
  return medications.value.filter(m => 
    m.nom.toLowerCase().includes(q) || m.code.toLowerCase().includes(q)
  )
})

const addToCart = (med: Medicament) => {
  const existing = cart.value.find(item => item.id === med.id)
  if (existing) {
    if (existing.quantity < med.stock) existing.quantity++
  } else {
    cart.value.push({ ...med, quantity: 1 })
  }
}

const updateQuantity = (id: number, delta: number) => {
  const item = cart.value.find(i => i.id === id)
  if (item) {
    const newQty = item.quantity + delta
    if (newQty > 0 && newQty <= item.stock) {
      item.quantity = newQty
    } else if (newQty <= 0) {
      removeFromCart(id)
    }
  }
}

const removeFromCart = (id: number) => {
  cart.value = cart.value.filter(item => item.id !== id)
}

const total = computed(() => cart.value.reduce((acc, item) => acc + (item.prix * item.quantity), 0))

// TVA 20% par défaut (Madagascar)
const subTotal = computed(() => total.value / 1.2)
const vatAmount = computed(() => total.value - subTotal.value)

const processSale = async () => {
  if (cart.value.length === 0) return
  
  loading.value = true
  try {
    lastTotal.value = total.value
    const payload: any = {
      items: cart.value.map(item => ({
        medicament_id: item.id,
        quantite: item.quantity
      }))
    }

    if (prescriptionRequired.value) {
      payload.ordonnance = prescription.value
    }

    const res = await axios.post('/ventes', payload)
    lastVente.value = res.data
    showReceipt.value = true
    cart.value = []
    prescription.value = { numero: '', medecin: '', date_ordonnance: new Date().toISOString().split('T')[0] }
    
    // Refresh stock
    const medsRes = await axios.get('/medicaments')
    medications.value = medsRes.data
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la vente')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex gap-4 overflow-hidden">
    <!-- Main POS Area -->
    <div class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden relative">
      <!-- Search & Filters -->
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between gap-4">
        <div class="flex-1 relative group max-w-xl">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-emerald-500 transition-colors" />
          <input 
            v-model="searchQuery" 
            placeholder="Rechercher par nom ou code..." 
            class="w-full bg-white border border-slate-200 rounded px-10 py-1.5 text-xs font-semibold focus:border-emerald-500 outline-none transition-all shadow-sm"
          >
          <div v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer text-slate-300 hover:text-slate-500">
            <X class="w-3.5 h-3.5" />
          </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded text-[10px] font-bold uppercase tracking-wider border border-emerald-100 italic">
          <PackageSearch class="w-3.5 h-3.5" />
          {{ filteredMedications.length }} Produits
        </div>
      </div>

      <!-- Inventory Grid -->
      <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
          <div 
            v-for="med in filteredMedications" 
            :key="med.id" 
            @click="med.stock > 0 && addToCart(med)"
            :class="[
              'group relative flex flex-col p-4 bg-white border rounded-md transition-all cursor-pointer select-none',
              med.stock > 0 
                ? 'border-slate-200 hover:border-emerald-500 hover:shadow-md' 
                : 'border-slate-100 opacity-40 cursor-not-allowed grayscale'
            ]"
          >
            <!-- Badge Ordonnance -->
            <div v-if="med.ordonnance_requise" class="absolute top-2 right-2 p-1 bg-rose-50 text-rose-600 rounded" title="Ordonnance requise">
              <FileText class="w-3 h-3" />
            </div>

            <div class="flex items-start gap-3 mb-3">
              <div class="w-8 h-8 rounded bg-slate-50 flex items-center justify-center group-hover:bg-emerald-50 transition-colors border border-slate-100 group-hover:border-emerald-100">
                <Pill :class="[med.stock > 0 ? 'text-emerald-500' : 'text-slate-300', 'w-4 h-4']" />
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="text-[11px] font-bold text-slate-800 uppercase tracking-tight truncate">{{ med.nom }}</h4>
                <p class="text-[9px] text-slate-400 font-medium uppercase truncate">{{ med.categorie }}</p>
              </div>
            </div>

            <div class="mt-auto flex items-end justify-between gap-2">
              <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-900 font-mono tracking-tight">{{ formatCurrency(med.prix) }}</span>
                <div :class="['mt-1 text-[9px] font-bold px-1.5 py-0.5 rounded border italic', med.stock <= med.seuil_alerte ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-slate-50 text-slate-500 border-slate-100']">
                  {{ med.stock }} en stock
                </div>
              </div>
              <div class="w-7 h-7 rounded bg-emerald-600 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg shadow-emerald-600/20">
                <Plus class="w-4 h-4" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Checkout Sidebar -->
    <div class="w-96 flex flex-col bg-slate-900 border border-slate-800 rounded-md shadow-2xl overflow-hidden relative">
      <!-- Decor -->
      <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Cart Header -->
      <div class="px-4 py-3 border-b border-slate-800 bg-slate-800/40 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded bg-emerald-500/20 flex items-center justify-center text-emerald-400">
            <ShoppingCart class="w-4 h-4" />
          </div>
          <div>
            <span class="text-xs font-bold text-white uppercase tracking-wider">Panier Client</span>
          </div>
        </div>
        <button v-if="cart.length > 0" @click="cart = []" class="text-[9px] font-bold text-slate-500 hover:text-rose-400 uppercase tracking-widest transition-colors">
          Vider
        </button>
      </div>

      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar-dark relative z-10">
        <div v-if="cart.length === 0" class="flex flex-col items-center justify-center h-full opacity-20">
          <ShoppingCart class="w-12 h-12 mb-4 text-emerald-500" />
          <p class="text-[10px] font-bold text-white uppercase tracking-[0.2em] text-center italic">Sélectionnez des articles</p>
        </div>
        
        <div v-for="item in cart" :key="item.id" class="bg-slate-800/50 p-3 rounded border border-slate-700/50 group transition-all">
          <div class="flex items-start justify-between mb-2">
            <div class="min-w-0 flex-1">
              <h5 class="text-[11px] font-bold text-white uppercase tracking-tight truncate">{{ item.nom }}</h5>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-[9px] font-bold text-emerald-500/60 font-mono">{{ formatCurrency(item.prix) }}</span>
                <span v-if="item.ordonnance_requise" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
              </div>
            </div>
            <p class="text-[11px] font-bold text-white font-mono ml-2">{{ formatCurrency(item.prix * item.quantity) }}</p>
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 bg-slate-900 rounded-sm p-0.5 border border-slate-700">
              <button @click="updateQuantity(item.id, -1)" class="w-6 h-6 flex items-center justify-center rounded-sm bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 transition-colors">
                <Minus class="w-3.5 h-3.5" />
              </button>
              <span class="w-8 text-center text-xs font-bold font-mono text-white">{{ item.quantity }}</span>
              <button @click="updateQuantity(item.id, 1)" class="w-6 h-6 flex items-center justify-center rounded-sm bg-emerald-600 hover:bg-emerald-500 text-white transition-colors">
                <Plus class="w-3.5 h-3.5" />
              </button>
            </div>
            <button @click="removeFromCart(item.id)" class="p-1.5 text-slate-600 hover:text-rose-400 hover:bg-rose-500/10 rounded transition-all">
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Checkout Footer -->
      <div class="p-4 bg-slate-800/80 border-t border-slate-700 space-y-4">
        <!-- Prescription Required Alert -->
        <div v-if="prescriptionRequired" class="bg-rose-500/10 border border-rose-500/30 p-3 rounded space-y-3">
          <div class="flex items-center gap-2 text-rose-400">
            <AlertCircle class="w-4 h-4" />
            <span class="text-[9px] font-bold uppercase tracking-widest">Ordonnance Requise</span>
          </div>
          <div class="space-y-2">
            <div class="relative">
              <FileText class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500" />
              <input v-model="prescription.numero" placeholder="N° Ordonnance" class="w-full bg-slate-900 border border-slate-700 rounded pl-8 pr-2 py-1.5 text-[10px] font-bold text-white focus:border-rose-500 outline-none transition-all placeholder:text-slate-600">
            </div>
            <div class="relative">
              <User class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500" />
              <input v-model="prescription.medecin" placeholder="Nom du Médecin" class="w-full bg-slate-900 border border-slate-700 rounded pl-8 pr-2 py-1.5 text-[10px] font-bold text-white focus:border-rose-500 outline-none transition-all placeholder:text-slate-600">
            </div>
          </div>
        </div>

        <div class="space-y-2 text-[10px] font-bold uppercase tracking-widest italic">
          <div class="flex items-center justify-between text-slate-500">
            <span>Sou-total HT</span>
            <span class="font-mono text-slate-400">{{ formatCurrency(subTotal) }}</span>
          </div>
          <div class="flex items-center justify-between text-slate-500">
            <span>TVA (20%)</span>
            <span class="font-mono text-slate-400">{{ formatCurrency(vatAmount) }}</span>
          </div>
          <div class="pt-2 border-t border-slate-700 flex items-end justify-between">
            <div class="flex flex-col">
              <span class="text-[9px] font-bold text-emerald-500 mb-0.5">Total à Encaisser</span>
              <span class="text-3xl font-bold font-mono tracking-tighter text-white">{{ formatCurrency(total) }}</span>
            </div>
            <div class="flex items-center gap-2 opacity-50">
              <CreditCard class="w-4 h-4 text-slate-400" />
              <Banknote class="w-4 h-4 text-emerald-400" />
            </div>
          </div>
        </div>

        <button 
          @click="processSale"
          :disabled="cart.length === 0 || loading || (prescriptionRequired && (!prescription.numero || !prescription.medecin))"
          class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:text-slate-600 rounded-sm font-bold text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-600/10 active:scale-95 flex items-center justify-center gap-3 text-white"
        >
          <Receipt v-if="!loading" class="w-4 h-4" />
          <Loader2 v-else class="w-4 h-4 animate-spin" />
          {{ loading ? 'En cours...' : 'Valider & Encaisser' }}
        </button>
      </div>
    </div>

    <!-- Receipt Preview Modal -->
    <Teleport to="body">
      <div v-if="showReceipt" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        <div class="bg-white w-full max-w-md rounded-md overflow-hidden relative shadow-2xl animate-in zoom-in-95 duration-300">
          <div class="p-8 text-center border-b border-slate-100 relative">
            <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle class="w-10 h-10 text-emerald-500" />
            </div>
            <h2 class="text-xl font-bold text-slate-900 uppercase tracking-tight">Transaction Terminée</h2>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 italic">Ticket N° {{ lastVente?.id }} • Madagascar</p>
            <button @click="showReceipt = false" class="absolute top-4 right-4 text-slate-300 hover:text-slate-950">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="p-6 bg-slate-50/50 space-y-4">
            <div class="flex justify-between items-center text-[11px] font-bold uppercase tracking-wider text-slate-500 italic">
              <span>Opérateur</span>
              <span class="text-slate-800">{{ auth.user?.name }}</span>
            </div>
            <div class="p-6 bg-white border border-slate-200 rounded text-center">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Montant Total</span>
              <p class="text-4xl font-bold font-mono tracking-tighter text-emerald-600 mt-1">
                {{ formatCurrency(lastTotal) }}
              </p>
            </div>
          </div>

          <div class="p-6 grid grid-cols-2 gap-4 bg-white">
            <button @click="showReceipt = false" class="py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded text-[10px] font-bold uppercase tracking-widest transition-colors">
              Fermer
            </button>
            <button @click="showReceipt = false" class="py-2.5 px-4 bg-emerald-600 text-white rounded text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-emerald-700 shadow-md shadow-emerald-600/10">
              <Receipt class="w-4 h-4" />
              Imprimer Reçu
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.custom-scrollbar-dark::-webkit-scrollbar { width: 3px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>
