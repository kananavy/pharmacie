<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '../api/axios'
import { 
  ShoppingCart, 
  Plus, 
  Search, 
  Trash2,
  Printer,
  X,
  Check,
  AlertCircle
} from 'lucide-vue-next'

interface Medicament {
  id: number
  nom: string
  code: string
  prix: number
  stock: number
  ordonnance_requise: boolean
}

interface CartItem {
  medicament: Medicament
  quantite: number
}

const medicaments = ref<Medicament[]>([])
const searchQuery = ref('')
const cart = ref<CartItem[]>([])
const loading = ref(false)
const showSuccess = ref(false)
const lastTicket = ref<any>(null)

onMounted(async () => {
  await fetchMedicaments()
})

const fetchMedicaments = async () => {
  try {
    const res = await axios.get('/medicaments')
    medicaments.value = res.data
  } catch (err) {
    console.error('Erreur chargement médicaments:', err)
  }
}

const filteredMedicaments = computed(() => {
  if (!searchQuery.value) return medicaments.value
  const q = searchQuery.value.toLowerCase()
  return medicaments.value.filter(m => 
    m.nom.toLowerCase().includes(q) || 
    m.code.toLowerCase().includes(q)
  )
})

const cartTotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.medicament.prix * item.quantite), 0)
})

const addToCart = (medicament: Medicament) => {
  const existing = cart.value.find(item => item.medicament.id === medicament.id)
  if (existing) {
    if (existing.quantite < medicament.stock) {
      existing.quantite++
    }
  } else {
    cart.value.push({ medicament, quantite: 1 })
  }
}

const removeFromCart = (index: number) => {
  cart.value.splice(index, 1)
}

const updateQuantity = (index: number, delta: number) => {
  const item = cart.value[index]
  const newQty = item.quantite + delta
  if (newQty > 0 && newQty <= item.medicament.stock) {
    item.quantite = newQty
  }
}

const generateTicket = async () => {
  if (cart.value.length === 0) {
    alert('Le panier est vide')
    return
  }

  loading.value = true
  try {
    const items = cart.value.map(item => ({
      medicament_id: item.medicament.id,
      quantite: item.quantite
    }))

    const res = await axios.post('/commandes', { items })
    lastTicket.value = res.data
    showSuccess.value = true
    cart.value = []
    
    // Auto-hide success message after 5s
    setTimeout(() => {
      showSuccess.value = false
    }, 5000)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la création de la commande')
  } finally {
    loading.value = false
  }
}

const printTicket = () => {
  window.print()
}
</script>

