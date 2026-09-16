<!-- Public vehicle detail view with media, specifications, live availability, and booking. -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  MapPin, Settings2, Gauge, Fuel, Users, Calendar,
  Droplet, Video, ArrowRight, Check, Menu, X
} from 'lucide-vue-next'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'
import AvailabilityCalendar from '../components/AvailabilityCalendar.vue'
import AstraLogo from '../components/AstraLogo.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const car = ref(null)
const loading = ref(true)
const bookingPanelOpen = ref(new URLSearchParams(window.location.search).has('start_date'))
const activeImageIndex = ref(0)
const mainImage = computed(() => car.value?.images?.[activeImageIndex.value]?.path || '/assets/images/fleet-suv.png')

/** Loads the selected car and refreshes its live availability data. */
async function load() {
  try {
    const { data } = await api.get(`/cars/${route.params.id}`)
    car.value = data.data || data
  } catch (err) {
    router.push('/cars')
  } finally {
    loading.value = false
  }
}
onMounted(load)

const fuelLabel = t => ({ gasoline: 'Essence', diesel: 'Diesel', hybrid: 'Hybride', electric: 'Électrique' }[t] || t)
const transLabel = t => ({ manual: 'Manuelle', automatic: 'Automatique' }[t] || t)
</script>

<template>
  <div class="showroom">
    
    <!-- Top Navigation (Pill Style from Reference) -->
    <nav class="showroom__nav">
      <div class="showroom__nav-pill">
        <RouterLink to="/" class="showroom__nav-link">Accueil</RouterLink>
        <RouterLink to="/cars" class="showroom__nav-link showroom__nav-link--active">Véhicules</RouterLink>
        <RouterLink to="/about" class="showroom__nav-link">À propos</RouterLink>
        <RouterLink to="/contact" class="showroom__nav-link">Contact</RouterLink>
      </div>

      <div class="showroom__nav-profile">
        <template v-if="auth.isAuthenticated">
          <RouterLink :to="`/${auth.user.role}/dashboard`" class="showroom__profile-btn">
            <div class="showroom__avatar">
              {{ auth.user.first_name?.[0] }}{{ auth.user.last_name?.[0] }}
            </div>
            <span>{{ auth.user.first_name }}</span>
          </RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/login" class="showroom__profile-btn">
            <span>Connexion</span>
          </RouterLink>
        </template>
      </div>
    </nav>

    <div v-if="loading" class="showroom__loading">
      Chargement du véhicule...
    </div>

    <template v-else>
      <main class="showroom__stage">
        
        <!-- Glowing Ring under car -->
        <div class="showroom__ring">
          <div class="showroom__ring-glow"></div>
        </div>

        <!-- Central Car Image -->
        <div class="showroom__car-wrap">
          <img :src="mainImage" :alt="`${car.brand} ${car.model}`" class="showroom__car-img" />
        </div>

        <!-- ═════════ FLOATING UI ELEMENTS ═════════ -->

        <!-- Top Left: Title Area -->
        <div class="showroom__float showroom__float--tl">
          <div class="showroom__brand-header">
            <AstraLogo class="showroom__logo" />
            <span class="showroom__brand-sub">Premium Cars Collection</span>
          </div>
          <h1 class="showroom__title">{{ car.brand }} {{ car.model }}</h1>
          <p class="showroom__subtitle">{{ car.category?.name || 'Véhicule Premium' }}</p>
          <div class="showroom__price-box">
            <span class="showroom__price-val">{{ Number(car.daily_price).toLocaleString('fr-MA') }}</span>
            <span class="showroom__price-cur">MAD / jour</span>
          </div>
          <div class="showroom__location">
            <MapPin :size="14" /> Agence ASTRA, Tanger
          </div>
        </div>

        <!-- Top Right: Video / Gallery -->
        <div class="showroom__float showroom__float--tr">
          <div class="showroom__gallery-card" v-if="car.images?.length > 1">
            <div class="showroom__gallery-thumbs">
              <button v-for="(img, i) in car.images" :key="img.id" @click="activeImageIndex = i"
                      :class="['showroom__thumb', { 'showroom__thumb--active': activeImageIndex === i }]">
                <img :src="img.path" />
              </button>
            </div>
            <span class="showroom__gallery-label">Galerie Photos</span>
          </div>
        </div>

        <!-- Left Column: Specs -->
        <div class="showroom__float showroom__float--ml">
          <div class="showroom__spec-card">
            <div class="showroom__spec-head">
              <span>Transmission</span> <Settings2 :size="14" />
            </div>
            <div class="showroom__spec-val">{{ transLabel(car.transmission) }}</div>
          </div>
          
          <div class="showroom__spec-card">
            <div class="showroom__spec-head">
              <span>Kilométrage</span> <Gauge :size="14" />
            </div>
            <div class="showroom__spec-val">{{ car.mileage?.toLocaleString('fr-MA') || '0' }} <small>km</small></div>
          </div>
        </div>

        <!-- Right Column: Specs -->
        <div class="showroom__float showroom__float--mr">
          <div class="showroom__spec-card">
            <div class="showroom__spec-head">
              <span>Motorisation</span> <Fuel :size="14" />
            </div>
            <div class="showroom__spec-val">{{ fuelLabel(car.fuel_type) }}</div>
          </div>

          <div class="showroom__spec-card">
            <div class="showroom__spec-head">
              <span>Capacité</span> <Users :size="14" />
            </div>
            <div class="showroom__spec-val">{{ car.seats }} <small>places</small></div>
          </div>

          <div class="showroom__spec-card">
            <div class="showroom__spec-head">
              <span>Année</span> <Calendar :size="14" />
            </div>
            <div class="showroom__spec-val">{{ car.year }}</div>
          </div>
        </div>

        <!-- Bottom Row: Mini Specs -->
        <div class="showroom__float showroom__float--bc">
          <div class="showroom__mini-specs">
            <div class="showroom__mini-spec">
              <Droplet :size="16" class="showroom__mini-icon" />
              <div>
                <span class="showroom__mini-label">Couleur</span>
                <span class="showroom__mini-val">{{ car.color }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Right: Action Card -->
        <div class="showroom__float showroom__float--br">
          <div class="showroom__action-card">
            <div class="showroom__action-text">
              <strong>Réservez ce véhicule !</strong>
              <p>Vérifiez la disponibilité et bloquez vos dates dès maintenant.</p>
            </div>
            <button class="showroom__btn" @click="bookingPanelOpen = true">
              Réserver <ArrowRight :size="16" />
            </button>
          </div>
        </div>

      </main>

      <!-- Integrated Booking Panel (Slide Over) -->
      <div v-if="bookingPanelOpen" class="booking-overlay">
        <div class="booking-overlay__bg" @click="bookingPanelOpen = false"></div>
        <div class="booking-panel">
          <div class="booking-panel__head">
            <div>
              <h2 class="booking-panel__title">Réservation</h2>
              <p class="booking-panel__sub">{{ car.brand }} {{ car.model }}</p>
            </div>
            <button @click="bookingPanelOpen = false" class="booking-panel__close">&times;</button>
          </div>
          <div class="booking-panel__body">
            <AvailabilityCalendar :car-id="car.id" :daily-price="car.daily_price" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
/* Base */
.showroom {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f5f7 0%, #e5e5ea 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #111;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Nav */
.showroom__nav {
  position: absolute;
  top: 2rem;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
  padding: 0 2rem;
}
.showroom__nav-pill {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 0.5rem;
  border-radius: 100px;
  display: flex;
  gap: 0.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}
.showroom__nav-link {
  padding: 0.6rem 1.5rem;
  border-radius: 100px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #6b7280;
  text-decoration: none;
  transition: all 0.3s;
}
.showroom__nav-link:hover {
  color: #111;
}
.showroom__nav-link--active {
  background: #111;
  color: #fff !important;
}

.showroom__nav-profile {
  position: absolute;
  right: 2rem;
}
.showroom__profile-btn {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 0.4rem 1rem 0.4rem 0.4rem;
  border-radius: 100px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: #111;
  font-size: 0.8rem;
  font-weight: 700;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  transition: background 0.3s;
}
.showroom__profile-btn:hover {
  background: #fff;
}
.showroom__avatar {
  width: 32px;
  height: 32px;
  background: #111;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
}

/* Loading */
.showroom__loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: #9ca3af;
}

/* Stage */
.showroom__stage {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8rem 2rem 4rem;
}
.showroom__stage-inner {
  position: relative;
  width: 100%;
  max-width: 1100px;
  height: 60vh;
  min-height: 500px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Glowing Ring */
.showroom__ring {
  position: absolute;
  bottom: 20%;
  left: 50%;
  transform: translateX(-50%);
  width: 900px;
  height: 300px;
  perspective: 1000px;
  pointer-events: none;
  z-index: 1;
}
.showroom__ring-glow {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 12px solid rgba(255, 255, 255, 1);
  box-shadow: 
    0 0 60px rgba(255, 255, 255, 0.9), 
    inset 0 0 60px rgba(255, 255, 255, 0.9),
    0 20px 40px rgba(0, 0, 0, 0.1); /* Slight shadow underneath to ground it */
  transform: rotateX(75deg);
  opacity: 0.9;
}

/* Car Image */
.showroom__car-wrap {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 900px;
  transform: translateY(-5%);
}
.showroom__car-img {
  width: 100%;
  height: auto;
  object-fit: contain;
  filter: drop-shadow(0 40px 30px rgba(0, 0, 0, 0.15));
  animation: float 6s ease-in-out infinite;
}
@keyframes float {
  0% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
  100% { transform: translateY(0); }
}

/* Floating Elements Base */
.showroom__float {
  position: absolute;
  z-index: 20;
}

/* Top Left */
.showroom__float--tl {
  top: 15%;
  left: 4rem;
}
.showroom__brand-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 2rem;
}
.showroom__logo {
  height: 20px;
  object-fit: contain;
}
.showroom__brand-sub {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
}
.showroom__title {
  font-size: 3.5rem;
  font-weight: 300;
  letter-spacing: -0.04em;
  line-height: 1;
  margin-bottom: 0.5rem;
}
.showroom__subtitle {
  font-size: 1rem;
  color: #6b7280;
  margin-bottom: 2rem;
}
.showroom__price-box {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
  margin-bottom: 1.5rem;
}
.showroom__price-val {
  font-size: 2.5rem;
  font-weight: 400;
  letter-spacing: -0.02em;
}
.showroom__price-cur {
  font-size: 1rem;
  font-weight: 600;
  color: #6b7280;
}
.showroom__location {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #111;
}

/* Top Right */
.showroom__float--tr {
  top: 25%;
  right: 4rem;
}
.showroom__gallery-card {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 1.5rem;
  padding: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
  text-align: center;
}
.showroom__gallery-thumbs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.showroom__thumb {
  width: 60px;
  height: 40px;
  border-radius: 0.75rem;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  background: #fff;
  padding: 0;
}
.showroom__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.showroom__thumb--active {
  border-color: #111;
}
.showroom__gallery-label {
  font-size: 0.65rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
}

/* Mid Left */
.showroom__float--ml {
  top: 60%;
  left: 4rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Mid Right */
.showroom__float--mr {
  top: 45%;
  right: 4rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Spec Cards */
.showroom__spec-card {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 1.5rem;
  padding: 1.25rem;
  min-width: 160px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
}
.showroom__spec-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.7rem;
  color: #6b7280;
  margin-bottom: 0.75rem;
}
.showroom__spec-val {
  font-size: 1.5rem;
  font-weight: 400;
}
.showroom__spec-val small {
  font-size: 0.85rem;
  color: #6b7280;
}

/* Bottom Center */
.showroom__float--bc {
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
}
.showroom__mini-specs {
  display: flex;
  gap: 1rem;
}
.showroom__mini-spec {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 1rem;
  padding: 0.75rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}
.showroom__mini-icon {
  color: #6b7280;
}
.showroom__mini-label {
  display: block;
  font-size: 0.6rem;
  color: #6b7280;
  margin-bottom: 0.1rem;
}
.showroom__mini-val {
  font-size: 0.8rem;
  font-weight: 600;
}

/* Bottom Right Action */
.showroom__float--br {
  bottom: 2rem;
  right: 4rem;
}
.showroom__action-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.6);
  border-radius: 1.5rem;
  padding: 1.5rem;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
  width: 260px;
}
.showroom__action-text {
  margin-bottom: 1.25rem;
}
.showroom__action-text strong {
  display: block;
  font-size: 0.9rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
}
.showroom__action-text p {
  font-size: 0.75rem;
  color: #6b7280;
  line-height: 1.4;
}
.showroom__btn {
  width: 100%;
  background: #111;
  color: #fff;
  border: none;
  padding: 1rem;
  border-radius: 1rem;
  font-size: 0.9rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}
.showroom__btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Booking Panel Overlay */
.booking-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  justify-content: flex-end;
}
.booking-overlay__bg {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px);
}
.booking-panel {
  position: relative;
  width: 100%;
  max-width: 450px;
  background: #fff;
  height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: -20px 0 50px rgba(0, 0, 0, 0.1);
  animation: slideIn 0.3s ease-out forwards;
}
.booking-panel__head {
  padding: 1.5rem;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.booking-panel__title {
  font-size: 1.25rem;
  font-weight: 800;
}
.booking-panel__sub {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 600;
}
.booking-panel__close {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f5f5f5;
  border: none;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.booking-panel__close:hover {
  background: #e5e5e5;
}
.booking-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .showroom__stage { flex-direction: column; padding-top: 6rem; overflow-y: auto; justify-content: flex-start; }
  .showroom__float { position: static; margin-bottom: 1rem; width: 100%; }
  .showroom__ring { display: none; }
  .showroom__float--tl, .showroom__float--ml, .showroom__float--mr, .showroom__float--tr, .showroom__float--bc, .showroom__float--br {
    width: 100%; max-width: 500px; margin: 0 auto 1rem;
  }
}
</style>
