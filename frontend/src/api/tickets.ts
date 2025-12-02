import type { Ticket, PurchaseTicketData } from '../types';
import { apiClient } from './client';

export const ticketsApi = {
  purchase: (data: PurchaseTicketData): Promise<{ ticketIds: string[] }> =>
    apiClient.post('/tickets/purchase', data),

  getMyTickets: (): Promise<{ tickets: Ticket[] }> =>
    apiClient.get('/tickets/my'),

  getById: (id: string): Promise<Ticket> =>
    apiClient.get(`/tickets/${id}`),

  checkAvailability: (eventId: string, ticketTypeId: string, quantity: number): Promise<{ available: boolean, quantity: number }> =>
    apiClient.get(`/tickets/availability`, {
      params: { eventId, ticketTypeId, quantity }
    }),
};