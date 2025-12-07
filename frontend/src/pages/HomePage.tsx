import React, { useState, useMemo } from 'react';
import {
  Container,
  Title,
  SimpleGrid,
  Group,
  Stack,
  Text,
  Loader,
  Center,
  rem,
  Badge,
  Paper,
  Divider,
  Box,
} from '@mantine/core';
import { TicketPurchaseModal } from '../components/TicketPurchaseModal';
import { AdvancedEventFilters } from '../components/AdvancedEventFilters';
import { useEvents, useFilterOptions } from '../hooks/useEvents';
import { EventCard } from '../components/EventCard';
import { Hero } from '../components/Hero';
import type { Event, EventFilters, FilterOptions } from '../types';

export const HomePage: React.FC = () => {
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

  // Event filters state
  const [filters, setFilters] = useState<EventFilters>({
    search: '',
    status: ['published'], // Only show published events on home page
    venue: [],
    date_from: undefined,
    date_to: undefined,
    price_min: undefined,
    price_max: undefined,
    has_available_tickets: false,
    sort_by: 'date',
    sort_direction: 'asc',
    page: 1,
    limit: 50,
  });

  const basicFilters = {
    search: '',
    status: ['published'],
    venue: [],
    date_from: undefined,
    date_to: undefined,
    price_min: undefined,
    price_max: undefined,
    has_available_tickets: false,
    sort_by: 'date' as const,
    sort_direction: 'asc' as const,
    page: 1,
    limit: 100,
  };

  const { data: eventsResponse, isLoading, error} = useEvents(basicFilters);
  const { data: filterOptionsFromAPI, isLoading: isLoadingFilterOptions } = useFilterOptions();

  const allEvents = eventsResponse?.events ?? [];

  const events = useMemo(() => {
    if (!allEvents || allEvents.length === 0) return [];

    let filteredEvents = [...allEvents];

    if (filters.search) {
      const searchTerm = filters.search.toLowerCase();
      filteredEvents = filteredEvents.filter((event: Event) =>
          event.name.toLowerCase().includes(searchTerm) ||
          event.venue.toLowerCase().includes(searchTerm) ||
          event.description?.toLowerCase().includes(searchTerm)
      );
    }

    if (filters.status && filters.status.length > 0) {
      filteredEvents = filteredEvents.filter((event: Event) =>
          filters.status!.includes(event.status)
      );
    }

    if (filters.venue && filters.venue.length > 0) {
      filteredEvents = filteredEvents.filter((event: Event) =>
          filters.venue!.includes(event.venue)
      );
    }

    if (filters.date_from) {
      const fromDate = new Date(filters.date_from);
      filteredEvents = filteredEvents.filter((event: Event) =>
          new Date(event.eventDate) >= fromDate
      );
    }

    if (filters.date_to) {
      const toDate = new Date(filters.date_to);
      filteredEvents = filteredEvents.filter((event: Event) =>
          new Date(event.eventDate) <= toDate
      );
    }

    if (filters.price_min !== undefined) {
      filteredEvents = filteredEvents.filter((event: Event) =>
          event.ticketTypes?.some(tt => (tt.price / 100) >= filters.price_min!)
      );
    }

    if (filters.price_max !== undefined) {
      filteredEvents = filteredEvents.filter((event: Event) =>
          event.ticketTypes?.some(tt => (tt.price / 100) <= filters.price_max!)
      );
    }

    if (filters.has_available_tickets) {
      filteredEvents = filteredEvents.filter((event: Event) =>
          event.availableTickets > 0
      );
    }

    if (filters.sort_by) {
      filteredEvents.sort((a: Event, b: Event) => {
        let aValue: any, bValue: any;

        switch (filters.sort_by) {
          case 'date':
            aValue = new Date(a.eventDate);
            bValue = new Date(b.eventDate);
            break;
          case 'name':
            aValue = a.name.toLowerCase();
            bValue = b.name.toLowerCase();
            break;
          case 'venue':
            aValue = a.venue.toLowerCase();
            bValue = b.venue.toLowerCase();
            break;
          case 'price':
            aValue = Math.min(...(a.ticketTypes?.map(tt => tt.price) || [0]));
            bValue = Math.min(...(b.ticketTypes?.map(tt => tt.price) || [0]));
            break;
          case 'created_at':
            aValue = new Date(a.createdAt || 0);
            bValue = new Date(b.createdAt || 0);
            break;
          default:
            return 0;
        }

        if (aValue < bValue) return filters.sort_direction === 'asc' ? -1 : 1;
        if (aValue > bValue) return filters.sort_direction === 'asc' ? 1 : -1;
        return 0;
      });
    }

    return filteredEvents;
  }, [allEvents, filters]);

  const filterOptions = useMemo<FilterOptions | null>(() => {
    if (filterOptionsFromAPI) {
      return filterOptionsFromAPI;
    }

    if (!allEvents || allEvents.length === 0) return null;

    const venues = [...new Set(allEvents.map((event: Event) => event.venue))];
    const statuses = [
      { value: 'published', label: 'Published' },
      { value: 'draft', label: 'Draft' },
      { value: 'cancelled', label: 'Cancelled' },
    ];

    const allPrices = allEvents.flatMap((event: Event) =>
        event.ticketTypes?.map(tt => tt.price / 100) || []
    );
    const priceRange = allPrices.length > 0 ? {
      min: Math.min(...allPrices),
      max: Math.max(...allPrices)
    } : { min: 0, max: 1000 };

    return {
      venues,
      statuses,
      priceRange
    };
  }, [allEvents, filterOptionsFromAPI]);

  const handleFiltersChange = (newFilters: Partial<EventFilters>) => {
    setFilters(prev => ({
      ...prev,
      ...newFilters,
      page: 1
    }));
  };

  const handleResetFilters = () => {
    setFilters({
      search: '',
      status: ['published'],
      venue: [],
      date_from: undefined,
      date_to: undefined,
      price_min: undefined,
      price_max: undefined,
      has_available_tickets: false,
      sort_by: 'date',
      sort_direction: 'asc',
      page: 1,
      limit: 50,
    });
  };

  const handlePurchaseTicket = (eventId: string) => {
    const event = events.find((e: Event) => e.id === eventId);
    if (event) {
      setSelectedEvent(event);
      setIsPurchaseModalOpen(true);
    }
  };

  const handleViewDetails = (eventId: string) => {
    window.location.href = `/events/${eventId}`;
  };

  if (isLoading || isLoadingFilterOptions) {
    return (
        <Center h={400}>
          <Loader size="lg" type="dots" />
        </Center>
    );
  }

  if (error) {
    return (
        <Container>
          <Paper withBorder p="lg" radius="md" shadow="sm">
            <Text c="red" fw={500}>Error loading events</Text>
            <Text c="red" size="sm" mt={4}>Please try again later or refresh the page</Text>
          </Paper>
        </Container>
    );
  }

  return (
      <Box>
        <Hero />
        
        <Container size="xl" pb="xl" id="events-section">
          <Stack gap="xl">
            {/* Advanced Filters Component */}
            <AdvancedEventFilters
                filters={filters}
                filterOptions={filterOptions}
                onFiltersChange={handleFiltersChange}
                onReset={handleResetFilters}
                loading={isLoading || isLoadingFilterOptions}
            />

            <Divider my="sm" />

            {events.length === 0 ? (
                <Center py="xl">
                  <Stack align="center" gap="xs">
                    <Text size="xl" fw={500} c="dimmed">No events found</Text>
                    <Text c="dimmed">Try adjusting your search or filters</Text>
                  </Stack>
                </Center>
            ) : (
                <>
                  <Group justify="space-between">
                    <Text c="dimmed">
                      Showing <Badge variant="light" color="blue" radius="sm">{events.length}</Badge> events
                    </Text>
                  </Group>

                  <SimpleGrid
                      cols={{ base: 1, sm: 2, lg: 3 }}
                      spacing="xl"
                      verticalSpacing="xl"
                  >
                    {events.map((event: Event) => (
                        <EventCard
                            key={event.id}
                            event={event}
                            onViewDetails={handleViewDetails}
                            onPurchaseTicket={handlePurchaseTicket}
                        />
                    ))}
                  </SimpleGrid>
                </>
            )}
          </Stack>

          {selectedEvent && (
              <TicketPurchaseModal
                  isOpen={isPurchaseModalOpen}
                  onClose={() => {
                    setIsPurchaseModalOpen(false);
                    setSelectedEvent(null);
                  }}
                  event={selectedEvent}
              />
          )}
        </Container>
      </Box>
  );
};