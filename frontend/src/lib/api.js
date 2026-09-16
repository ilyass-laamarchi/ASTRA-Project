/**
 * Shared Axios client for every Vue-to-Laravel request.
 * It adds Sanctum bearer tokens and removes expired local sessions on HTTP 401.
 */
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
        Accept: 'application/json'
    }
})

// Attach the current bearer token just before each request is sent.
api.interceptors.request.use(config => {
    const token = localStorage.getItem('astra_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Unwrap Laravel resource responses while preserving ordinary Axios responses.
api.interceptors.response.use(
    response => {
        if (response.data && response.data.data !== undefined) {
            return response.data
        }
        return response
    },
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('astra_token')
            localStorage.removeItem('astra_user')
        }
        return Promise.reject(error)
    }
)

export default api
