<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from '../api/axios'
import {
  History,
  User,
  Zap,
  Tag,
  Clock,
  Search,
  ChevronLeft,
  ChevronRight,
  Eye,
  ArrowUp, ArrowDown
} from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth' // To filter users for dropdown

interface User {
  id: number;
  name: string;
}

interface Auditable {
    id: number;
    nom?: string; // For Medicament
    numero_lot?: string; // For Lot
    key?: string; // For Setting
    // Add other fields from auditable models as needed for display
}

interface AuditLog {
  id: number;
  user_id?: number;
  user?: User;
  event: string;
  auditable_type?: string;
  auditable_id?: number;
  auditable?: Auditable;
  old_values?: Record<string, any>;
  new_values?: Record<string, any>;
  url?: string;
  ip_address?: string;
  user_agent?: string;
  created_at: string;
}

const authStore = useAuthStore()

const auditLogs = ref<AuditLog[]>([])
const users = ref<User[]>([])
const loading = ref(true)
const currentPage = ref(1)
const lastPage = ref(1)
const totalLogs = ref(0)
const perPage = ref(20)

// Filters
const searchFilter = ref('')
const selectedUserId = ref('')
const selectedEvent = ref('')
const selectedAuditableType = ref('')
const auditableTypes = ref<string[]>([]) // Dynamic list from fetched logs or hardcoded

const showDetailsModal = ref(false)
const selectedLog = ref<AuditLog | null>(null)

const events = [
    'created', 'updated', 'deleted', 'login', 'logout', 'failed_login',
    'reception', 'vente', 'annulation', 'retour', 'ajustement', 'cloture'
]; // Add more as your app grows

const displayAuditableType = (type: string | undefined) => {
    if (!type) return 'N/A';
    const parts = type.split('\\');
    return parts[parts.length - 1]; // Get just the class name
};


onMounted(async () => {
  await Promise.all([fetchAuditLogs(), fetchUsers()])
})

const fetchAuditLogs = async (page: number = 1) => {
  loading.value = true
  try {
    const params: Record<string, any> = { page: page, per_page: perPage.value };
    if (searchFilter.value) params.search = searchFilter.value;
    if (selectedUserId.value) params.user_id = selectedUserId.value;
    if (selectedEvent.value) params.event = selectedEvent.value;
    if (selectedAuditableType.value) params.auditable_type = 'App\\Models\\' + selectedAuditableType.value;

    const res = await axios.get('/audit-logs', { params })
    auditLogs.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
    totalLogs.value = res.data.total

    // Collect auditable types dynamically
    const types = new Set<string>();
    auditLogs.value.forEach(log => {
        if (log.auditable_type) types.add(displayAuditableType(log.auditable_type));
    });
    auditableTypes.value = Array.from(types).sort();

  } catch (err) {
    console.error('Erreur lors de la récupération des logs d\'audit:', err)
    alert('Erreur lors de la récupération des logs d\'audit.')
  } finally {
    loading.value = false
  }
}

const fetchUsers = async () => {
    try {
        const res = await axios.get('/users')
        users.value = res.data.data // Assuming pagination on users endpoint
    } catch (err) {
        console.error('Erreur lors de la récupération des utilisateurs:', err)
    }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchAuditLogs(page)
  }
}

const applyFilters = () => {
  fetchAuditLogs(1)
}

const resetFilters = () => {
  searchFilter.value = ''
  selectedUserId.value = ''
  selectedEvent.value = ''
  selectedAuditableType.value = ''
  fetchAuditLogs(1)
}

const openLogDetails = (log: AuditLog) => {
    selectedLog.value = log;
    showDetailsModal.value = true;
};

// Helper for formatted JSON display
const formatJson = (json: Record<string, any> | undefined) => {
    if (!json) return '{}';
    return JSON.stringify(json, null, 2);
}

