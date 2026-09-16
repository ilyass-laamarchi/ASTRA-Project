<!-- Booking calendar that combines public availability checks with live Reverb refreshes. -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon, Clock, AlertCircle, Lock, Check } from 'lucide-vue-next'
import api from '../lib/api'
import { fromIsoDate, toIsoDate } from '../lib/dates'
import { CLIENT_RESERVATION_ENDPOINT, buildReservationPayload } from '../lib/reservations'
import { useAvailabilitySync } from '../composables/useAvailabilitySync'

const props = defineProps({
  carId: { type: [Number, String], required: true },
  dailyPrice: { type: [Number, String], required: true }
})

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

// State
const reservations = ref([])
const loading = ref(true)
const error = ref(null)
const availabilityStatus = ref(null) // null | 'available' | 'unavailable'
const realtimeUpdated = ref(false)

const requestedStartDate = fromIsoDate(String(route.query.start_date || ''))
const currentDate = ref(requestedStartDate || new Date()) // Month currently being viewed
const startDate = ref(null)
const endDate = ref(null)

// Format date as YYYY-MM-DD
/** Converts a calendar cell to the API's YYYY-MM-DD format. */
function toIso(date) {
  return toIsoDate(date)
}

// Load unavailable periods for the calendar
/** Loads safe blocking periods for the visible calendar window. */
async function fetchAvailability() {
  try {
    const today = new Date()
    const futureEnd = new Date(today)
    futureEnd.setMonth(futureEnd.getMonth() + 12)
    const { data } = await api.get(`/cars/${props.carId}/unavailable-periods`, {
      params: { from: toIso(today), to: toIso(futureEnd) }
    })
    reservations.value = data?.data || data || []
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Chargement des indisponibilités impossible.'
  } finally {
    loading.value = false
  }
}

// Check date-specific availability when URL has start_date/end_date
/** Revalidates date query parameters before showing a price quote. */
async function checkUrlAvailability() {
  const urlStart = route.query.start_date
  const urlEnd = route.query.end_date
  if (!urlStart || !urlEnd) return
  try {
    const { data } = await api.get(`/cars/${props.carId}/availability`, {
      params: { start_date: urlStart, end_date: urlEnd }
    })
    availabilityStatus.value = data.available ? 'available' : 'unavailable'
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Vérification de la disponibilité impossible.'
  }
}

// Real-time refresh wrapper: fetch + show notification
/** Refreshes blocked dates after another reservation changes through Reverb. */
async function onRealtimeRefresh() {
  await fetchAvailability()
  await checkUrlAvailability()
  realtimeUpdated.value = true
  setTimeout(() => { realtimeUpdated.value = false }, 10000)
}

onMounted(async () => {
  await fetchAvailability()
  await checkUrlAvailability()
})

useAvailabilitySync({
  carId: computed(() => props.carId),
  refresh: onRealtimeRefresh
})

// Calendar Logic
const weekDays = ['L', 'M', 'M', 'J', 'V', 'S', 'D']
const currentMonthName = computed(() => {
  return currentDate.value.toLocaleString('fr-FR', { month: 'long', year: 'numeric' })
})

const daysInMonth = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const days = []
  
  const firstDay = new Date(year, month, 1).getDay()
  const offset = firstDay === 0 ? 6 : firstDay - 1 // Make Monday = 0
  
  const totalDays = new Date(year, month + 1, 0).getDate()
  
  for (let i = 0; i < offset; i++) {
    days.push({ empty: true })
  }
  
  for (let i = 1; i <= totalDays; i++) {
    const date = new Date(year, month, i)
    // Strip time for comparison
    date.setHours(0,0,0,0)
    
    days.push({
      empty: false,
      date,
      dayNumber: i,
      dateIso: toIso(date),
      isDisabled: isDateDisabled(date)
    })
  }
  return days
})

/** Moves the calendar to the previous allowed month. */
function prevMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}
/** Moves the calendar to the following month. */
function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

/** Returns whether a day is past, blocked, or otherwise not selectable. */
function isDateDisabled(date) {
  const today = new Date()
  today.setHours(0,0,0,0)
  if (date < today) return true
  
  const dStr = toIso(date)
  return reservations.value.some(r => {
    return dStr >= r.start_date.split('T')[0] && dStr < r.end_date.split('T')[0]
  })
}

