<!-- Reusable public fleet card that renders one car with its main image and price. -->
<script setup>
import { computed } from 'vue'
import { Users, Fuel, Gauge, ArrowUpRight } from 'lucide-vue-next'

const props = defineProps({
  car: { type: Object, required: true },
  dates: { type: Object, default: () => ({}) },
})

const fuelLabels = {
  gasoline: 'Essence',
  diesel: 'Diesel',
  hybrid: 'Hybride',
  electric: 'Électrique',
}

const transmissionLabels = {
  manual: 'Manuelle',
  automatic: 'Automatique',
}

const detailLink = computed(() => ({
  path: `/cars/${props.car.id}`,
  query: Object.fromEntries(Object.entries(props.dates).filter(([, value]) => value)),
}))
</script>
<template>
  <article class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-[0_10px_40px_rgba(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(0,0,0,0.08)] transition-all duration-300 group flex flex-col h-full transform hover:-translate-y-1">
    <div class="relative bg-brand-light aspect-[4/3] flex items-center justify-center p-6 overflow-hidden">
      <div class="absolute bottom-4 w-3/4 h-10 bg-gray-200 rounded-[100%] blur-xl opacity-60 group-hover:bg-gray-300 transition-colors"></div>
      <img class="w-full h-full object-contain relative z-10 drop-shadow-xl transform group-hover:scale-105 transition-transform duration-500" :src="car.images?.[0]?.path || '/assets/images/fleet-suv.png'" :alt="`${car.brand} ${car.model}`">
      <div class="absolute top-4 left-4 z-20">
        <span class="bg-white/90 backdrop-blur text-brand-dark px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm">{{ car.category?.name || car.category }}</span>
      </div>
    </div>
    
    <div class="p-6 md:p-8 flex flex-col flex-1 bg-white">
      <h3 class="text-2xl font-black text-brand-dark mb-4">{{ car.brand }} {{ car.model }}</h3>
      
      <div class="grid grid-cols-3 gap-2 mb-6 mt-auto">
        <div class="flex flex-col items-center justify-center bg-brand-light p-3 rounded-2xl text-brand-dark">
          <Gauge :size="16" class="mb-1 opacity-70"/>
          <span class="text-[10px] font-bold uppercase tracking-wider text-center leading-tight truncate w-full">{{ transmissionLabels[car.transmission] }}</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-brand-light p-3 rounded-2xl text-brand-dark">
          <Fuel :size="16" class="mb-1 opacity-70"/>
          <span class="text-[10px] font-bold uppercase tracking-wider text-center leading-tight truncate w-full">{{ fuelLabels[car.fuel_type] }}</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-brand-light p-3 rounded-2xl text-brand-dark">
          <Users :size="16" class="mb-1 opacity-70"/>
          <span class="text-[10px] font-bold uppercase tracking-wider text-center leading-tight truncate w-full">{{ car.seats }} places</span>
        </div>
      </div>
      
      <div class="flex items-center justify-between border-t border-gray-100 pt-6">
        <div>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tarif journalier</p>
          <strong class="text-2xl font-black text-brand-dark leading-none block">{{ Number(car.daily_price).toLocaleString('fr-MA') }} <span class="text-sm">MAD</span></strong>
        </div>
        <RouterLink class="w-12 h-12 rounded-full bg-brand-dark text-white flex items-center justify-center hover:bg-brand-yellow hover:text-brand-dark shadow-md transition-colors group-hover:scale-110" :to="detailLink" aria-label="Voir le véhicule">
          <ArrowUpRight :size="20"/>
        </RouterLink>
      </div>
    </div>
  </article>
</template>
