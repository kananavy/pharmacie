<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '../api/axios'
import { 
  Users, 
  UserPlus, 
  Trash2, 
  Edit2,  
  X,
  Loader2,
  Check
} from 'lucide-vue-next'

interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'vendeur' | 'caissier'
  created_at: string
}

const users = ref<User[]>([])
const loading = ref(true)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Forms
const newUser = ref({ name: '', email: '', password: '', role: 'caissier' as const })
const editForm = ref({ id: 0, name: '', email: '', password: '', role: 'caissier' as 'admin' | 'vendeur' | 'caissier' })

const fetchUsers = async () => {
  loading.value = true
  try {
    const res = await axios.get('/users')
    users.value = res.data
  } catch (err: any) {
    errorMessage.value = "Impossible de charger les utilisateurs."
  } finally {
    loading.value = false
  }
}

const createUser = async () => {
  submitting.value = true
  errorMessage.value = null
  try {
    await axios.post('/users', newUser.value)
    successMessage.value = "Utilisateur créé avec succès."
    showCreateModal.value = false
    newUser.value = { name: '', email: '', password: '', role: 'caissier' }
    await fetchUsers()
    setTimeout(() => successMessage.value = null, 3000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || "Erreur lors de la création."
  } finally {
    submitting.value = false
  }
}

const openEdit = (user: User) => {
  editForm.value = { 
    id: user.id, 
    name: user.name, 
    email: user.email, 
    password: '', // blank unless changing
    role: user.role as 'admin' | 'vendeur' | 'caissier'
  }
  showEditModal.value = true
}

const updateUser = async () => {
  submitting.value = true
  errorMessage.value = null
  try {
    const payload: any = { 
      name: editForm.value.name,
      role: editForm.value.role 
    }
    if (editForm.value.password) {
      payload.password = editForm.value.password
    }

    await axios.put(`/users/${editForm.value.id}`, payload)
    successMessage.value = "Utilisateur mis à jour."
    showEditModal.value = false
    await fetchUsers()
    setTimeout(() => successMessage.value = null, 3000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || "Erreur lors de la mise à jour."
  } finally {
    submitting.value = false
  }
}

const deleteUser = async (user: User) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) return
  try {
    await axios.delete(`/users/${user.id}`)
    successMessage.value = "Utilisateur supprimé."
    await fetchUsers()
    setTimeout(() => successMessage.value = null, 3000)
  } catch (err: any) {
    alert(err.response?.data?.message || "Erreur lors de la suppression.")
  }
}

const getRoleBadgeClass = (role: string) => {
  switch (role) {
    case 'admin': return 'bg-purple-100 text-purple-700 border-purple-200'
    case 'vendeur': return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'caissier': return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    default: return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center">
          <Users class="w-6 h-6" />
        </div>
        <div>
           <h1 class="text-2xl font-bold text-slate-900">Gestion Utilisateurs</h1>
           <p class="text-sm text-slate-500">Administrez les comptes et les rôles</p>
        </div>
      </div>
      <button 
        @click="showCreateModal = true"
        class="px-4 py-2 bg-slate-900 text-white rounded-lg font-medium hover:bg-slate-800 flex items-center gap-2 transition-colors"
      >
        <UserPlus class="w-4 h-4" />
        Nouvel Utilisateur
      </button>
    </div>

    <!-- Messages -->
    <div v-if="successMessage" class="p-4 bg-emerald-50 text-emerald-700 rounded-lg flex items-center gap-2 border border-emerald-100">
       <Check class="w-5 h-5" /> {{ successMessage }}
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
               <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nom</th>
               <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Email</th>
               <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rôle</th>
               <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date Création</th>
               <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
             <tr v-if="loading">
                <td colspan="5" class="py-8 text-center text-slate-400">
                   <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2" />
                   Chargement...
                </td>
             </tr>
             <template v-else>
               <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors">
                  <td class="px-6 py-4 font-medium text-slate-900 flex items-center gap-3">
                     <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">
                        {{ user.name.charAt(0).toUpperCase() }}
                     </div>
                     {{ user.name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ user.email }}</td>
                  <td class="px-6 py-4">
                     <span :class="['px-2 py-1 rounded-md text-xs font-bold uppercase border', getRoleBadgeClass(user.role)]">
                        {{ user.role }}
                     </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-500">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                  <td class="px-6 py-4 text-right space-x-2">
                     <button @click="openEdit(user)" class="p-1 text-slate-400 hover:text-indigo-600 transition-colors">
                        <Edit2 class="w-4 h-4" />
                     </button>
                     <button @click="deleteUser(user)" class="p-1 text-slate-400 hover:text-rose-600 transition-colors">
                        <Trash2 class="w-4 h-4" />
                     </button>
                  </td>
               </tr>
               <tr v-if="users.length === 0" >
                  <td colspan="5" class="py-8 text-center text-slate-400">Aucun utilisateur trouvé</td>
               </tr>
             </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95">
         <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Nouvel Utilisateur</h3>
            <button @click="showCreateModal = false" class="text-slate-400 hover:text-rose-500"><X class="w-5 h-5"/></button>
         </div>
         <form @submit.prevent="createUser" class="p-6 space-y-4">
            <div v-if="errorMessage" class="p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">{{ errorMessage }}</div>
            
            <div>
               <label class="block text-sm font-medium text-slate-700 mb-1">Nom complet</label>
               <input v-model="newUser.name" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <div>
               <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
               <input v-model="newUser.email" type="email" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
               <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                  <input v-model="newUser.password" type="password" required minlength="8" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
               </div>
               <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Rôle</label>
                  <select v-model="newUser.role" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                     <option value="admin">Admin</option>
                     <option value="vendeur">Vendeur</option>
                     <option value="caissier">Caissier</option>
                  </select>
               </div>
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
               <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Annuler</button>
               <button type="submit" :disabled="submitting" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50">
                  {{ submitting ? 'Création...' : 'Créer le compte' }}
               </button>
            </div>
         </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95">
         <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Modifier Utilisateur</h3>
            <button @click="showEditModal = false" class="text-slate-400 hover:text-rose-500"><X class="w-5 h-5"/></button>
         </div>
         <form @submit.prevent="updateUser" class="p-6 space-y-4">
            <div v-if="errorMessage" class="p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">{{ errorMessage }}</div>
            
            <div>
               <label class="block text-sm font-medium text-slate-700 mb-1">Nom complet</label>
               <input v-model="editForm.name" type="text" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <!-- Email Readonly often safer/easier -->
            <div>
               <label class="block text-sm font-medium text-slate-700 mb-1">Email (Lecture seule)</label>
               <input v-model="editForm.email" type="email" readonly class="w-full px-3 py-2 border rounded-lg bg-slate-100 text-slate-500 cursor-not-allowed">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
               <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Nouveau mot de passe</label>
                  <input v-model="editForm.password" type="password" placeholder="Laisser vide si inchangé" minlength="8" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
               </div>
               <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Rôle</label>
                  <select v-model="editForm.role" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                     <option value="admin">Admin</option>
                     <option value="vendeur">Vendeur</option>
                     <option value="caissier">Caissier</option>
                  </select>
               </div>
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
               <button type="button" @click="showEditModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Annuler</button>
               <button type="submit" :disabled="submitting" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50">
                  {{ submitting ? 'Enregistrement...' : 'Mettre à jour' }}
               </button>
            </div>
         </form>
      </div>
    </div>

  </div>
</template>
