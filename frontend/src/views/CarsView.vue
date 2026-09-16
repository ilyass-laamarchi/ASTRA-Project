<!-- Public fleet catalogue with API-backed filters, date availability, sorting, and pagination. -->
<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/api'
import AstraLogo from '../components/AstraLogo.vue'
import { addIsoDays, todayIso } from '../lib/dates'
import { useAvailabilitySync } from '../composables/useAvailabilitySync'
import {
  Search, SlidersHorizontal, RotateCcw, Fuel, Settings2, Users,
  Gauge, ArrowRight, Menu, X, Facebook, Twitter, Instagram, Youtube
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const mobileNav = ref(false)
const mobileFilters = ref(false)

const cars = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref('')
const meta = ref({})
const page = ref(1)

const filters = ref({
  search: '', category_id: '', min_price: '', max_price: '',
  fuel_type: '', transmission: '', seats: '',
  start_date: '', end_date: '', sort: 'price_asc'
})

const today = computed(todayIso)
const endMin = computed(() =>
  filters.value.start_date ? addIsoDays(filters.value.start_date, 1) : addIsoDays(today.value, 1)
)

let timer
/** Loads one filtered page of active cars from the public API. */
async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = Object.fromEntries(
      Object.entries(filters.value).filter(([, value]) => value !== '' && value !== null && value !== undefined)
    )
    const result = await api.get('/cars', { params: { ...params, page: page.value } })
    cars.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
    meta.value = result.meta || result.data?.meta || {}
  } catch (e) {
    cars.value = []
    error.value = e.response?.data?.message || 'Impossible de charger la flotte.'
  } finally {
    loading.value = false
  }
}

/** Resets catalogue filters while preserving a clean route state. */
function clear() {
  Object.assign(filters.value, {
    search: '', category_id: '', min_price: '', max_price: '',
    fuel_type: '', transmission: '', seats: '',
    start_date: '', end_date: '', sort: 'price_asc'
  })
  page.value = 1
  load()
}

/** Clears an end date that no longer follows the selected start date. */
function onStartChange() {
  if (filters.value.end_date && filters.value.end_date < endMin.value)
    filters.value.end_date = ''
}

watch(filters, () => {
  page.value = 1
  clearTimeout(timer)
  timer = setTimeout(() => {
    if ((!filters.value.start_date && !filters.value.end_date) ||
        (filters.value.start_date && filters.value.end_date)) load()
  }, 350)
}, { deep: true })

/** Opens a valid catalogue page and returns the viewport to the results header. */
function goPage(value) { if(value<1 || value>(meta.value.last_page||1))return; page.value=value; load(); window.scrollTo({top:0,behavior:'smooth'}) }

onMounted(async () => {
  const params = new URLSearchParams(location.search)
  for (const key of Object.keys(filters.value))
    if (params.get(key)) filters.value[key] = params.get(key)
  try {
    const result = await api.get('/categories')
    categories.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
  } catch {}
  await load()
})

useAvailabilitySync({ refresh: load })

const fuelLabel = t => ({ gasoline: 'Essence', diesel: 'Diesel', hybrid: 'Hybride', electric: 'Électrique' }[t] || t)
const transLabel = t => ({ manual: 'Manuel', automatic: 'Auto' }[t] || t)
</script>

