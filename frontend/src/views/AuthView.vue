<!-- Premium ASTRA login and public client registration. Authentication remains store/API driven. -->
<script setup>
import { reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/api'
import AstraLogo from '../components/AstraLogo.vue'
import { ArrowLeft, Mail, Lock, Phone, User, AlertCircle, Eye, EyeOff, ShieldCheck, Headphones, ArrowRight, Gem, MapPin } from 'lucide-vue-next'

const props = defineProps({ mode: String })
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const form = reactive({ first_name: '', last_name: '', email: '', phone: '', password: '', password_confirmation: '', remember: false })
const state = reactive({ showPassword: false, showConfirmation: false, termsAccepted: false })
const registering = computed(() => props.mode === 'register')
const submitLabel = computed(() => auth.loading
  ? (registering.value ? 'Création du compte...' : 'Connexion...')
  : (registering.value ? 'Créer mon compte' : 'Se connecter'))

/** Validates local confirmation, authenticates, and opens the role dashboard. */
async function submit() {
  auth.error = ''
  if (registering.value && form.password !== form.password_confirmation) {
    auth.error = 'La confirmation du mot de passe ne correspond pas.'
    return
  }
  try {
    const user = registering.value
      ? await auth.register(form)
      : await auth.login({ email: form.email, password: form.password })
    router.push(route.query.redirect || `/${user.role}/dashboard`)
  } catch {}
}

/** Requests the configured Google consent URL from Laravel. */
async function loginWithGoogle() {
  auth.error = ''
  try {
    const { data } = await api.get('/auth/google/redirect')
    window.location.href = data.url
  } catch (error) {
    auth.error = error.response?.data?.message || 'La connexion avec Google est temporairement indisponible.'
  }
}

watch(registering, () => {
  auth.error = ''
  form.password = ''
  form.password_confirmation = ''
  state.showPassword = false
  state.showConfirmation = false
})

onMounted(() => {
  auth.error = ''
  if (route.query.error === 'oauth_failed') auth.error = 'La connexion avec Google a échoué.'
})
</script>

<template>
  <main class="auth-page">
    <section class="auth-shell" :class="{ 'auth-shell--register': registering, 'auth-shell--login': !registering }" :aria-label="registering ? 'Créer un compte ASTRA' : 'Connexion ASTRA'">
      <div class="auth-form-panel">
        <RouterLink to="/" class="auth-back"><ArrowLeft :size="16" aria-hidden="true" />Retour au site</RouterLink>

        <div class="auth-form-wrap">
          <RouterLink to="/" class="auth-brand" aria-label="Accueil ASTRA">
            <AstraLogo />
          </RouterLink>

          <header class="auth-heading">
            <span class="auth-eyebrow">ASTRA LOCATION PREMIUM</span>
            <h1 v-if="registering">Créez votre <span class="auth-title-accent">compte.</span></h1>
            <h1 v-else>Bienvenue chez <span class="auth-title-accent">ASTRA.</span></h1>
            <p>{{ registering ? "Rejoignez ASTRA et profitez d'une expérience premium sur mesure." : "Connectez-vous à votre espace et accédez à l'expérience premium." }}</p>
          </header>

          <form class="auth-form" @submit.prevent="submit">
            <div v-if="registering" class="auth-name-grid">
              <label class="auth-field"><span>Prénom</span><span class="auth-input"><User :size="18" aria-hidden="true" /><input v-model.trim="form.first_name" name="first_name" autocomplete="given-name" required placeholder="Prénom" /></span></label>
              <label class="auth-field"><span>Nom</span><span class="auth-input"><User :size="18" aria-hidden="true" /><input v-model.trim="form.last_name" name="last_name" autocomplete="family-name" required placeholder="Nom" /></span></label>
            </div>

            <label class="auth-field">
              <span>Adresse e-mail</span>
              <span class="auth-input"><Mail :size="18" aria-hidden="true" /><input v-model.trim="form.email" name="email" type="email" inputmode="email" autocomplete="email" required placeholder="nom@exemple.com" /></span>
            </label>

            <label v-if="registering" class="auth-field">
              <span>Numéro de téléphone</span>
              <span class="auth-input"><Phone :size="18" aria-hidden="true" /><input v-model.trim="form.phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="Numéro de téléphone" /></span>
            </label>

            <label class="auth-field">
              <span>Mot de passe</span>
              <span class="auth-input auth-input--password">
                <Lock :size="18" aria-hidden="true" />
                <input v-model="form.password" name="password" :type="state.showPassword ? 'text' : 'password'" :autocomplete="registering ? 'new-password' : 'current-password'" minlength="8" required placeholder="Mot de passe" />
                <button type="button" class="auth-password-toggle" :aria-label="state.showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'" :aria-pressed="state.showPassword" @click="state.showPassword = !state.showPassword"><Eye v-if="state.showPassword" :size="19" aria-hidden="true" /><EyeOff v-else :size="19" aria-hidden="true" /></button>
              </span>
            </label>

            <label v-if="registering" class="auth-field">
              <span>Confirmation du mot de passe</span>
              <span class="auth-input auth-input--password">
                <Lock :size="18" aria-hidden="true" />
                <input v-model="form.password_confirmation" name="password_confirmation" :type="state.showConfirmation ? 'text' : 'password'" autocomplete="new-password" minlength="8" required placeholder="Confirmer le mot de passe" />
                <button type="button" class="auth-password-toggle" :aria-label="state.showConfirmation ? 'Masquer la confirmation' : 'Afficher la confirmation'" :aria-pressed="state.showConfirmation" @click="state.showConfirmation = !state.showConfirmation"><Eye v-if="state.showConfirmation" :size="19" aria-hidden="true" /><EyeOff v-else :size="19" aria-hidden="true" /></button>
              </span>
            </label>

            <div v-if="registering" class="auth-terms">
              <input id="terms" v-model="state.termsAccepted" type="checkbox" required />
              <label for="terms">J’accepte les <RouterLink to="/legal">conditions d’utilisation</RouterLink> et la <RouterLink to="/privacy">politique de confidentialité</RouterLink>.</label>
            </div>

            <div v-else class="auth-options">
              <label class="auth-remember"><input v-model="form.remember" type="checkbox" /><span>Se souvenir de moi</span></label>
              <RouterLink to="/forgot-password">Mot de passe oublié ?</RouterLink>
            </div>

            <div v-if="auth.error" class="auth-alert" role="alert" aria-live="polite"><AlertCircle :size="18" aria-hidden="true" /><span>{{ auth.error }}</span></div>

            <button class="auth-submit" type="submit" :disabled="auth.loading">
              <span>{{ submitLabel }}</span><ArrowRight v-if="!auth.loading" :size="18" aria-hidden="true" /><span v-else class="auth-spinner" aria-hidden="true"></span>
            </button>
          </form>

          <div class="auth-divider auth-divider--login"><span>ou</span></div>
          <button class="auth-google" type="button" :disabled="auth.loading" @click="loginWithGoogle">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Continuer avec Google
          </button>

          <p class="auth-switch">{{ registering ? 'Vous avez déjà un compte ?' : 'Vous n’avez pas encore de compte ?' }} <RouterLink :to="registering ? '/login' : '/register'">{{ registering ? 'Se connecter' : 'Créer un compte' }}<ArrowRight v-if="!registering" :size="16" aria-hidden="true" /></RouterLink></p>
        </div>
      </div>

      <aside class="auth-visual" aria-label="Présentation ASTRA Location Premium">
        <img
          src="/assets/images/astra-login-clean-background-exact-4k.png"
          alt="Grand tourisme bleu devant une résidence côtière premium"
          :loading="registering ? 'lazy' : 'eager'"
          decoding="async"
        />
        <div class="auth-visual-shade"></div>
        <div class="auth-visual-top"><span class="auth-visual-mark">ASTRA LOCATION PREMIUM</span><span class="auth-visual-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
        <div v-if="!registering" class="auth-login-quote"><span>♔</span><strong>L’élégance<br>à chaque trajet</strong><i></i></div>
        <div class="auth-visual-copy">
          <span class="auth-visual-kicker">Mobilité <i></i> Service <i></i> Confiance</span>
          <h2>{{ registering ? 'Votre prochaine expérience commence ici.' : 'Retrouvez le plaisir de conduire.' }}</h2>
          <p>Une flotte d’exception et un service sur mesure, pensés pour chaque kilomètre.</p>
          <div v-if="!registering" class="auth-visual-benefits">
            <span><Gem :size="27" aria-hidden="true" /><span><strong>Véhicules d'exception</strong><small>Une sélection exclusive des plus belles voitures.</small></span></span>
            <span><ShieldCheck :size="27" aria-hidden="true" /><span><strong>Service sur mesure</strong><small>Accompagnement dédié 24h/24 et 7j/7.</small></span></span>
            <span><MapPin :size="27" aria-hidden="true" /><span><strong>Liberté totale</strong><small>Roulez où vous voulez, en toute sérénité.</small></span></span>
          </div>
          <div v-else class="auth-visual-benefits"><span><ShieldCheck :size="18" aria-hidden="true" /> Réservation sécurisée</span><span><Headphones :size="18" aria-hidden="true" /> Assistance 24/7</span></div>
        </div>
      </aside>
    </section>
  </main>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&display=swap');

.auth-page{min-height:100svh;display:grid;place-items:center;padding:24px;overflow-x:hidden;color:#0a1731;font-family:'Inter',system-ui,-apple-system,sans-serif;background:radial-gradient(circle at 10% 12%,rgba(73,200,255,.22),transparent 31%),radial-gradient(circle at 91% 88%,rgba(50,111,210,.24),transparent 34%),linear-gradient(135deg,#061735 0%,#0d3568 52%,#06152f 100%)}
.auth-shell{width:min(1360px,100%);min-height:min(830px,calc(100svh - 48px));display:grid;grid-template-columns:minmax(470px,.92fr) minmax(500px,1.08fr);padding:12px;overflow:hidden;background:rgba(247,251,255,.97);border:1px solid rgba(105,211,255,.28);border-radius:32px;box-shadow:0 30px 100px rgba(1,15,43,.34),inset 0 1px 0 rgba(255,255,255,.82);animation:auth-enter .65s cubic-bezier(.2,.8,.2,1) both}
.auth-shell--register{min-height:min(900px,calc(100svh - 48px))}
.auth-form-panel{min-width:0;max-height:calc(100svh - 72px);display:flex;flex-direction:column;padding:clamp(28px,3.5vw,52px);overflow-y:auto;background:linear-gradient(155deg,rgba(255,255,255,.98),rgba(241,248,255,.96));scrollbar-width:thin;scrollbar-color:#87d9fa transparent}
.auth-form-panel::-webkit-scrollbar{width:6px}.auth-form-panel::-webkit-scrollbar-track{background:transparent}.auth-form-panel::-webkit-scrollbar-thumb{background:#87d9fa;border-radius:999px}
.auth-back{align-self:flex-start;display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;color:#687287;font-size:.76rem;font-weight:700;text-decoration:none;transition:color .2s ease,transform .2s ease}.auth-back:hover{color:#0a1731;transform:translateX(-2px)}
.auth-form-wrap{width:min(100%,470px);margin:auto}.auth-brand{display:inline-block;margin-bottom:25px;line-height:0}.auth-brand img{display:block;width:176px;height:auto}
.auth-heading{margin-bottom:24px}.auth-eyebrow{display:block;margin-bottom:10px;color:#168fc8;font-size:.65rem;font-weight:800;letter-spacing:.13em}.auth-heading h1{margin:0;color:#071a3b;font-family:'Playfair Display',Georgia,serif;font-size:clamp(2.35rem,3.2vw,3.25rem);font-weight:600;line-height:1.05;letter-spacing:-.035em}.auth-heading p{max-width:430px;margin:12px 0 0;color:#5d6f88;font-size:.88rem;line-height:1.65}
.auth-google{width:100%;min-height:54px;display:flex;align-items:center;justify-content:center;gap:12px;padding:0 18px;color:#26334b;background:#fff;border:1px solid #dfe3e9;border-radius:15px;font-family:inherit;font-size:.82rem;font-weight:750;cursor:pointer;box-shadow:0 5px 18px rgba(10,23,49,.04);transition:border-color .2s ease,background .2s ease,transform .2s ease}.auth-google svg{width:19px;height:19px}.auth-google:hover:not(:disabled){background:#fbfaf8;border-color:#bdc4cf;transform:translateY(-1px)}.auth-google:disabled{opacity:.6;cursor:wait}
.auth-divider{display:flex;align-items:center;gap:14px;margin:20px 0;color:#9aa1ad;font-size:.62rem;font-weight:750;letter-spacing:.1em;text-transform:uppercase}.auth-divider:before,.auth-divider:after{content:'';flex:1;height:1px;background:#e9e5df}
.auth-form{display:grid;gap:15px}.auth-name-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.auth-field{min-width:0;display:grid;gap:7px;color:#2b374d;font-size:.72rem;font-weight:750}.auth-field>span:first-child{padding-left:3px}
.auth-input{position:relative;min-width:0;display:flex;align-items:center}.auth-input>svg{position:absolute;z-index:2;left:16px;width:18px;height:18px;color:#7e90a8;pointer-events:none;transition:color .2s ease}.auth-input input{width:100%;height:54px;min-width:0;padding:0 16px 0 48px;color:#0b2043;background:#f7fbff;border:1px solid #d8e4ef;border-radius:15px;outline:none;font:600 .82rem/1.2 inherit;transition:border-color .2s ease,box-shadow .2s ease,background .2s ease}.auth-input input::placeholder{color:#96a4b5;font-weight:500;opacity:1}.auth-input input:hover{border-color:#b6d6e7}.auth-input input:focus{background:#fff;border-color:#36bdf3;box-shadow:0 0 0 4px rgba(54,189,243,.13)}.auth-input:focus-within>svg{color:#168fc8}.auth-input--password input{padding-right:50px}
.auth-password-toggle{position:absolute;z-index:3;right:9px;width:36px;height:36px;display:grid;place-items:center;padding:0;color:#8b94a4;background:transparent;border:0;border-radius:10px;cursor:pointer;transition:color .2s ease,background .2s ease}.auth-password-toggle:hover{color:#0a1731;background:#f0ece6}
.auth-options,.auth-terms{display:flex;align-items:center;color:#69748a;font-size:.7rem;font-weight:600}.auth-options{justify-content:space-between;gap:16px;margin-top:2px}.auth-options>a,.auth-terms a{color:#0a1731;font-weight:750;text-decoration:none}.auth-options>a:hover,.auth-terms a:hover{text-decoration:underline}.auth-remember{display:flex;align-items:center;gap:9px;cursor:pointer}.auth-terms{align-items:flex-start;gap:10px;line-height:1.55}.auth-options input,.auth-terms input{width:16px;height:16px;flex:0 0 auto;margin:0;accent-color:#0a1731;cursor:pointer}
.auth-alert{display:flex;align-items:flex-start;gap:9px;padding:11px 13px;color:#9e2f2f;background:#fff3f1;border:1px solid #f3cbc5;border-radius:13px;font-size:.72rem;font-weight:650;line-height:1.45}.auth-alert svg{flex:0 0 auto;margin-top:1px}
.auth-submit{width:100%;min-height:56px;display:flex;align-items:center;justify-content:center;gap:11px;padding:0 22px;color:#fff;background:linear-gradient(135deg,#071b3e,#0d4f89);border:1px solid rgba(88,205,255,.22);border-radius:15px;font-family:inherit;font-size:.84rem;font-weight:750;cursor:pointer;box-shadow:0 11px 27px rgba(4,35,76,.23);transition:background .2s ease,transform .2s ease,box-shadow .2s ease}.auth-submit:hover:not(:disabled){background:linear-gradient(135deg,#0a2855,#1169aa);transform:translateY(-1px);box-shadow:0 14px 32px rgba(4,35,76,.29)}.auth-submit:disabled{opacity:.7;cursor:wait}.auth-spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.34);border-top-color:#fff;border-radius:50%;animation:auth-spin .8s linear infinite}
.auth-switch{margin:22px 0 0;color:#7b8494;font-size:.75rem;font-weight:600;text-align:center}.auth-switch a{color:#0a1731;font-weight:800;text-decoration:none}.auth-switch a:hover{text-decoration:underline}
.auth-visual{position:relative;min-width:0;overflow:hidden;border-radius:24px;background:#07172f;isolation:isolate}.auth-visual>img{position:absolute;inset:0;z-index:-3;width:100%;height:100%;object-fit:cover;object-position:50% 56%;transform:scale(1.01);transition:transform 12s ease-out}.auth-shell:hover .auth-visual>img{transform:scale(1.035)}.auth-visual-shade{position:absolute;inset:0;z-index:-2;background:linear-gradient(180deg,rgba(4,21,52,.12),rgba(4,21,52,.04) 34%,rgba(3,18,46,.88)),linear-gradient(90deg,rgba(18,90,144,.08),rgba(3,18,46,.22))}
.auth-visual-top{position:absolute;top:30px;left:30px;right:30px;display:flex;align-items:center;justify-content:space-between}.auth-visual-mark{padding:9px 12px;color:#fff;background:rgba(6,30,67,.56);border:1px solid rgba(87,207,255,.32);border-radius:999px;box-shadow:inset 0 1px 0 rgba(255,255,255,.12);backdrop-filter:blur(12px);font-size:.58rem;font-weight:850;letter-spacing:.12em}.auth-visual-dots{display:flex;gap:6px}.auth-visual-dots i{width:7px;height:7px;background:rgba(255,255,255,.36);border-radius:50%}.auth-visual-dots i:first-child{background:#45c9ff;box-shadow:0 0 9px rgba(69,201,255,.7)}
.auth-visual-copy{position:absolute;right:34px;bottom:34px;left:34px;padding:25px;color:#fff;background:rgba(4,24,58,.62);border:1px solid rgba(86,207,255,.22);border-radius:20px;box-shadow:0 20px 50px rgba(0,10,28,.28),inset 0 1px 0 rgba(255,255,255,.1);backdrop-filter:blur(16px)}.auth-visual-kicker{display:flex;align-items:center;gap:8px;color:#68d9ff;font-size:.61rem;font-weight:750;letter-spacing:.12em;text-transform:uppercase}.auth-visual-kicker i{width:3px;height:3px;background:#46caff;border-radius:50%;box-shadow:0 0 6px rgba(70,202,255,.65)}.auth-visual-copy h2{max-width:500px;margin:12px 0 8px;font-family:'Playfair Display',Georgia,serif;font-size:clamp(2rem,3vw,3.15rem);font-weight:500;line-height:1.05;letter-spacing:-.035em}.auth-visual-copy p{max-width:440px;margin:0;color:rgba(231,246,255,.77);font-size:.75rem;line-height:1.6}.auth-visual-benefits{display:flex;flex-wrap:wrap;gap:10px 22px;margin-top:18px;padding-top:17px;border-top:1px solid rgba(103,211,255,.19)}.auth-visual-benefits span{display:inline-flex;align-items:center;gap:8px;color:rgba(241,250,255,.91);font-size:.66rem;font-weight:650}.auth-visual-benefits svg{color:#55d3ff}
.auth-shell--register .auth-visual>img{object-position:50% 48%}.auth-shell--register .auth-visual-copy{right:28px;bottom:28px;left:28px;padding:18px 21px;background:rgba(8,13,23,.49)}.auth-shell--register .auth-visual-copy h2{max-width:560px;margin:8px 0 5px;font-size:clamp(1.8rem,2.55vw,2.65rem)}.auth-shell--register .auth-visual-benefits{margin-top:12px;padding-top:11px}
@keyframes auth-enter{from{opacity:0;transform:translateY(14px) scale(.992)}to{opacity:1;transform:none}}@keyframes auth-spin{to{transform:rotate(360deg)}}
@media(max-width:1050px){.auth-shell{grid-template-columns:minmax(430px,1fr) minmax(390px,.9fr)}.auth-form-panel{padding:34px}.auth-visual-copy{right:22px;bottom:22px;left:22px;padding:21px}}
@media(max-width:900px){.auth-page{align-items:start}.auth-shell,.auth-shell--register{min-height:auto;grid-template-columns:1fr}.auth-form-panel{order:2;max-height:none;overflow:visible}.auth-visual{order:1;height:255px}.auth-visual>img{object-position:50% 64%}.auth-visual-copy,.auth-shell--register .auth-visual-copy{padding:18px 20px}.auth-visual-copy h2,.auth-shell--register .auth-visual-copy h2{margin:7px 0 4px;font-size:1.8rem}.auth-visual-copy p,.auth-visual-benefits{display:none}}
@media(max-width:640px){.auth-page{display:block;min-height:100svh;padding:0;background:#f3f8fd}.auth-shell,.auth-shell--register{width:100%;padding:0;border:0;border-radius:0;box-shadow:none}.auth-visual{height:165px;border-radius:0 0 22px 22px}.auth-visual-top{top:16px;right:16px;left:16px}.auth-visual-mark{font-size:.5rem}.auth-visual-copy,.auth-shell--register .auth-visual-copy{right:16px;bottom:14px;left:16px;padding:12px 14px;border-radius:14px}.auth-visual-kicker{font-size:.49rem}.auth-visual-copy h2{margin:5px 0 0;font-size:1.35rem}.auth-shell--register .auth-visual-copy h2{margin:5px 0 0;font-size:1.12rem;line-height:1.03}.auth-form-panel{padding:24px 18px 34px}.auth-back{margin-bottom:20px}.auth-form-wrap{max-width:480px}.auth-brand{margin-bottom:19px}.auth-brand img{width:152px}.auth-heading{margin-bottom:20px}.auth-heading h1{font-size:2.25rem}.auth-heading p{margin-top:9px;font-size:.8rem}.auth-name-grid{grid-template-columns:1fr;gap:15px}.auth-input input{height:53px}.auth-options{align-items:flex-start}}
@media(max-width:370px){.auth-form-panel{padding-inline:14px}.auth-visual{height:150px}.auth-shell--register .auth-visual-copy{padding:9px 12px}.auth-shell--register .auth-visual-kicker{display:none}.auth-shell--register .auth-visual-copy h2{margin:0;font-size:1.05rem}.auth-heading h1{font-size:2.05rem}.auth-options{gap:10px;font-size:.65rem}.auth-input input{font-size:.78rem}}
@media(prefers-reduced-motion:reduce){.auth-shell,.auth-visual>img,.auth-back,.auth-google,.auth-submit,.auth-spinner{animation:none!important;transition:none!important}}

@media not all {
/* Organic ASTRA auth experience */
.auth-page{position:relative;isolation:isolate;background:radial-gradient(circle at 12% 15%,rgba(53,198,248,.26),transparent 27%),radial-gradient(circle at 86% 82%,rgba(33,104,194,.28),transparent 31%),linear-gradient(135deg,#04132e 0%,#0a3768 58%,#061a39 100%)}
.auth-atmosphere{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none}.auth-atmosphere i{position:absolute;border:1px solid rgba(92,214,254,.14);border-radius:48% 52% 46% 54%;animation:auth-drift 16s ease-in-out infinite alternate}.auth-atmosphere i:nth-child(1){top:-32vh;left:-12vw;width:72vw;height:75vh}.auth-atmosphere i:nth-child(2){right:-20vw;bottom:-38vh;width:78vw;height:82vh;animation-delay:-6s}.auth-atmosphere i:nth-child(3){top:18%;right:8%;width:28vw;height:32vw;animation-delay:-10s}
.auth-shell{position:relative;isolation:isolate;border-color:rgba(197,166,106,.42);border-radius:66px 28px 72px 30px;box-shadow:0 36px 120px rgba(0,12,35,.42),0 0 0 1px rgba(92,214,254,.12),inset 0 1px 0 rgba(255,255,255,.86)}
.auth-shell-wave{position:absolute;z-index:5;top:0;bottom:0;left:calc(46% - 64px);width:150px;height:100%;pointer-events:none;filter:drop-shadow(14px 0 26px rgba(0,23,54,.13))}.auth-shell-wave path{fill:rgba(246,251,255,.98)}
.auth-form-panel{position:relative;z-index:6;overflow-x:hidden;border-radius:52px 108px 108px 24px;box-shadow:18px 0 50px rgba(3,25,58,.08)}
.auth-form-wrap{position:relative;z-index:7}
.auth-form-waves{position:absolute;right:-210px;bottom:-205px;width:510px;height:510px;pointer-events:none;opacity:.72}.auth-form-waves i{position:absolute;inset:0;border:1px solid rgba(18,91,143,.11);border-radius:42% 58% 48% 52%}.auth-form-waves i:nth-child(2){inset:52px;border-color:rgba(197,166,106,.2);transform:rotate(18deg)}.auth-form-waves i:nth-child(3){inset:112px;border-color:rgba(49,188,236,.16);transform:rotate(-14deg)}
.auth-signature{display:flex;align-items:center;gap:.7rem;margin:-13px 0 22px;color:#67839b;font-size:.62rem;font-weight:750;letter-spacing:.08em;text-transform:uppercase}.auth-signature>span{width:32px;height:2px;background:linear-gradient(90deg,#b88c45,#e0c68e);border-radius:99px}.auth-signature p{margin:0}
.auth-visual{z-index:1;border-radius:18% 24px 24px 16%/12% 24px 24px 88%;box-shadow:inset 22px 0 50px rgba(0,12,35,.18)}
.auth-visual-ripples{position:absolute;z-index:-1;inset:0;overflow:hidden;pointer-events:none}.auth-visual-ripples i{position:absolute;top:42%;left:60%;border:1px solid rgba(95,215,255,.26);border-radius:50%;transform:translate(-50%,-50%)}.auth-visual-ripples i:nth-child(1){width:280px;height:280px}.auth-visual-ripples i:nth-child(2){width:430px;height:430px}.auth-visual-ripples i:nth-child(3){width:590px;height:590px}
.auth-visual-moment{position:absolute;z-index:3;top:92px;right:30px;width:min(250px,42%);display:grid;gap:.35rem;padding:14px 16px;color:#fff;background:rgba(4,25,58,.56);border:1px solid rgba(83,207,249,.3);border-radius:6px 20px 6px 20px;box-shadow:0 18px 42px rgba(0,12,35,.2);backdrop-filter:blur(14px)}.auth-visual-moment span{color:#59d4fc;font-size:.5rem;font-weight:900;letter-spacing:.16em}.auth-visual-moment strong{font-size:.66rem;line-height:1.5}
.auth-visual-top{left:84px}.auth-visual-copy{left:82px;border-color:rgba(216,186,126,.32);border-radius:8px 34px 8px 34px;background:linear-gradient(135deg,rgba(3,21,51,.84),rgba(7,43,75,.68))}.auth-visual-kicker{color:#e2c78f}.auth-visual-kicker i{background:#d4ad68;box-shadow:0 0 7px rgba(212,173,104,.65)}
.auth-shell--register .auth-visual{border-radius:17% 24px 24px 19%/82% 24px 24px 18%}.auth-shell--register .auth-shell-wave{transform:scaleY(-1)}.auth-shell--register .auth-visual-copy{left:76px}
@keyframes auth-drift{to{transform:translate3d(2.5vw,2vh,0) rotate(5deg)}}
@media(max-width:1050px){.auth-shell-wave{left:calc(52% - 75px)}.auth-visual-moment{right:22px}.auth-visual-copy,.auth-shell--register .auth-visual-copy{left:58px}.auth-visual-top{left:60px}}
@media(max-width:900px){.auth-shell{border-radius:30px}.auth-shell-wave{display:none}.auth-form-panel{border-radius:0 0 30px 30px}.auth-visual,.auth-shell--register .auth-visual{border-radius:28px 28px 46% 46%/28px 28px 22% 22%}.auth-visual-moment{top:24px;right:22px;width:250px}.auth-visual-copy,.auth-shell--register .auth-visual-copy{left:22px}.auth-visual-top{left:22px}.auth-visual-ripples i:nth-child(3){width:500px;height:500px}}
@media(max-width:640px){.auth-atmosphere{display:none}.auth-visual,.auth-shell--register .auth-visual{border-radius:0 0 46% 46%/0 0 18% 18%}.auth-visual-moment{display:none}.auth-signature{margin-bottom:17px;font-size:.52rem}.auth-visual-copy,.auth-shell--register .auth-visual-copy{left:16px}.auth-visual-top{left:16px}.auth-visual-ripples i:nth-child(1){width:170px;height:170px}.auth-visual-ripples i:nth-child(2){width:260px;height:260px}.auth-visual-ripples i:nth-child(3){display:none}}
@media(prefers-reduced-motion:reduce){.auth-atmosphere i{animation:none!important}}

/* Full-screen cinematic auth — intentionally not a split-card layout */
.auth-page{display:block;min-height:100svh;padding:0;background:#051a38}
.auth-shell,.auth-shell--register{position:relative;width:100%;min-height:100svh;display:block;padding:0;overflow:hidden;background:#061a39;border:0;border-radius:0;box-shadow:none}
.auth-visual,.auth-shell--register .auth-visual{position:absolute;z-index:1;inset:0;width:100%;height:100%;border-radius:0;box-shadow:none}
.auth-visual>img,.auth-shell--register .auth-visual>img{object-position:58% 58%;transform:scale(1.015)}
.auth-shell--register .auth-visual>img{object-position:34% 50%}
.auth-visual-shade{background:linear-gradient(90deg,rgba(3,17,40,.82) 0%,rgba(4,25,54,.42) 36%,rgba(3,18,43,.08) 66%,rgba(3,17,40,.32) 100%),linear-gradient(180deg,rgba(2,13,33,.28),transparent 45%,rgba(2,13,33,.68))}
.auth-shell--register .auth-visual-shade{background:linear-gradient(270deg,rgba(3,17,40,.82) 0%,rgba(4,25,54,.43) 37%,rgba(3,18,43,.06) 68%,rgba(3,17,40,.26) 100%),linear-gradient(180deg,rgba(2,13,33,.18),transparent 42%,rgba(2,13,33,.65))}
.auth-shell-wave{z-index:4;left:calc(5vw + 390px);width:330px;opacity:.96;filter:drop-shadow(25px 0 45px rgba(0,16,42,.24))}
.auth-shell--register .auth-shell-wave{right:calc(5vw + 460px);left:auto;transform:scale(-1,-1)}
.auth-form-panel{position:relative;z-index:6;width:min(520px,calc(100vw - 64px));min-height:auto;max-height:calc(100svh - 64px);margin:32px 0 32px 5vw;padding:clamp(30px,3.2vw,50px);overflow-y:auto;background:linear-gradient(155deg,rgba(255,255,255,.98),rgba(239,247,253,.97));border:1px solid rgba(255,255,255,.82);border-radius:38px 138px 68px 38px/38px 105px 82px 38px;box-shadow:0 35px 100px rgba(0,12,34,.36),inset 0 1px 0 #fff}
.auth-shell--register .auth-form-panel{width:min(620px,calc(100vw - 64px));margin-right:5vw;margin-left:auto;border-radius:138px 38px 38px 68px/105px 38px 38px 82px}
.auth-form-panel::before,.auth-form-panel::after{content:'';position:absolute;z-index:0;pointer-events:none;border:1px solid rgba(20,121,175,.11);border-radius:48% 52% 45% 55%}
.auth-form-panel::before{right:-210px;bottom:-220px;width:510px;height:510px}
.auth-form-panel::after{right:-125px;bottom:-145px;width:350px;height:350px;border-color:rgba(194,157,88,.22)}
.auth-shell--register .auth-form-panel::before{right:auto;left:-210px}.auth-shell--register .auth-form-panel::after{right:auto;left:-125px}
.auth-form-waves{display:none}
.auth-form-wrap{width:min(100%,470px);margin:auto}.auth-shell--register .auth-form-wrap{width:min(100%,520px)}
.auth-visual-top{z-index:4;top:32px;right:4vw;left:auto}.auth-shell--register .auth-visual-top{right:auto;left:4vw}
.auth-visual-moment{z-index:4;top:96px;right:4vw;width:270px}.auth-shell--register .auth-visual-moment{right:auto;left:4vw}
.auth-visual-copy,.auth-shell--register .auth-visual-copy{z-index:4;right:4vw;bottom:5vh;left:auto;width:min(570px,40vw);padding:28px 30px;border-radius:34px 8px 34px 8px}
.auth-shell--register .auth-visual-copy{right:auto;left:4vw;border-radius:8px 34px 8px 34px}
.auth-visual-copy h2,.auth-shell--register .auth-visual-copy h2{font-size:clamp(2.1rem,3.1vw,3.5rem)}
.auth-visual-ripples i{top:48%;left:70%}.auth-shell--register .auth-visual-ripples i{left:30%}

@media(max-width:1100px){.auth-form-panel{margin-left:28px}.auth-shell--register .auth-form-panel{margin-right:28px}.auth-shell-wave{left:385px}.auth-shell--register .auth-shell-wave{right:470px}.auth-visual-copy,.auth-shell--register .auth-visual-copy{width:38vw}.auth-visual-copy h2,.auth-shell--register .auth-visual-copy h2{font-size:2.2rem}}
@media(max-width:900px){.auth-page{background:#f2f7fb}.auth-shell,.auth-shell--register{display:flex;min-height:100svh;flex-direction:column;overflow:visible;background:#f2f7fb}.auth-shell-wave{display:none}.auth-visual,.auth-shell--register .auth-visual{position:relative;order:-1;inset:auto;width:100%;height:310px;border-radius:0 0 48% 48%/0 0 18% 18%}.auth-visual>img,.auth-shell--register .auth-visual>img{object-position:50% 58%}.auth-form-panel,.auth-shell--register .auth-form-panel{width:100%;max-height:none;margin:0;padding:34px clamp(24px,7vw,60px) 48px;overflow:visible;border:0;border-radius:0;background:#f7fbff;box-shadow:none}.auth-form-wrap,.auth-shell--register .auth-form-wrap{width:min(100%,560px)}.auth-visual-top,.auth-shell--register .auth-visual-top{top:22px;right:24px;left:24px}.auth-visual-moment{display:none}.auth-visual-copy,.auth-shell--register .auth-visual-copy{right:24px;bottom:20px;left:24px;width:auto;padding:18px 20px;border-radius:22px 6px 22px 6px}.auth-visual-copy h2,.auth-shell--register .auth-visual-copy h2{font-size:1.7rem}.auth-visual-copy p,.auth-visual-benefits{display:none}}
@media(max-width:640px){.auth-visual,.auth-shell--register .auth-visual{height:210px;border-radius:0 0 44% 44%/0 0 14% 14%}.auth-form-panel,.auth-shell--register .auth-form-panel{padding:28px 18px 40px}.auth-visual-top,.auth-shell--register .auth-visual-top{top:14px;right:16px;left:16px}.auth-visual-copy,.auth-shell--register .auth-visual-copy{right:16px;bottom:14px;left:16px;padding:12px 14px}.auth-visual-copy h2,.auth-shell--register .auth-visual-copy h2{font-size:1.3rem}.auth-visual-kicker{font-size:.48rem}.auth-brand img{width:168px}.auth-heading h1{font-size:2.45rem}}

/* Final composition: centered form, full background, waves at the outer edges */
@media(min-width:901px){
  .auth-shell::before,.auth-shell::after{content:'';position:absolute;z-index:3;top:-18%;width:34vw;height:136%;pointer-events:none;border:1px solid rgba(99,216,252,.22);border-radius:47% 53% 42% 58%/58% 43% 57% 42%;box-shadow:0 0 0 72px rgba(48,176,225,.035),0 0 0 145px rgba(194,157,88,.025)}
  .auth-shell::before{left:-24vw;transform:rotate(-7deg)}
  .auth-shell::after{right:-24vw;transform:rotate(7deg)}
  .auth-shell-wave{display:none}
  .auth-form-panel,.auth-shell--register .auth-form-panel{width:min(620px,calc(100vw - 80px));max-height:calc(100svh - 56px);margin:28px auto;padding:clamp(32px,3vw,48px);background:linear-gradient(155deg,rgba(255,255,255,.96),rgba(237,247,253,.93));border:1px solid rgba(255,255,255,.82);border-radius:105px 42px 105px 42px/64px 105px 64px 105px;box-shadow:0 38px 110px rgba(0,12,34,.46),inset 0 1px 0 #fff;backdrop-filter:blur(18px)}
  .auth-shell--register .auth-form-panel{width:min(670px,calc(100vw - 80px));border-radius:42px 105px 42px 105px/105px 64px 105px 64px}
  .auth-form-panel::before{right:-185px;bottom:-210px}.auth-form-panel::after{right:-112px;bottom:-138px}
  .auth-shell--register .auth-form-panel::before{right:auto;left:-185px}.auth-shell--register .auth-form-panel::after{right:auto;left:-112px}
  .auth-visual-shade,.auth-shell--register .auth-visual-shade{background:radial-gradient(circle at 50% 48%,rgba(3,18,43,.08),rgba(3,18,43,.34) 58%,rgba(2,14,35,.7) 100%),linear-gradient(180deg,rgba(3,17,40,.18),transparent 44%,rgba(3,17,40,.42))}
  .auth-visual-top,.auth-shell--register .auth-visual-top{top:34px;right:auto;left:3vw}
  .auth-visual-moment,.auth-shell--register .auth-visual-moment{top:34px;right:3vw;left:auto}
  .auth-visual-copy,.auth-shell--register .auth-visual-copy{display:none}
  .auth-visual-ripples i{top:50%;left:50%}.auth-shell--register .auth-visual-ripples i{left:50%}
  .auth-form-wrap,.auth-shell--register .auth-form-wrap{width:min(100%,520px)}
}
}
@media not all {
/* Superseded login-only composition. */
.auth-page:has(.auth-shell--login){display:block;min-height:100svh;padding:0;background:#f7faff}
.auth-shell--login{position:relative;box-sizing:border-box;width:100%;min-height:100svh;display:block;padding:0;overflow:hidden;background:#f7faff url('/assets/images/astra-login-background-exact-4k.png') center/cover no-repeat;border:0;border-radius:0;box-shadow:none;isolation:isolate}
.auth-shell--login::before,.auth-shell--login::after{display:none}
.auth-login-page-brand{position:absolute;z-index:10;top:clamp(28px,5.2vh,56px);left:clamp(36px,4.3vw,83px);display:block;width:clamp(170px,10.6vw,204px);line-height:0}.auth-login-page-brand img{display:block;width:100%;height:auto}
.auth-login-help{position:absolute;z-index:10;top:clamp(26px,4.8vh,52px);right:clamp(30px,3.8vw,72px);display:inline-flex;align-items:center;gap:10px;padding:13px 19px;color:#09234e;background:rgba(255,255,255,.78);border:1px solid rgba(60,116,190,.08);border-radius:16px;box-shadow:0 10px 30px rgba(30,77,139,.08);backdrop-filter:blur(18px);font-size:.73rem;font-weight:800;text-decoration:none}
.auth-shell--login .auth-form-panel{position:absolute;z-index:6;top:11.5svh;left:14.4vw;box-sizing:border-box;width:min(31.6vw,608px);height:82svh;max-height:none;display:flex;margin:0;padding:clamp(46px,5.6vh,66px) clamp(38px,3.2vw,61px);overflow:visible;background:rgba(255,255,255,.88);border:1px solid rgba(91,145,216,.13);border-radius:44px;box-shadow:0 30px 76px rgba(53,91,149,.14),inset 0 1px 0 rgba(255,255,255,.98);backdrop-filter:blur(20px)}
.auth-shell--login .auth-form-panel::before,.auth-shell--login .auth-form-panel::after{display:none}
.auth-shell--login .auth-back,.auth-shell--login .auth-brand{display:none}
.auth-shell--login .auth-form-wrap{position:relative;z-index:2;width:100%;max-width:none;margin:auto 0}
.auth-shell--login .auth-heading{margin-bottom:clamp(24px,3.2vh,34px)}
.auth-shell--login .auth-eyebrow{display:inline-flex;align-items:center;padding:8px 13px;margin-bottom:clamp(20px,2.7vh,29px);color:#126bdc;background:#edf5ff;border-radius:999px;font-size:.64rem;font-weight:850;letter-spacing:.1em}.auth-shell--login .auth-eyebrow::before{content:'♔';margin-right:7px;color:#1e73e7;font-size:.84rem}
.auth-shell--login .auth-heading h1{max-width:430px;margin:0;font-size:clamp(3rem,3.2vw,3.85rem);line-height:1.01;letter-spacing:-.045em}.auth-title-dot{color:#1878ec}.auth-shell--login .auth-heading p{max-width:390px;margin-top:17px;color:#697990;font-size:clamp(.86rem,.8vw,.98rem);line-height:1.55}
.auth-shell--login .auth-form{gap:clamp(15px,1.8vh,19px)}.auth-shell--login .auth-field{gap:9px;color:#162b50;font-size:.73rem}.auth-shell--login .auth-input input{height:58px;background:rgba(255,255,255,.72);border-color:#cfdbeb;border-radius:12px}.auth-shell--login .auth-submit{min-height:58px;margin-top:3px;background:linear-gradient(180deg,#4c94ef,#0f55bd);border-radius:13px;box-shadow:0 13px 28px rgba(24,92,192,.24)}
.auth-shell--login .auth-options{margin-top:2px}.auth-shell--login .auth-divider--login{margin:clamp(20px,2.4vh,27px) 0 clamp(17px,2vh,21px)}.auth-shell--login .auth-google{min-height:56px;border-radius:13px}.auth-shell--login .auth-switch{margin-top:clamp(23px,2.8vh,31px)}.auth-shell--login .auth-switch a{display:inline-flex;align-items:center;gap:7px;color:#1370dc}
.auth-shell--login .auth-visual{position:absolute;z-index:3;top:11.5svh;right:5.6vw;bottom:7.4svh;left:39.9vw;min-height:0;overflow:visible;background:transparent;border:0;border-radius:0;box-shadow:none;isolation:auto}
.auth-shell--login .auth-visual>img{display:none}.auth-shell--login .auth-visual-shade,.auth-shell--login .auth-visual-top{display:none}
.auth-login-quote{position:absolute;z-index:4;top:7.6vh;right:6.2vw;display:grid;gap:10px;width:180px;padding:24px 25px;color:#0a234d;background:rgba(255,255,255,.72);border:1px solid rgba(255,255,255,.76);border-radius:22px;box-shadow:0 18px 45px rgba(35,82,143,.12);backdrop-filter:blur(18px)}.auth-login-quote>span{color:#d5a52d;font-size:1.05rem}.auth-login-quote strong{font-family:'Playfair Display',Georgia,serif;font-size:1.18rem;line-height:1.34}.auth-login-quote i{width:26px;height:2px;background:#42b668}
.auth-shell--login .auth-visual-copy{position:absolute;z-index:4;right:7.2vw;bottom:2.2vh;left:3.2vw;display:block;padding:27px 30px;color:#fff;background:linear-gradient(90deg,rgba(62,105,175,.71),rgba(40,105,199,.68));border:1px solid rgba(255,255,255,.25);border-radius:19px;box-shadow:0 20px 44px rgba(29,71,135,.16);backdrop-filter:blur(18px)}
.auth-shell--login .auth-visual-kicker,.auth-shell--login .auth-visual-copy h2,.auth-shell--login .auth-visual-copy>p{display:none}.auth-shell--login .auth-visual-benefits{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin:0;padding:0;border:0}.auth-shell--login .auth-visual-benefits>span{display:grid;grid-template-columns:34px 1fr;align-items:start;gap:13px;color:#fff;text-align:left}.auth-shell--login .auth-visual-benefits>span>svg{margin-top:2px;color:#fff;stroke-width:1.5}.auth-shell--login .auth-visual-benefits>span>span{display:grid;gap:5px}.auth-shell--login .auth-visual-benefits strong{font-size:.69rem;line-height:1.35}.auth-shell--login .auth-visual-benefits small{color:rgba(255,255,255,.84);font-size:.6rem;font-weight:500;line-height:1.5}
@media(max-width:1200px){.auth-shell--login .auth-form-panel{left:7vw;width:38vw;padding-inline:40px}.auth-shell--login .auth-visual{left:39vw;right:3vw}.auth-shell--login .auth-visual-copy{right:3vw;left:2vw}.auth-login-quote{right:4vw}}
@media(max-width:900px){.auth-shell--login{min-height:100svh;overflow:auto;background-position:60% center}.auth-login-page-brand{top:24px;left:25px;width:160px}.auth-login-help{top:20px;right:22px}.auth-shell--login .auth-form-panel{position:relative;top:auto;left:auto;width:min(600px,calc(100% - 40px));height:auto;margin:100px auto 26px;padding:44px;border-radius:32px}.auth-shell--login .auth-visual{position:relative;top:auto;right:auto;bottom:auto;left:auto;width:min(760px,calc(100% - 40px));height:430px;margin:0 auto 30px;overflow:hidden;background:rgba(255,255,255,.16);border-radius:70px 28px}.auth-shell--login .auth-visual>img{display:block;z-index:-1;object-position:74% 55%;transform:scale(1.01)}.auth-shell--login .auth-visual-shade{display:block;background:linear-gradient(180deg,transparent 45%,rgba(31,81,161,.16))}.auth-login-quote{top:28px;right:28px}.auth-shell--login .auth-visual-copy{right:22px;bottom:22px;left:22px}}
@media(max-width:640px){.auth-shell--login{background-position:68% center}.auth-login-page-brand{top:19px;left:18px;width:140px}.auth-login-help{top:14px;right:15px;padding:11px}.auth-login-help{font-size:0}.auth-login-help svg{width:19px;height:19px}.auth-shell--login .auth-form-panel{width:calc(100% - 24px);margin-top:78px;padding:34px 22px 38px;border-radius:28px}.auth-shell--login .auth-heading h1{font-size:2.75rem}.auth-shell--login .auth-visual{width:calc(100% - 24px);height:330px;border-radius:42px 20px}.auth-login-quote{display:none}.auth-shell--login .auth-visual-copy{right:12px;bottom:12px;left:12px;padding:16px}.auth-shell--login .auth-visual-benefits{gap:8px}.auth-shell--login .auth-visual-benefits>span{grid-template-columns:1fr;gap:5px;text-align:center}.auth-shell--login .auth-visual-benefits>span>svg{width:20px;height:20px;margin:auto}.auth-shell--login .auth-visual-benefits strong{font-size:.55rem}.auth-shell--login .auth-visual-benefits small{display:none}}
}

/* Exact shared login/register visual system from the supplied 4K design. */
.auth-page{display:block;min-height:100svh;padding:0;background:#f7faff}
.auth-shell,.auth-shell--login,.auth-shell--register{position:relative;width:100%;min-height:100svh;display:block;padding:0;overflow:hidden;background:#f7faff url('/assets/images/astra-login-clean-background-exact-4k.png') center/cover no-repeat;border:0;border-radius:0;box-shadow:none;isolation:isolate}
.auth-shell::before,.auth-shell::after{display:none}
.auth-form-panel,.auth-shell--register .auth-form-panel{position:absolute;z-index:6;inset:0 auto 0 0;box-sizing:border-box;width:51.5vw;min-height:100svh;max-height:100svh;display:block;margin:0;padding:0;overflow-y:auto;overflow-x:hidden;background:rgba(255,255,255,.975);border:0;border-radius:0 44% 38% 0/0 18% 82% 0;box-shadow:18px 0 60px rgba(69,117,180,.06);backdrop-filter:blur(8px);scrollbar-width:thin;scrollbar-color:#bdd9fb transparent}
.auth-shell--login .auth-form-panel,.auth-shell--register .auth-form-panel{background:transparent;border-radius:0;box-shadow:none;backdrop-filter:none}
.auth-form-panel::before,.auth-form-panel::after{display:none}
.auth-form-wrap,.auth-shell--register .auth-form-wrap{position:relative;z-index:2;width:min(29.5vw,590px);margin:0 0 0 10.8vw;padding:7.4svh 0 5svh}
.auth-back,.auth-eyebrow{display:none}
.auth-brand{display:block;width:clamp(205px,12.5vw,250px);margin:0 auto clamp(34px,5.4vh,58px);line-height:0}.auth-brand img{display:block;width:100%;height:auto}
.auth-heading{margin-bottom:clamp(22px,3.2vh,35px)}.auth-heading h1{max-width:620px;margin:0;color:#08245a;font-family:'Playfair Display',Georgia,serif;font-size:clamp(3rem,3.5vw,4.35rem);font-weight:500;line-height:1.02;letter-spacing:-.045em}.auth-title-accent{color:#2485ed}.auth-heading p{max-width:570px;margin:18px 0 0;color:#8092af;font-size:clamp(.88rem,1vw,1.05rem);line-height:1.65}
.auth-form{display:grid;gap:14px}.auth-name-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.auth-field{display:block}.auth-field>span:first-child{position:absolute;width:1px;height:1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}.auth-input input{height:62px;padding-left:52px;color:#0b2c63;background:rgba(255,255,255,.88);border:1px solid #cad8eb;border-radius:14px;font-size:.9rem;font-weight:550;box-shadow:inset 0 1px 0 rgba(255,255,255,.95)}.auth-input>svg{left:18px;color:#3566ad}.auth-input input::placeholder{color:#8da0bf}.auth-input input:focus{border-color:#2585ed;box-shadow:0 0 0 4px rgba(37,133,237,.12)}.auth-password-toggle{right:11px;color:#4c6f9e}
.auth-options{margin:2px 0 0;color:#647896}.auth-options>a{color:#166fd2}.auth-options input,.auth-terms input{accent-color:#1d73d6}.auth-terms{color:#6b7d99;line-height:1.45}.auth-terms a{color:#145fae}
.auth-submit{min-height:60px;margin-top:2px;background:linear-gradient(180deg,#4c96f4,#1260c9);border:0;border-radius:14px;box-shadow:0 14px 28px rgba(31,102,200,.22)}.auth-divider{margin:18px 0}.auth-google{min-height:58px;border-color:#d6dfeb;border-radius:14px;background:rgba(255,255,255,.9)}.auth-switch{margin:22px 0 0;color:#74859f}.auth-switch a{display:inline-flex;align-items:center;gap:7px;color:#1672dc}
.auth-visual,.auth-shell--register .auth-visual{display:none}
.auth-shell--register .auth-form-wrap{padding-top:20.5svh;padding-bottom:3.5svh}.auth-shell--register .auth-brand{width:clamp(180px,10.5vw,220px);margin-bottom:clamp(20px,2.8vh,30px)}.auth-shell--register .auth-heading{margin-bottom:18px}.auth-shell--register .auth-heading h1{font-size:clamp(2.65rem,3vw,3.65rem)}.auth-shell--register .auth-heading p{margin-top:11px}.auth-shell--register .auth-form{gap:11px}.auth-shell--register .auth-input input{height:54px}.auth-shell--register .auth-submit{min-height:54px}.auth-shell--register .auth-divider{margin:12px 0}.auth-shell--register .auth-google{min-height:52px}.auth-shell--register .auth-switch{margin-top:14px}
.auth-shell--login .auth-form-wrap{padding-top:20.5svh;padding-bottom:4svh}.auth-shell--login .auth-brand{margin-bottom:clamp(48px,6.2vh,67px)}.auth-shell--login .auth-heading{margin-bottom:clamp(28px,3.5vh,38px)}
@media(max-width:1100px){.auth-form-panel,.auth-shell--register .auth-form-panel{width:58vw;border-radius:0 34% 30% 0/0 14% 86% 0}.auth-form-wrap,.auth-shell--register .auth-form-wrap{width:min(38vw,560px);margin-left:8vw}.auth-heading h1{font-size:3.2rem}}
@media(max-width:760px){.auth-shell,.auth-shell--login,.auth-shell--register{min-height:100svh;overflow:auto;background-position:70% center;background-attachment:fixed}.auth-form-panel,.auth-shell--register .auth-form-panel{position:relative;width:calc(100% - 24px);min-height:auto;max-height:none;margin:12px;padding:0;overflow:visible;background:rgba(255,255,255,.94);border-radius:34px;box-shadow:0 24px 60px rgba(39,87,151,.13);backdrop-filter:blur(18px)}.auth-form-wrap,.auth-shell--register .auth-form-wrap{width:auto;margin:0;padding:34px 24px 38px}.auth-brand,.auth-shell--register .auth-brand{width:180px;margin-bottom:30px}.auth-heading h1,.auth-shell--register .auth-heading h1{font-size:2.75rem}.auth-heading p{font-size:.88rem}.auth-input input,.auth-shell--register .auth-input input{height:56px}.auth-name-grid{grid-template-columns:1fr}.auth-shell--register .auth-form{gap:12px}}
@media(max-width:390px){.auth-form-panel,.auth-shell--register .auth-form-panel{width:calc(100% - 16px);margin:8px;border-radius:26px}.auth-form-wrap,.auth-shell--register .auth-form-wrap{padding:28px 17px 32px}.auth-brand,.auth-shell--register .auth-brand{width:160px;margin-bottom:24px}.auth-heading h1,.auth-shell--register .auth-heading h1{font-size:2.35rem}.auth-heading p{line-height:1.5}.auth-input input,.auth-shell--register .auth-input input{height:54px}.auth-options{gap:10px;font-size:.65rem}.auth-switch{font-size:.68rem}}
</style>
