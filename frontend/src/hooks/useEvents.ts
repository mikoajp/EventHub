import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { eventsApi } from '../api/events';
import { type CreateEventData } from '../types';
import { notifications } from '@mantine/notifications';

export const useEvents = () => {
  return useQuery({
    queryKey: ['events'],
    queryFn: eventsApi.getAll,
    select: (data) => data['hydra:member'],
  });
};

export const useEvent = (id: string) => {
  return useQuery({
    queryKey: ['events', id],
    queryFn: () => eventsApi.getById(id),
    enabled: !!id,
  });
};

export const useCreateEvent = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateEventData) => eventsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
      notifications.show({
        title: 'Success',
        message: 'Event created successfully!',
        color: 'green',
      });
    },
    onError: (error: any) => {
      notifications.show({
        title: 'Error',
        message: error.response?.data?.message || 'Failed to create event',
        color: 'red',
      });
    },
  });
};

export const usePublishEvent = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => eventsApi.publish(id),
    onSuccess: (_, id) => {
      queryClient.invalidateQueries({ queryKey: ['events'] });
      queryClient.invalidateQueries({ queryKey: ['events', id] });
      notifications.show({
        title: 'Success',
        message: 'Event published successfully!',
        color: 'green',
      });
    },
    onError: (error: any) => {
      notifications.show({
        title: 'Error',
        message: error.response?.data?.message || 'Failed to publish event',
        color: 'red',
      });
    },
  });
};

export const useEventStatistics = (id: string, from?: string, to?: string) => {
  return useQuery({
    queryKey: ['events', id, 'statistics', from, to],
    queryFn: () => eventsApi.getStatistics(id, from, to),
    enabled: !!id,
    refetchInterval: 30000, // Refresh every 30 seconds
  });
};