<template>
  <div class="cv">
    <!-- NAV -->
    <nav class="cv-nav">
      <div class="cv-nav__inner">
        <RouterLink to="/" class="cv-nav__logo">
          <AstraLogo />
        </RouterLink>
        <div class="cv-nav__links">
          <RouterLink to="/" class="cv-nav__link">Accueil</RouterLink>
          <RouterLink to="/cars" class="cv-nav__link cv-nav__link--active">Véhicules</RouterLink>
          <RouterLink to="/about" class="cv-nav__link">À propos</RouterLink>
          <RouterLink to="/contact" class="cv-nav__link">Contact</RouterLink>
        </div>
        <div class="cv-nav__actions">
          <template v-if="!auth.isAuthenticated">
            <RouterLink to="/login" class="cv-nav__login">Connexion</RouterLink>
            <RouterLink to="/register" class="cv-nav__signup">S'inscrire</RouterLink>
          </template>
          <template v-else>
            <RouterLink :to="`/${auth.user.role}/dashboard`" class="cv-nav__signup">Mon Espace</RouterLink>
          </template>
        </div>
        <button class="cv-nav__burger" @click="mobileNav = !mobileNav"><Menu v-if="!mobileNav" :size="24" /><X v-else :size="24" /></button>
      </div>
      <div v-if="mobileNav" class="cv-nav__mobile" @click="mobileNav = false">
        <RouterLink to="/" class="cv-nav__mlink">Accueil</RouterLink>
        <RouterLink to="/cars" class="cv-nav__mlink">Véhicules</RouterLink>
        <RouterLink to="/about" class="cv-nav__mlink">À propos</RouterLink>
        <RouterLink to="/contact" class="cv-nav__mlink">Contact</RouterLink>
      </div>
    </nav>

    <!-- HERO -->
    <section class="cv-hero">
      <span class="cv-hero__label">NOTRE FLOTTE</span>
      <h1 class="cv-hero__title">Trouvez le Véhicule Parfait</h1>
      <p class="cv-hero__sub">Parcourez notre sélection et trouvez le véhicule adapté à vos dates et votre style.</p>
      <p class="cv-hero__count">{{ meta.total || cars.length }} véhicule(s) disponible(s)</p>
    </section>

    <!-- CONTENT -->
    <section class="cv-body">
      <div class="cv-body__inner">
        <!-- Mobile filter toggle -->
        <button class="cv-filter-toggle" @click="mobileFilters = !mobileFilters">
          <SlidersHorizontal :size="18" /> Filtres
        </button>

        <!-- SIDEBAR -->
        <aside :class="['cv-sidebar', { 'cv-sidebar--open': mobileFilters }]">
          <div class="cv-field">
            <label>Rechercher marque ou modèle</label>
            <div class="cv-field__input-wrap">
              <Search :size="16" class="cv-field__icon" />
              <input v-model="filters.search" placeholder="ex: Peugeot" />
            </div>
          </div>

          <div class="cv-field">
            <label>Catégorie</label>
            <select v-model="filters.category_id">
              <option value="">Toutes les catégories</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="cv-field__row">
            <div class="cv-field">
              <label>Prix minimum</label>
              <input v-model="filters.min_price" type="number" placeholder="MAD" />
            </div>
            <div class="cv-field">
              <label>Prix maximum</label>
              <input v-model="filters.max_price" type="number" placeholder="MAD" />
            </div>
          </div>

          <div class="cv-field">
            <label>Carburant</label>
            <select v-model="filters.fuel_type">
              <option value="">Tout</option>
              <option value="gasoline">Essence</option>
              <option value="diesel">Diesel</option>
              <option value="hybrid">Hybride</option>
              <option value="electric">Électrique</option>
            </select>
          </div>

          <div class="cv-field">
            <label>Transmission</label>
            <select v-model="filters.transmission">
              <option value="">Tout</option>
              <option value="manual">Manuelle</option>
              <option value="automatic">Automatique</option>
            </select>
          </div>

          <div class="cv-field">
            <label>Places minimum</label>
            <input v-model="filters.seats" type="number" min="2" max="9" />
          </div>

          <div class="cv-sidebar__sep"></div>
          <h4 class="cv-sidebar__subtitle">Disponibilité</h4>

          <div class="cv-field">
            <label>Date de départ</label>
            <input v-model="filters.start_date" type="date" :min="today" @change="onStartChange" />
          </div>
          <div class="cv-field">
            <label>Date de retour</label>
            <input v-model="filters.end_date" type="date" :min="endMin" />
          </div>

          <div class="cv-sidebar__sep"></div>

          <div class="cv-field">
            <label>Trier par</label>
            <select v-model="filters.sort">
              <option value="price_asc">Prix croissant</option>
              <option value="price_desc">Prix décroissant</option>
              <option value="newest">Plus récents</option>
            </select>
          </div>

          <button class="cv-sidebar__reset" @click="clear">
            <RotateCcw :size="15" /> Réinitialiser
          </button>
        </aside>

        <!-- GRID -->
        <div class="cv-grid-area">
          <!-- Loading -->
          <div v-if="loading" class="cv-grid">
            <div v-for="i in 6" :key="i" class="cv-skel"></div>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="cv-empty">
            <h2>Erreur de chargement</h2>
            <p>{{ error }}</p>
            <button class="cv-empty__btn" @click="load">Réessayer</button>
          </div>

          <!-- No results -->
          <div v-else-if="!cars.length" class="cv-empty">
            <h2>Aucun véhicule trouvé</h2>
            <p>Essayez d'ajuster vos filtres ou vos dates.</p>
            <button class="cv-empty__btn" @click="clear">Effacer les filtres</button>
          </div>

          <!-- Car Cards -->
          <div v-else class="cv-grid">
            <RouterLink v-for="car in cars" :key="car.id"
                        :to="{ path: `/cars/${car.id}`, query: Object.fromEntries(Object.entries({ start_date: filters.start_date, end_date: filters.end_date }).filter(([,v]) => v)) }"
                        class="cv-card">
              <div class="cv-card__img">
                <img :src="car.images?.[0]?.path || '/assets/images/fleet-sedan.png'" :alt="`${car.brand} ${car.model}`" />
                <span class="cv-card__badge">{{ car.category?.name }}</span>
              </div>
              <div class="cv-card__body">
                <h3>{{ car.brand ? (car.brand + ' ' + (car.model || '')) : 'Véhicule ASTRA' }}</h3>
                <div class="cv-card__specs">
                  <span><Fuel :size="14" /> {{ fuelLabel(car.fuel_type) }}</span>
                  <span><Settings2 :size="14" /> {{ transLabel(car.transmission) }}</span>
                  <span><Users :size="14" /> {{ car.seats }} pl.</span>
                  <span><Gauge :size="14" /> {{ car.year }}</span>
                </div>
                <div class="cv-card__foot">
                  <div>
                    <span class="cv-card__price">{{ Number(car.daily_price).toLocaleString('fr-MA') }} MAD</span>
                    <span class="cv-card__per">/jour</span>
                  </div>
                  <span class="cv-card__cta">Réserver <ArrowRight :size="14" /></span>
                </div>
              </div>
            </RouterLink>
          </div>
          <div v-if="!loading && meta.last_page > 1" class="flex items-center justify-center gap-3 mt-8">
            <button class="cv-empty__btn" :disabled="page<=1" @click="goPage(page-1)">Précédent</button>
            <span class="text-sm font-bold">Page {{page}} / {{meta.last_page}}</span>
            <button class="cv-empty__btn" :disabled="page>=meta.last_page" @click="goPage(page+1)">Suivant</button>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="cv-footer">
      <div class="cv-footer__inner">
        <div class="cv-footer__top">
          <div class="cv-footer__brand">
            <AstraLogo variant="inverse" class="cv-footer__logo" loading="lazy" />
            <p>Mobilité premium, service d'exception et confiance pour chaque trajet.</p>
          </div>
          <div class="cv-footer__col">
            <h4>Pages</h4>
            <RouterLink to="/">Accueil</RouterLink>
            <RouterLink to="/cars">Véhicules</RouterLink>
            <RouterLink to="/about">À propos</RouterLink>
          </div>
          <div class="cv-footer__col">
            <h4>Espace Client</h4>
            <RouterLink to="/login">Connexion</RouterLink>
            <RouterLink to="/register">Inscription</RouterLink>
          </div>
          <div class="cv-footer__col"><h4>Nous contacter</h4><RouterLink to="/contact">Formulaire de contact</RouterLink><a href="mailto:contact@astra.ma">contact@astra.ma</a></div>
        </div>
        <div class="cv-footer__bottom">
          <span>© {{ new Date().getFullYear() }} ASTRA Premium. Tous droits réservés.</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.cv { font-family: 'Inter', system-ui, sans-serif; color: #111; background: #fff; }

/* NAV */
.cv-nav { position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,.92); border-bottom: 1px solid rgba(188,211,228,.64); box-shadow: 0 8px 28px rgba(7,26,59,.05); backdrop-filter: blur(18px); }
.cv-nav__inner { max-width: 1380px; margin: 0 auto; padding: 0 clamp(1.25rem,4vw,3.5rem); height: 92px; display: flex; align-items: center; justify-content: space-between; }
.cv-nav__logo { position: relative; width: 220px; display: inline-block; line-height: 0; }
.cv-nav__logo img { display: block; width: 220px; height: auto; object-fit: contain; }
.cv-nav__links { display: flex; gap: clamp(1.4rem,3vw,2.6rem); }
.cv-nav__link { position: relative; padding: 1.85rem 0; font-size: .84rem; font-weight: 700; color: #52627a; text-decoration: none; transition: color .2s; }
.cv-nav__link::after { content: ''; position: absolute; right: 0; bottom: 1.25rem; left: 0; height: 2px; background: #3bc8f5; border-radius: 10px; transform: scaleX(0); transition: transform .2s ease; }
.cv-nav__link:hover, .cv-nav__link--active { color: #071a3b; }
.cv-nav__link:hover::after, .cv-nav__link--active::after { transform: scaleX(1); }
.cv-nav__actions { display: flex; align-items: center; gap: 1rem; }
.cv-nav__login { font-size: .875rem; font-weight: 600; color: #071a3b; text-decoration: none; }
.cv-nav__signup { font-size: .875rem; font-weight: 700; color: #fff; background: linear-gradient(135deg,#071a3b,#0d4f89); border: 1px solid rgba(59,200,245,.25); box-shadow: 0 8px 22px rgba(7,42,84,.18); padding: .6rem 1.5rem; border-radius: 100px; text-decoration: none; transition: transform .2s ease,box-shadow .2s ease; }
.cv-nav__signup:hover { transform: translateY(-1px); box-shadow: 0 11px 28px rgba(7,42,84,.25); }
.cv-nav__burger { display: none; color: #071a3b; background: none; border: none; cursor: pointer; }
.cv-nav__mobile { display: none; flex-direction: column; padding: 1rem 2rem 2rem; background: #fff; border-top: 1px solid #dbe7f1; }
.cv-nav__mlink { padding: .75rem 0; font-weight: 600; color: #071a3b; text-decoration: none; border-bottom: 1px solid #dbe7f1; }
@media (max-width: 768px) {
  .cv-nav__inner { height: 70px; }
  .cv-nav__logo, .cv-nav__logo img { width: 175px; }
  .cv-nav__links, .cv-nav__actions { display: none; }
  .cv-nav__burger { display: block; }
  .cv-nav__mobile { display: flex; }
}

/* HERO */
.cv-hero { max-width: 1280px; margin: 0 auto; padding: 3.5rem 2rem 2rem; }
.cv-hero__label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #9ca3af; }
.cv-hero__title { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; letter-spacing: -.03em; margin: .5rem 0; }
.cv-hero__sub { color: #6b7280; font-weight: 500; font-size: .95rem; max-width: 500px; }
.cv-hero__count { margin-top: 1rem; font-size: .85rem; font-weight: 700; color: #111; padding: .5rem 1rem; background: #fafafa; border-radius: 100px; display: inline-block; border: 1px solid #f0f0f0; }

/* BODY */
.cv-body { padding: 0 0 5rem; }
.cv-body__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start; }

/* FILTER TOGGLE */
.cv-filter-toggle { display: none; align-items: center; gap: .5rem; padding: .65rem 1.25rem; border: 1.5px solid #e5e7eb; border-radius: 100px; background: #fff; font-size: .8rem; font-weight: 700; cursor: pointer; margin-bottom: 1rem; grid-column: 1 / -1; }
@media (max-width: 1024px) {
  .cv-body__inner { grid-template-columns: 1fr; }
  .cv-filter-toggle { display: flex; }
  .cv-sidebar { display: none; }
  .cv-sidebar--open { display: flex; }
}

/* SIDEBAR */
.cv-sidebar { background: #fff; border: 1px solid #f0f0f0; border-radius: 1.25rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; position: sticky; top: 88px; }
.cv-sidebar__sep { border-top: 1px solid #f0f0f0; margin: .25rem 0; }
.cv-sidebar__subtitle { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; }
.cv-sidebar__reset { display: flex; align-items: center; justify-content: center; gap: .4rem; padding: .65rem; border: 1.5px solid #e5e7eb; border-radius: 100px; background: #fff; font-size: .8rem; font-weight: 700; cursor: pointer; color: #6b7280; transition: all .2s; }
.cv-sidebar__reset:hover { border-color: #111; color: #111; }

/* FIELDS */
.cv-field { display: flex; flex-direction: column; gap: .35rem; }
.cv-field label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; }
.cv-field input, .cv-field select {
  padding: .6rem .85rem; border: 1.5px solid #e5e7eb; border-radius: .75rem;
  font-size: .82rem; font-weight: 600; color: #111; background: #fafafa;
  outline: none; transition: border-color .2s;
}
.cv-field input:focus, .cv-field select:focus { border-color: #111; background: #fff; }
.cv-field input::placeholder { color: #c0bcc8; }
.cv-field__input-wrap { position: relative; display: flex; align-items: center; }
.cv-field__icon { position: absolute; left: .75rem; color: #c0bcc8; pointer-events: none; }
.cv-field__input-wrap input { padding-left: 2.25rem; width: 100%; }
.cv-field__row { display: flex; flex-direction: column; gap: 1rem; }

/* GRID */
.cv-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
@media (max-width: 1024px) { .cv-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .cv-grid { grid-template-columns: 1fr; } }

/* SKELETON */
.cv-skel { height: 360px; background: linear-gradient(110deg, #f0f0f0 25%, #fafafa 37%, #f0f0f0 63%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 1.25rem; }
@keyframes shimmer { to { background-position-x: -200%; } }

/* EMPTY */
.cv-empty { grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; background: #fafafa; border-radius: 1.25rem; border: 1px solid #f0f0f0; }
.cv-empty h2 { font-size: 1.25rem; font-weight: 800; margin-bottom: .5rem; }
.cv-empty p { color: #6b7280; font-weight: 500; font-size: .9rem; margin-bottom: 1.5rem; }
.cv-empty__btn { padding: .65rem 1.5rem; background: #111; color: #fff; border: none; border-radius: 100px; font-size: .8rem; font-weight: 700; cursor: pointer; }

/* CARD */
.cv-card { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 1.25rem; overflow: hidden; text-decoration: none; color: #111; transition: box-shadow .3s, transform .3s; display: flex; flex-direction: column; }
.cv-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,.08); transform: translateY(-4px); }
.cv-card__img { padding: 1.5rem 1.5rem 0; height: 180px; display: flex; align-items: center; justify-content: center; position: relative; }
.cv-card__img img { max-width: 100%; max-height: 100%; object-fit: contain; }
.cv-card__badge { position: absolute; top: 1rem; left: 1rem; background: #fff; border: 1px solid #f0f0f0; padding: .25rem .75rem; border-radius: 100px; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; }
.cv-card__body { padding: 1.25rem 1.5rem 1.5rem; flex: 1; display: flex; flex-direction: column; }
.cv-card__body h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: .75rem; }
.cv-card__specs { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.25rem; }
.cv-card__specs span { display: flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 600; color: #6b7280; }
.cv-card__foot { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f0f0f0; padding-top: 1rem; margin-top: auto; }
.cv-card__price { font-size: 1.1rem; font-weight: 800; }
.cv-card__per { font-size: .72rem; color: #9ca3af; font-weight: 600; }
.cv-card__cta { display: flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 700; }

/* FOOTER */
.cv-footer { background: #111; color: #fff; padding: 4rem 0 2rem; }
.cv-footer__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
.cv-footer__top { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 3rem; padding-bottom: 3rem; border-bottom: 1px solid rgba(255,255,255,.1); }
.cv-footer__logo { height: 54px; width: auto; object-fit: contain; filter: drop-shadow(0 0 1px rgba(255,255,255,.55)); margin-bottom: 1rem; }
.cv-footer__brand p { font-size: .85rem; color: rgba(255,255,255,.5); line-height: 1.6; max-width: 280px; }
.cv-footer__col h4 { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.4); margin-bottom: 1.25rem; }
.cv-footer__col a { display: block; font-size: .85rem; color: rgba(255,255,255,.6); text-decoration: none; padding: .3rem 0; transition: color .2s; }
.cv-footer__col a:hover { color: #fff; }
.cv-footer__socials { display: flex; gap: .75rem; }
.cv-footer__socials a { width: 36px; height: 36px; border-radius: 50%; border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.5); padding: 0; }
.cv-footer__socials a:hover { background: #fff; color: #111; border-color: #fff; }
.cv-footer__bottom { padding-top: 2rem; text-align: center; font-size: .75rem; color: rgba(255,255,255,.35); }
@media (max-width: 768px) { .cv-footer__top { grid-template-columns: 1fr 1fr; gap: 2rem; } }
</style>
