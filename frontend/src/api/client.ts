import axios from 'axios';
import type { AxiosInstance, AxiosRequestConfig, AxiosError } from 'axios';

export class ApiClient {
  private client: AxiosInstance;
  private isRefreshing = false;
  private refreshSubscribers: ((token: string) => void)[] = [];

  constructor(baseURL: string = 'https://127.0.0.1:8000/api') {
    this.client = axios.create({
      baseURL,
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
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean };

        // If error is 401 and we haven't retried yet
        if (error.response?.status === 401 && !originalRequest._retry) {
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
            const refreshToken = localStorage.getItem('refresh_token');
            
            if (!refreshToken) {
              throw new Error('No refresh token available');
            }

            // Call refresh endpoint
            const response = await axios.post(
              'https://127.0.0.1:8000/api/token/refresh',
              { refresh_token: refreshToken }
            );

            const { token, refresh_token } = response.data;

            // Store new tokens
            localStorage.setItem('auth_token', token);
            localStorage.setItem('refresh_token', refresh_token);

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
            // Refresh failed - logout user
            localStorage.removeItem('auth_token');
            localStorage.removeItem('refresh_token');
            window.location.href = '/login';
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

export const apiClient = new ApiClient();