</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-purple-100 text-purple-700 flex items-center justify-center">
          <History class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Journal d'Audit</h1>
          <p class="text-xs text-slate-500">
            Historique détaillé des actions utilisateurs et système.
          </p>
        </div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Recherche (IP, URL, user)</label>
                <input v-model="searchFilter" type="text" placeholder="Rechercher..." class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
            </div>
            <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Utilisateur</label>
                <select v-model="selectedUserId" class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                    <option value="">Tous</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Événement</label>
                <select v-model="selectedEvent" class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                    <option value="">Tous</option>
                    <option v-for="eventOption in events" :key="eventOption" :value="eventOption">{{ eventOption }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Type d'élément</label>
                <select v-model="selectedAuditableType" class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                    <option value="">Tous</option>
                    <option v-for="type in auditableTypes" :key="type" :value="type">{{ type }}</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button @click="resetFilters" class="px-3 py-2 text-[11px] text-slate-500 hover:text-slate-700">
                Réinitialiser les filtres
            </button>
            <button @click="applyFilters" class="inline-flex items-center gap-2 px-3.5 py-2 rounded bg-emerald-600 text-white text-[11px] font-semibold hover:bg-emerald-700">
                <Search class="w-3.5 h-3.5"/>
                Appliquer les filtres
            </button>
        </div>
    </div>


    <!-- Tableau des logs -->
    <section class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden">
      <div class="flex-1 overflow-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
            <tr>
              <th class="px-3 py-2 font-medium text-left">Date</th>
              <th class="px-3 py-2 font-medium text-left">Utilisateur</th>
              <th class="px-3 py-2 font-medium text-left">Événement</th>
              <th class="px-3 py-2 font-medium text-left">Élément</th>
              <th class="px-3 py-2 font-medium text-left">IP</th>
              <th class="px-3 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" v-for="i in 6" :key="i" class="animate-pulse">
              <td colspan="6" class="px-3 py-3">
                <div class="h-6 bg-slate-100 rounded"></div>
              </td>
            </tr>
            <tr v-else v-for="log in auditLogs" :key="log.id" class="border-b border-slate-100 hover:bg-slate-50">
              <td class="px-3 py-2 text-slate-600">{{ new Date(log.created_at).toLocaleString() }}</td>
              <td class="px-3 py-2 text-slate-800">{{ log.user?.name || 'Système' }}</td>
              <td class="px-3 py-2 text-slate-800 font-semibold">{{ log.event }}</td>
              <td class="px-3 py-2 text-slate-600">
                <span v-if="log.auditable">
                    {{ displayAuditableType(log.auditable_type) }} #{{ log.auditable_id }}
                    <span v-if="log.auditable.nom"> ({{ log.auditable.nom }})</span>
                    <span v-else-if="log.auditable.numero_lot"> ({{ log.auditable.numero_lot }})</span>
                    <span v-else-if="log.auditable.key"> ({{ log.auditable.key }})</span>
                </span>
                <span v-else>N/A</span>
              </td>
              <td class="px-3 py-2 text-slate-600 font-mono">{{ log.ip_address }}</td>
              <td class="px-3 py-2 text-right">
                <button
                  @click="openLogDetails(log)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-slate-700 border border-slate-300 rounded hover:bg-slate-50"
                >
                  <Eye class="w-3.5 h-3.5" />
                  Détails
                </button>
              </td>
            </tr>
            <tr v-if="!loading && auditLogs.length === 0">
              <td colspan="6" class="px-3 py-6 text-center text-[11px] text-slate-400">
                Aucun log d'audit trouvé.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex items-center justify-between p-4 border-t border-slate-200 bg-slate-50/70 text-xs">
        <span class="text-slate-600">Affichage de {{ (currentPage - 1) * perPage + 1 }} à {{ Math.min(currentPage * perPage, totalLogs) }} sur {{ totalLogs }}</span>
        <div class="flex items-center gap-2">
          <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="p-2 rounded bg-white border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-50">
            <ChevronLeft class="w-3.5 h-3.5" />
          </button>
          <span class="font-semibold text-slate-700">Page {{ currentPage }} / {{ lastPage }}</span>
          <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage" class="p-2 rounded bg-white border border-slate-300 text-slate-600 hover:bg-slate-100 disabled:opacity-50">
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </section>

    <!-- Log Details Modal -->
    <Teleport to="body">
      <div v-if="showDetailsModal && selectedLog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg border border-slate-200 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50 text-xs">
            <h2 class="text-sm font-semibold text-slate-900">Détails Log #{{ selectedLog.id }}</h2>
            <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="px-5 py-4 space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-2 text-slate-600">
              <p><strong>Date:</strong> {{ new Date(selectedLog.created_at).toLocaleString() }}</p>
              <p><strong>Utilisateur:</strong> {{ selectedLog.user?.name || 'Système' }}</p>
              <p><strong>Événement:</strong> <span class="font-semibold text-slate-800">{{ selectedLog.event }}</span></p>
              <p v-if="selectedLog.auditable_type"><strong>Élément audité:</strong> {{ displayAuditableType(selectedLog.auditable_type) }} #{{ selectedLog.auditable_id }}</p>
              <p v-if="selectedLog.ip_address"><strong>Adresse IP:</strong> {{ selectedLog.ip_address }}</p>
              <p v-if="selectedLog.url"><strong>URL:</strong> {{ selectedLog.url }}</p>
            </div>

            <div v-if="selectedLog.old_values || selectedLog.new_values" class="grid grid-cols-2 gap-4 border-t border-slate-200 pt-3">
                <div>
                    <h3 class="font-semibold text-slate-800 mb-2">Anciennes Valeurs:</h3>
                    <pre class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto text-[10px] text-slate-700">{{ formatJson(selectedLog.old_values) }}</pre>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 mb-2">Nouvelles Valeurs:</h3>
                    <pre class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto text-[10px] text-slate-700">{{ formatJson(selectedLog.new_values) }}</pre>
                </div>
            </div>
            <div v-if="selectedLog.user_agent" class="border-t border-slate-200 pt-3">
              <h3 class="font-semibold text-slate-800 mb-2">User Agent:</h3>
              <p class="bg-slate-50 p-2 rounded border border-slate-200 overflow-x-auto text-[10px] text-slate-700">{{ selectedLog.user_agent }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
