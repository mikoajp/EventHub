import type { Event, CreateEventData, EventStatistics, EventsResponse, EventFilters, FilterOptions } from '../types';
import { apiClient } from './client';

export const eventsApi = {
  getAll: (filters?: EventFilters): Promise<EventsResponse> => {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          if (Array.isArray(value)) {
            value.forEach(v => params.append(`${key}[]`, v.toString()));
          } else {
            params.append(key, value.toString());
          }
        }
      });
    }
    
    return apiClient.get(`/events?${params.toString()}`);
  },

  getById: (id: string): Promise<Event> =>
    apiClient.get(`/events/${id}`),

  create: (data: CreateEventData): Promise<Event> =>
    apiClient.post('/events', data),

  update: (id: string, data: Partial<Event>): Promise<Event> =>
    apiClient.patch(`/events/${id}`, data),

  delete: (id: string): Promise<void> =>
    apiClient.delete(`/events/${id}`),

  publish: (id: string): Promise<void> =>
    apiClient.post(`/events/${id}/publish`),

  cancel: (id: string, reason: string): Promise<void> =>
    apiClient.post(`/events/${id}/cancel`, { reason }),

  getStatistics: (id: string, from?: string, to?: string): Promise<EventStatistics> =>
    apiClient.get(`/events/${id}/statistics`, {
      params: { from, to }
    }),

  getFilterOptions: (): Promise<FilterOptions> =>
    apiClient.get('/events/filters/options'),
};
