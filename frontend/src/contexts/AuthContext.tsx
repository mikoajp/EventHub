import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import type { User } from '../types';
import { apiClient } from '../api/client';

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

  const fetchCurrentUser = useCallback(async () => {
    try {
      const token = localStorage.getItem('auth_token');
      
      // Don't make API call if no token exists
      if (!token) {
        setUser(null);
        setIsAuthenticated(false);
        return;
      }

      const raw = await apiClient.get<any>('/auth/me');
      if (raw && (raw as any).error) {
        throw new Error('Unauthenticated');
      }
      const userData = raw as User;
      setUser(userData);
      setIsAuthenticated(true);
    } catch (error) {
      // If refresh token exists, the interceptor will try to refresh
      // If that fails, it will clear tokens and redirect
      localStorage.removeItem('auth_token');
      localStorage.removeItem('refresh_token');
      setUser(null);
      setIsAuthenticated(false);
    }
  }, []);

  useEffect(() => {
    const initAuth = async () => {
      setIsLoading(true);
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
