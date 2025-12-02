import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ticketsApi } from '../api/tickets';
import type { PurchaseTicketData } from '../types';
import { notifications } from '@mantine/notifications';

export const useMyTickets = () => {
  return useQuery({
    queryKey: ['tickets', 'my'],
    queryFn: ticketsApi.getMyTickets,
    select: (data) => data.tickets || [],
    retry: 1, // Retry once on failure
  });
};

export const usePurchaseTicket = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: PurchaseTicketData) => ticketsApi.purchase(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tickets'] });
      queryClient.invalidateQueries({ queryKey: ['events'] });
      notifications.show({
        title: 'Success',
        message: 'Tickets purchased successfully!',
        color: 'green',
      });
    },
    onError: (error: any) => {
      notifications.show({
        title: 'Error',
        message: error.response?.data?.message || 'Failed to purchase tickets',
        color: 'red',
      });
    },
  });
};

export const useTicketAvailability = (eventId: string, ticketTypeId: string, quantity: number) => {
  return useQuery({
    queryKey: ['tickets', 'availability', eventId, ticketTypeId, quantity],
    queryFn: () => ticketsApi.checkAvailability(eventId, ticketTypeId, quantity),
    enabled: !!eventId && !!ticketTypeId && quantity > 0,
    refetchInterval: 10000,
  });
};