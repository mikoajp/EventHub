import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { eventsApi } from '../api/events';
import { type CreateEventData, type EventsResponse, type EventFilters, type FilterOptions } from '../types';
import { notifications } from '@mantine/notifications';

export const useEvents = (filters?: EventFilters) => {
  return useQuery<EventsResponse, Error>({
    queryKey: ['events', filters],
    queryFn: () => eventsApi.getAll(filters),
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchOnWindowFocus: false,
  });
};

export const useFilterOptions = () => {
  return useQuery<FilterOptions, Error>({
    queryKey: ['events', 'filter-options'],
    queryFn: eventsApi.getFilterOptions,
    staleTime: 30 * 60 * 1000, // 30 minutes
    refetchOnWindowFocus: false,
  });
};

export const useEvent = (id: string) => {
  return useQuery({
    queryKey: ['events', id],
    queryFn: () => eventsApi.getById(id),
    enabled: !!id,
    refetchInterval: 30000,
    staleTime: 15000,
    refetchOnWindowFocus: true,
    retry: 3,
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
      const status = error.response?.status;
      let message = error.response?.data?.message || 'Failed to create event';
      
      if (status === 401) {
        message = 'You must be logged in as an organizer to create events. Please log in or register as an organizer.';
      } else if (status === 403) {
        message = 'You need organizer permissions to create events. Please contact support or register as an organizer.';
      }
      
      notifications.show({
        title: status === 401 || status === 403 ? 'Permission Denied' : 'Error',
        message,
        color: 'red',
        autoClose: 8000, // Keep longer for auth errors
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
    refetchInterval: 30000,
  });
};