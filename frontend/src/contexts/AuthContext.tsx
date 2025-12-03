import React, { createContext, useContext, useEffect, useState, useCallback, useRef } from 'react';
import type { User } from '../types';
import { apiClient } from '../api/client';
import { tokenManager } from '../utils/tokenManager';

interface AuthContextType {
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (email: string, password: string, firstName: string, lastName: string, wantToBeOrganizer?: boolean) => Promise<void>;
  logout: () => void;
  isLoading: boolean;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const refreshTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const fetchUserAttempts = useRef(0);

  // Schedule automatic token refresh before expiration
  const scheduleTokenRefresh = useCallback(() => {
    // Clear any existing timeout
    if (refreshTimeoutRef.current) {
      clearTimeout(refreshTimeoutRef.current);
    }

    const timeUntilExpiration = tokenManager.getTimeUntilExpiration();
    if (!timeUntilExpiration) return;

    // Refresh 5 minutes before expiration (or halfway through if token expires sooner)
    const refreshTime = Math.max(timeUntilExpiration - 300, timeUntilExpiration / 2);
    
    if (refreshTime > 0) {
      refreshTimeoutRef.current = setTimeout(async () => {
        try {
          const response = await apiClient.post<{ token?: string; refresh_token?: string }>('/auth/refresh', null);
          
          if (response.token) {
            localStorage.setItem('auth_token', response.token);
            if (response.refresh_token) {
              localStorage.setItem('refresh_token', response.refresh_token);
            }
            scheduleTokenRefresh(); // Schedule next refresh
          } else {
            console.warn('Token refresh returned no access token');
          }
        } catch (error) {
          console.error('Auto token refresh failed:', error);
          // Let the user continue, interceptor will handle 401 on next request
        }
      }, refreshTime * 1000);
    }
  }, []);

  const fetchCurrentUser = useCallback(async () => {
    try {
      const token = localStorage.getItem('auth_token');
      
      // Don't make API call if no token exists
      if (!token) {
        setUser(null);
        setIsAuthenticated(false);
        return;
      }

      // Check if token is expired and we have a refresh token
      if (tokenManager.isAccessTokenExpired() && tokenManager.getRefreshToken()) {
        try {
          // Try to refresh the token before fetching user
          const response = await apiClient.post<{ token?: string; refresh_token?: string }>('/auth/refresh', null);
          
          if (response.token && response.refresh_token) {
            localStorage.setItem('auth_token', response.token);
            localStorage.setItem('refresh_token', response.refresh_token);
          }
        } catch (refreshError: any) {
          // Only clear tokens if it's an authentication error (401)
          // Don't clear on network errors (timeout, 500, etc.)
          if (refreshError?.response?.status === 401) {
            console.warn('Refresh token invalid or expired, logging out');
            localStorage.removeItem('auth_token');
            localStorage.removeItem('refresh_token');
            setUser(null);
            setIsAuthenticated(false);
          } else {
            console.error('Refresh failed with network error:', refreshError);
            // Keep tokens, let user try again
            setUser(null);
            setIsAuthenticated(false);
          }
          return;
        }
      }

      const raw = await apiClient.get<any>('/auth/me');
      if (raw && (raw as any).error) {
        throw new Error('Unauthenticated');
      }
      const userData = raw as User;
      setUser(userData);
      setIsAuthenticated(true);
      
      // Schedule automatic token refresh
      scheduleTokenRefresh();
    } catch (error: any) {
      // Only clear tokens if it's an authentication error (401)
      // Don't clear on network errors, CORS errors, timeouts, etc.
      const isAuthError = error?.response?.status === 401;
      
      if (isAuthError) {
        console.warn('Authentication failed, clearing tokens');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('refresh_token');
        setUser(null);
        setIsAuthenticated(false);
      } else {
        // Network error - retry once after delay if this is first attempt
        console.error('Failed to fetch user (keeping tokens):', error?.message || error);
        
        if (fetchUserAttempts.current === 0 && localStorage.getItem('auth_token')) {
          fetchUserAttempts.current++;
          console.log('Retrying fetchCurrentUser in 2 seconds...');
          setTimeout(() => {
            fetchCurrentUser();
          }, 2000);
        } else {
          setUser(null);
          setIsAuthenticated(false);
        }
      }
    }
  }, [scheduleTokenRefresh]);

  useEffect(() => {
    const initAuth = async () => {
      setIsLoading(true);
      fetchUserAttempts.current = 0; // Reset attempt counter
      await fetchCurrentUser();
      setIsLoading(false);
    };
    initAuth();
  }, [fetchCurrentUser]);

  const login = async (email: string, password: string) => {
    setIsLoading(true);
    try {
      // Clear any stale tokens before login
      localStorage.removeItem('auth_token');
      localStorage.removeItem('refresh_token');

      const response = await apiClient.post<{ token?: string; refresh_token?: string; user?: User; error?: any }>('/auth/login', { email, password });

      if (response.error) {
        throw new Error(response.error?.message || 'Invalid credentials');
      }

      const token = response.token;
      const refresh = response.refresh_token;

      if (!token) {
        throw new Error('Invalid credentials');
      }

      // Store access token (refresh token is stored as httpOnly cookie by backend)
      localStorage.setItem('auth_token', token);
      if (refresh) {
        localStorage.setItem('refresh_token', refresh);
      }

      if (response.user) {
        setUser(response.user as User);
        setIsAuthenticated(true);
        // Schedule automatic token refresh after login
        scheduleTokenRefresh();
      } else {
        await fetchCurrentUser();
      }

    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    // Clear refresh timeout
    if (refreshTimeoutRef.current) {
      clearTimeout(refreshTimeoutRef.current);
    }
    
    // Remove both tokens
    localStorage.removeItem('auth_token');
    localStorage.removeItem('refresh_token');
    setUser(null);
    setIsAuthenticated(false);
  };

  const register = async (email: string, password: string, firstName: string, lastName: string, wantToBeOrganizer: boolean = false) => {
    setIsLoading(true);
    try {
      const response = await apiClient.post<{ token?: string; refresh_token?: string; user?: User; error?: any }>('/auth/register', {
        email,
        password,
        firstName,
        lastName,
        wantToBeOrganizer,
      });

      if (response.error) {
        throw new Error(response.error?.message || 'Registration failed');
      }

      const token = response.token;
      const refresh = response.refresh_token;

      if (!token) {
        throw new Error('Registration failed');
      }

      // Store access token (refresh token is stored as httpOnly cookie by backend)
      localStorage.setItem('auth_token', token);
      if (refresh) {
        localStorage.setItem('refresh_token', refresh);
      }

      if (response.user) {
        setUser(response.user as User);
        setIsAuthenticated(true);
        // Schedule automatic token refresh after registration
        scheduleTokenRefresh();
      } else {
        await fetchCurrentUser();
      }
    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const value = {
    user,
    login,
    register,
    logout,
    isLoading,
    isAuthenticated,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