/** Applies the first or second boundary of a half-open booking range. */
function onDateClick(day) {
  if (day.empty || day.isDisabled) return
  
  if (!startDate.value || (startDate.value && endDate.value)) {
    startDate.value = day.date
    endDate.value = null
  } else if (day.date < startDate.value) {
    startDate.value = day.date
  } else {
    // Check if range contains disabled dates
    let current = new Date(startDate.value)
    let valid = true
    while (current < day.date) {
      if (isDateDisabled(current)) {
        valid = false
        break
      }
      current.setDate(current.getDate() + 1)
    }
    if (valid) {
      endDate.value = day.date
    } else {
      startDate.value = day.date
    }
  }
}

/** Returns whether a day is one of the selected range boundaries. */
function isSelected(day) {
  if (day.empty) return false
  if (startDate.value && day.date.getTime() === startDate.value.getTime()) return true
  if (endDate.value && day.date.getTime() === endDate.value.getTime()) return true
  return false
}

/** Returns whether a day lies inside the selected half-open range. */
function isInRange(day) {
  if (day.empty || !startDate.value || !endDate.value) return false
  return day.date > startDate.value && day.date < endDate.value
}

// Summary Logic
const daysCount = computed(() => {
  if (!startDate.value || !endDate.value) return 0
  const diffTime = Math.abs(endDate.value - startDate.value)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})
const totalPrice = computed(() => daysCount.value * props.dailyPrice)

