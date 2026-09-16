/**
 * Pinia authentication store for the current user and Sanctum token.
 * Views use these actions instead of duplicating login and session code.
 */
import { defineStore } from 'pinia'
import api from '../lib/api'

export const useAuthStore=defineStore('auth',{
  state:()=>({user:JSON.parse(localStorage.getItem('astra_user')||'null'),loading:false,error:''}),
  getters:{isAuthenticated:state=>Boolean(state.user)},
  actions:{
    /** Saves fresh user data so every avatar, header, and workspace updates immediately. */
    updateUser(user){this.user=user;localStorage.setItem('astra_user',JSON.stringify(user))},
    /** Sends credentials to an auth endpoint and stores the returned bearer token. */
    async authenticate(endpoint,payload){this.loading=true;this.error='';try{const {data}=await api.post(endpoint,payload);this.updateUser(data.user);localStorage.setItem('astra_token',data.token);return data.user}catch(error){this.error=error.response?.data?.message||'Une erreur est survenue.';throw error}finally{this.loading=false}},
    /** Authenticates an existing account. */
    login(payload){return this.authenticate('/login',payload)},
    /** Registers a public client; no role is ever sent by the browser. */
    register(payload){
      const {first_name,last_name,email,phone,password,password_confirmation}=payload
      return this.authenticate('/register',{first_name,last_name,email,phone,password,password_confirmation})
    },
    /** Validates a stored token with Laravel and refreshes the current user. */
    async restore(){if(!localStorage.getItem('astra_token'))return;try{const {data}=await api.get('/me');this.updateUser(data.user)}catch{this.user=null}},
    /** Revokes the current token and clears local authentication state. */
    async logout(){try{await api.post('/logout')}finally{this.user=null;localStorage.removeItem('astra_token');localStorage.removeItem('astra_user')}}
  }
})
