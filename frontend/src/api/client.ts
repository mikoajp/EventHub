import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosError } from 'axios';

export class ApiClient {
  private client: AxiosInstance;
  private baseURL: string;
  private isRefreshing = false;
  private refreshSubscribers: ((token: string) => void)[] = [];

  constructor(baseURL: string = import.meta.env.VITE_API_URL || 'http://localhost:8001/api') {
    const normalizedBase = baseURL.endsWith('/api') ? baseURL : `${baseURL.replace(/\/$/, '')}/api`;
    this.baseURL = normalizedBase;
    
    this.client = axios.create({
      baseURL: normalizedBase,
      withCredentials: true,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    this.setupInterceptors();
  }

  private setupInterceptors() {
    // Request interceptor - add auth token
    this.client.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor - handle token refresh
    this.client.interceptors.response.use(
      (response) => {
        if (typeof response.data === 'string') {
          // Strip leading HTML warnings before JSON
          const match = response.data.match(/\{[\s\S]*\}$/);
          if (match) {
            try { response.data = JSON.parse(match[0]); } catch { /* ignore parse error */ }
          }
        }
        return response;
      },
      async (error: AxiosError) => {
        const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean };

        // If error is 401 and we haven't retried yet
        const reqUrl = (originalRequest.url || '').toString();
        const isAuthRoute = /\/auth\/(login|register|me)/.test(reqUrl);
        
        // Only try to refresh if we have a refresh token and it's not an auth route
        const hasRefreshToken = !!localStorage.getItem('refresh_token');
        
        if (error.response?.status === 401 && !originalRequest._retry && !isAuthRoute && hasRefreshToken) {
          if (this.isRefreshing) {
            // If already refreshing, wait for new token
            return new Promise((resolve) => {
              this.refreshSubscribers.push((token: string) => {
                if (originalRequest.headers) {
                  originalRequest.headers.Authorization = `Bearer ${token}`;
                }
                resolve(this.client(originalRequest));
              });
            });
          }

          originalRequest._retry = true;
          this.isRefreshing = true;

          try {
            // Call refresh endpoint - uses HttpOnly cookie automatically via withCredentials
            const response = await axios.post(
              `${this.baseURL}/auth/refresh`,
              null,
              { withCredentials: true }
            );

            const token = response.data.token;
            const refresh_token = response.data.refresh_token;
            if (!token) {
              throw new Error('Refresh failed - no token in response');
            }

            // Store new tokens
            localStorage.setItem('auth_token', token);
            if (refresh_token) {
              localStorage.setItem('refresh_token', refresh_token);
            }

            // Update authorization header
            if (originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${token}`;
            }

            // Notify all subscribers
            this.refreshSubscribers.forEach((callback) => callback(token));
            this.refreshSubscribers = [];

            // Retry original request
            return this.client(originalRequest);
          } catch (refreshError) {
            // Refresh failed - clear tokens but DON'T redirect
            // Let the component handle the redirect if needed
            localStorage.removeItem('auth_token');
            localStorage.removeItem('refresh_token');
            return Promise.reject(refreshError);
          } finally {
            this.isRefreshing = false;
          }
        }

        return Promise.reject(error);
      }
    );
  }

  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.client.get(url, config);
    return response.data;
  }

  async post<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.client.post(url, data, config);
    return response.data;
  }

  async put<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.client.put(url, data, config);
    return response.data;
  }

  async patch<T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.client.patch(url, data, config);
    return response.data;
  }

  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.client.delete(url, config);
    return response.data;
  }
}

export const apiClient = new ApiClient(import.meta.env.VITE_API_URL);
