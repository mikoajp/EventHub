import type { Event, CreateEventData, EventStatistics } from '../types';
import { apiClient } from './client';

export const eventsApi = {
  getAll: (): Promise<{ 'hydra:member': Event[] }> =>
    apiClient.get('/events'),

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
};