<template>
  <div class="space-y-6 h-[calc(100vh-140px)] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <ShoppingCart class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Créer une Commande</h1>
          <p class="text-xs text-slate-500">Sélectionnez les médicaments et générez un ticket</p>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccess" class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-start gap-3">
      <Check class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
      <div class="flex-1">
        <p class="text-sm font-semibold text-emerald-900">Commande créée avec succès!</p>
        <p class="text-xs text-emerald-700 mt-1">
          Ticket N° <span class="font-mono font-bold">{{ lastTicket?.numero_ticket }}</span> - 
          Total: <span class="font-bold">{{ lastTicket?.total }} Ar</span>
        </p>
      </div>
      <button @click="printTicket" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700 flex items-center gap-1.5">
        <Printer class="w-3.5 h-3.5" />
        Imprimer
      </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 grid grid-cols-3 gap-6 min-h-0">
      <!-- Products List -->
      <div class="col-span-2 flex flex-col bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="p-4 border-b border-slate-200">
          <div class="flex items-center gap-3 bg-slate-50 px-4 py-2.5 rounded-lg border border-slate-200">
            <Search class="w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un médicament..."
              class="bg-transparent border-none outline-none w-full text-sm text-slate-700"
            />
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-2">
          <div
            v-for="med in filteredMedicaments"
            :key="med.id"
            @click="addToCart(med)"
            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:bg-emerald-50 hover:border-emerald-300 cursor-pointer transition-all group"
          >
            <div class="flex-1">
              <p class="text-sm font-semibold text-slate-900 group-hover:text-emerald-700">{{ med.nom }}</p>
              <p class="text-xs text-slate-500 font-mono">{{ med.code }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-slate-900">{{ med.prix }} Ar</p>
              <p class="text-xs" :class="med.stock > 0 ? 'text-emerald-600' : 'text-rose-600'">
                Stock: {{ med.stock }}
              </p>
            </div>
            <Plus class="w-5 h-5 text-emerald-600 ml-3 opacity-0 group-hover:opacity-100 transition-opacity" />
          </div>

          <div v-if="filteredMedicaments.length === 0" class="text-center py-12 text-slate-400 text-sm">
            Aucun médicament trouvé
          </div>
        </div>
      </div>

      <!-- Cart -->
      <div class="flex flex-col bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50">
          <h2 class="text-sm font-semibold text-slate-900">Panier ({{ cart.length }})</h2>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-for="(item, index) in cart" :key="index" class="border border-slate-200 rounded-lg p-3">
            <div class="flex items-start justify-between mb-2">
              <div class="flex-1">
                <p class="text-sm font-semibold text-slate-900">{{ item.medicament.nom }}</p>
                <p class="text-xs text-slate-500">{{ item.medicament.prix }} Ar × {{ item.quantite }}</p>
              </div>
              <button @click="removeFromCart(index)" class="text-rose-500 hover:text-rose-700">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="updateQuantity(index, -1)"
                class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold"
              >
                -
              </button>
              <input
                :value="item.quantite"
                readonly
                class="w-12 h-7 text-center border border-slate-200 rounded text-sm font-semibold"
              />
              <button
                @click="updateQuantity(index, 1)"
                class="w-7 h-7 rounded bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 font-bold"
              >
                +
              </button>
              <div class="ml-auto text-sm font-bold text-slate-900">
                {{ item.medicament.prix * item.quantite }} Ar
              </div>
            </div>
          </div>

          <div v-if="cart.length === 0" class="text-center py-12 text-slate-400 text-sm">
            <ShoppingCart class="w-12 h-12 mx-auto mb-2 opacity-30" />
            Panier vide
          </div>
        </div>

        <div class="p-4 border-t border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-600">Total</span>
            <span class="text-xl font-bold text-slate-900">{{ cartTotal }} Ar</span>
          </div>

          <button
            @click="generateTicket"
            :disabled="cart.length === 0 || loading"
            class="w-full py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg font-semibold text-sm hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-500/30"
          >
            {{ loading ? 'Génération...' : 'Générer Ticket' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Print Template (hidden) -->
    <div v-if="lastTicket" class="hidden print:block print:p-8">
      <div class="max-w-sm mx-auto border-2 border-slate-300 p-6 font-mono text-sm">
        <div class="text-center mb-4 border-b-2 border-dashed border-slate-300 pb-4">
          <h1 class="text-xl font-bold">PHARMACIE PRO</h1>
          <p class="text-xs">Ticket de Commande</p>
        </div>

        <div class="space-y-1 mb-4 text-xs">
          <p><strong>N° Ticket:</strong> {{ lastTicket.numero_ticket }}</p>
          <p><strong>Date:</strong> {{ new Date(lastTicket.created_at).toLocaleString('fr-FR') }}</p>
          <p><strong>Vendeur:</strong> {{ lastTicket.vendeur?.name }}</p>
        </div>

        <div class="border-t border-b border-slate-300 py-3 mb-3">
          <div v-for="detail in lastTicket.details" :key="detail.id" class="flex justify-between text-xs mb-1">
            <span>{{ detail.medicament.nom }} x{{ detail.quantite }}</span>
            <span>{{ detail.prix_unitaire * detail.quantite }} Ar</span>
          </div>
        </div>

        <div class="text-right mb-4">
          <p class="text-lg font-bold">TOTAL: {{ lastTicket.total }} Ar</p>
        </div>

        <div class="text-center text-xs border-t-2 border-dashed border-slate-300 pt-4">
          <p>Présentez ce ticket à la caisse</p>
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
