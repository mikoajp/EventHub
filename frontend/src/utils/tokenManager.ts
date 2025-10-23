/**
 * Token Manager Utility
 * 
 * Provides centralized management of authentication tokens
 */

const TOKEN_KEY = 'auth_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

export const tokenManager = {
  /**
   * Get the current access token
   */
  getAccessToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  },

  /**
   * Get the current refresh token
   */
  getRefreshToken(): string | null {
    return localStorage.getItem(REFRESH_TOKEN_KEY);
  },

  /**
   * Store both access and refresh tokens
   */
  setTokens(accessToken: string, refreshToken: string): void {
    localStorage.setItem(TOKEN_KEY, accessToken);
    localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
  },

  /**
   * Update only the access token (used after refresh)
   */
  setAccessToken(accessToken: string): void {
    localStorage.setItem(TOKEN_KEY, accessToken);
  },

  /**
   * Clear all tokens (logout)
   */
  clearTokens(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(REFRESH_TOKEN_KEY);
  },

  /**
   * Check if user has valid tokens
   */
  hasTokens(): boolean {
    return !!(this.getAccessToken() || this.getRefreshToken());
  },

  /**
   * Parse JWT token to get expiration time
   */
  getTokenExpiration(token: string): number | null {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp ? payload.exp * 1000 : null; // Convert to milliseconds
    } catch {
      return null;
    }
  },

  /**
   * Check if access token is expired
   */
  isAccessTokenExpired(): boolean {
    const token = this.getAccessToken();
    if (!token) return true;

    const expiration = this.getTokenExpiration(token);
    if (!expiration) return true;

    // Consider token expired if less than 60 seconds remaining
    return Date.now() >= expiration - 60000;
  },

  /**
   * Get time until token expiration (in seconds)
   */
  getTimeUntilExpiration(): number | null {
    const token = this.getAccessToken();
    if (!token) return null;

    const expiration = this.getTokenExpiration(token);
    if (!expiration) return null;

    const timeLeft = Math.floor((expiration - Date.now()) / 1000);
    return timeLeft > 0 ? timeLeft : 0;
  },
};