// Booking Action
const bookingLoading = ref(false)
/** Creates an owned pending reservation or redirects visitors to login. */
async function confirmBooking() {
  if (!auth.isAuthenticated) {
    router.push({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  
  bookingLoading.value = true
  error.value = null
  
  try {
    const payload = buildReservationPayload({
      carId: props.carId,
      startDate: toIso(startDate.value),
      endDate: toIso(endDate.value)
    })
    const { data } = await api.post(CLIENT_RESERVATION_ENDPOINT, payload)
    
    if (data.session_url) {
      window.location.href = data.session_url
    } else {
      router.push('/client/dashboard')
    }
  } catch (err) {
    if (err.response?.status === 401) {
      router.push({ path: '/login', query: { redirect: route.fullPath } })
      return
    }
    error.value = err.response?.data?.message || 'Erreur lors de la réservation.'
  } finally {
    bookingLoading.value = false
  }
}
</script>

<template>
  <div class="bg-white flex flex-col h-full font-sans">
    
    <!-- Availability Status Banner -->
    <div v-if="availabilityStatus === 'available'" class="px-6 py-3 bg-emerald-50 border-b border-emerald-100 flex items-center gap-2">
      <Check :size="16" class="text-emerald-600" />
      <span class="text-sm font-bold text-emerald-700">Disponible pour ces dates</span>
    </div>
    <div v-else-if="availabilityStatus === 'unavailable'" class="px-6 py-3 bg-red-50 border-b border-red-100 flex items-center gap-2">
      <AlertCircle :size="16" class="text-red-600" />
      <span class="text-sm font-bold text-red-700">Non disponible pour ces dates</span>
    </div>

    <!-- Real-time update notification -->
    <div v-if="realtimeUpdated" class="px-6 py-2 bg-blue-50 border-b border-blue-100 flex items-center gap-2">
      <Clock :size="14" class="text-blue-600" />
      <span class="text-xs font-bold text-blue-700">Calendrier actualisé en temps réel</span>
    </div>

    <!-- Top Progress / Title -->
    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
      <div class="flex items-center gap-3 text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">
        <span class="text-gray-900">1. Dates</span>
        <ChevronRight :size="12" />
        <span>2. Paiement</span>
      </div>
      <h3 class="text-2xl font-black tracking-tight text-gray-900">Sélectionnez vos dates</h3>
    </div>

    <!-- Calendar Area -->
    <div class="p-6 pb-2">
      <div class="flex items-center justify-between mb-6">
        <button @click="prevMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-900 transition-colors">
          <ChevronLeft :size="20"/>
        </button>
        <span class="text-sm font-black uppercase tracking-wider text-gray-900 capitalize">{{ currentMonthName }}</span>
        <button @click="nextMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-900 transition-colors">
          <ChevronRight :size="20"/>
        </button>
      </div>

      <!-- Weekdays -->
      <div class="grid grid-cols-7 gap-1 mb-2">
        <div v-for="day in weekDays" :key="day" class="text-center text-[10px] font-black text-gray-400 uppercase tracking-widest py-2">
          {{ day }}
        </div>
      </div>

      <!-- Days Grid -->
      <div class="grid grid-cols-7 gap-y-2 gap-x-1">
        <div v-for="(day, i) in daysInMonth" :key="i" class="h-12 relative flex items-center justify-center">
          
          <template v-if="!day.empty">
            <!-- Range Background -->
            <div v-if="isInRange(day)" class="absolute inset-y-0 -inset-x-1 bg-gray-100 z-0"></div>
            
            <!-- Start/End Background Extensions -->
            <div v-if="startDate && endDate && day.date.getTime() === startDate.getTime()" class="absolute inset-y-0 right-[-4px] w-1/2 bg-gray-100 z-0"></div>
            <div v-if="startDate && endDate && day.date.getTime() === endDate.getTime()" class="absolute inset-y-0 left-[-4px] w-1/2 bg-gray-100 z-0"></div>

            <button @click="onDateClick(day)" 
                    :disabled="day.isDisabled"
                    :data-date="day.dateIso"
                    :class="[
                      'relative z-10 w-10 h-10 flex items-center justify-center rounded-full text-sm font-bold transition-all',
                      isSelected(day) ? 'bg-black text-white shadow-md scale-105' : 
                      day.isDisabled ? 'text-gray-300 cursor-not-allowed line-through decoration-gray-300' : 
                      'text-gray-900 hover:bg-gray-200'
                    ]">
              {{ day.dayNumber }}
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Booking Summary Panel -->
    <div class="mt-auto bg-gray-50 border-t border-gray-100 p-6">
      
      <div v-if="startDate" class="flex flex-col gap-4 mb-6">
        
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-gray-200 shadow-sm">
              <CalendarIcon :size="16" class="text-gray-900" />
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Départ</p>
              <p class="text-sm font-bold text-gray-900">{{ startDate.toLocaleDateString('fr-FR') }}</p>
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <div class="text-right">
              <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Retour</p>
              <p class="text-sm font-bold text-gray-900">{{ endDate ? endDate.toLocaleDateString('fr-FR') : '—' }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-gray-200 shadow-sm">
              <CalendarIcon :size="16" class="text-gray-900" />
            </div>
          </div>
        </div>

        <div v-if="endDate" class="bg-white rounded-[1rem] p-4 border border-gray-100 shadow-sm">
          <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-bold text-gray-500">{{Number(props.dailyPrice).toLocaleString('fr-MA')}} MAD x {{daysCount}} jours</span>
            <span class="text-sm font-black text-gray-900">{{Number(totalPrice).toLocaleString('fr-MA')}} MAD</span>
          </div>
          <div class="flex justify-between items-center pt-2 border-t border-gray-50">
            <span class="text-sm font-black uppercase tracking-wider text-gray-900">Total</span>
            <span class="text-xl font-black text-gray-900">{{Number(totalPrice).toLocaleString('fr-MA')}} <span class="text-sm text-gray-500">MAD</span></span>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-6">
        <p class="text-sm font-bold text-gray-400">Veuillez sélectionner vos dates sur le calendrier.</p>
      </div>

      <div v-if="error" class="mb-4 flex items-center gap-2 p-3 bg-red-50 border border-red-100 rounded-xl text-red-600">
        <AlertCircle :size="16" class="shrink-0" />
        <p class="text-xs font-bold">{{error}}</p>
      </div>

      <button @click="confirmBooking" 
              :disabled="!startDate || !endDate || bookingLoading"
              class="w-full bg-black hover:bg-gray-900 text-white font-black uppercase tracking-wider py-4 px-6 rounded-[1rem] shadow-lg transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
        <span v-if="bookingLoading">Redirection...</span>
        <template v-else>
          <Lock :size="16" /> Confirmer la réservation
        </template>
      </button>
      
    </div>
  </div>
</template>
