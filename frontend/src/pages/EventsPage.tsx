import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Container,
  Title,
  Text,
  SimpleGrid,
  Group,
  Button,
  Stack,
  SegmentedControl,
  Loader,
  Center,
  Pagination,
  Alert,
  Card,
  Badge,
} from '@mantine/core';
import {
  IconGridDots,
  IconList,
  IconAlertCircle,
  IconCalendar,
  IconMapPin,
  IconCurrency,
} from '@tabler/icons-react';
import { format } from 'date-fns';
import { EventCard } from '../components/EventCard';
import { AdvancedEventFilters } from '../components/AdvancedEventFilters';
import { TicketPurchaseModal } from '../components/TicketPurchaseModal';
import { useEvents, useFilterOptions } from '../hooks/useEvents';
import type { Event, EventFilters } from '../types';

type ViewMode = 'grid' | 'list';

const EventListItem: React.FC<{
  event: Event;
  onViewDetails: (id: string) => void;
  onPurchaseTicket?: (id: string) => void;
}> = ({ event, onViewDetails, onPurchaseTicket }) => {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'published': return 'green';
      case 'draft': return 'yellow';
      case 'cancelled': return 'red';
      case 'completed': return 'gray';
      default: return 'blue';
    }
  };

  const soldPercentage = (event.ticketsSold / event.maxTickets) * 100;
  const minPrice = Math.min(...event.ticketTypes.map(tt => tt.price));

  return (
      <Card withBorder padding="md" radius="md">
        <Group gap="md" align="flex-start">
          {/* Event Image Placeholder */}
          <div
              style={{
                width: 120,
                height: 80,
                backgroundColor: '#f1f3f4',
                borderRadius: 8,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
          >
            <IconCalendar size={32} color="#9ca3af" />
          </div>

          {/* Event Details */}
          <div style={{ flex: 1 }}>
            <Group justify="space-between" align="flex-start" mb="xs">
              <div>
                <Group gap="xs" mb={4}>
                  <Text fw={600} size="lg" lineClamp={1}>
                    {event.name}
                  </Text>
                  <Badge color={getStatusColor(event.status)} variant="light" size="sm">
                    {event.status.toUpperCase()}
                  </Badge>
                </Group>
                <Text c="dimmed" size="sm" lineClamp={2}>
                  {event.description}
                </Text>
              </div>
            </Group>

            <Stack gap="xs">
              <Group gap="md">
                <Group gap="xs">
                  <IconCalendar size={14} />
                  <Text size="sm">
                    {format(new Date(event.eventDate), 'MMM dd, yyyy')} at{' '}
                    {format(new Date(event.eventDate), 'h:mm a')}
                  </Text>
                </Group>

                <Group gap="xs">
                  <IconMapPin size={14} />
                  <Text size="sm">{event.venue}</Text>
                </Group>

                <Group gap="xs">
                  <IconCurrency size={14} />
                  <Text size="sm">From ${(minPrice / 100).toFixed(2)}</Text>
                </Group>
              </Group>

              <Group justify="space-between" align="center">
                <div style={{ flex: 1 }}>
                  <Text size="xs" c="dimmed" mb={2}>
                    {event.ticketsSold} / {event.maxTickets} tickets sold
                  </Text>
                  <div style={{ width: '100%', maxWidth: 200 }}>
                    <div
                        style={{
                          height: 4,
                          backgroundColor: '#e9ecef',
                          borderRadius: 2,
                          overflow: 'hidden',
                        }}
                    >
                      <div
                          style={{
                            height: '100%',
                            width: `${soldPercentage}%`,
                            backgroundColor: soldPercentage > 80 ? '#fa5252' : soldPercentage > 50 ? '#fd7e14' : '#51cf66',
                            transition: 'width 0.3s ease',
                          }}
                      />
                    </div>
                  </div>
                </div>

                <Group gap="xs">
                  <Button
                      variant="light"
                      size="sm"
                      onClick={() => onViewDetails(event.id)}
                  >
                    View Details
                  </Button>
                  {onPurchaseTicket && event.availableTickets > 0 && (
                      <Button
                          size="sm"
                          onClick={() => onPurchaseTicket(event.id)}
                      >
                        Buy Tickets
                      </Button>
                  )}
                </Group>
              </Group>
            </Stack>
          </div>
        </Group>
      </Card>
  );
};

export const EventsPage: React.FC = () => {
  const navigate = useNavigate();
  const [viewMode, setViewMode] = useState<ViewMode>('grid');
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

  const [filters, setFilters] = useState<EventFilters>({
    search: '',
    status: ['published'],
    venue: [],
    date_from: '',
    date_to: '',
    price_min: undefined,
    price_max: undefined,
    has_available_tickets: false,
    sort_by: 'date',
    sort_direction: 'asc',
    page: 1,
    limit: 20,
  });

  const { data: eventsResponse, isLoading, error} = useEvents(filters);
  const { data: filterOptions } = useFilterOptions();

  const events = eventsResponse?.events || [];
  const pagination = eventsResponse?.pagination;

  const handleFiltersChange = (newFilters: Partial<EventFilters>) => {
    setFilters(prev => ({
      ...prev,
      ...newFilters,
      // Reset page when filters change (except when explicitly setting page)
      page: newFilters.page !== undefined ? newFilters.page : 1,
    }));
  };

  const handleResetFilters = () => {
    setFilters({
      search: '',
      status: ['published'],
      venue: [],
      date_from: '',
      date_to: '',
      price_min: undefined,
      price_max: undefined,
      has_available_tickets: false,
      sort_by: 'date',
      sort_direction: 'asc',
      page: 1,
      limit: 20,
    });
  };

  const handlePurchaseTicket = (eventId: string) => {
    const event = events.find(e => e.id === eventId);
    if (event) {
      setSelectedEvent(event);
      setIsPurchaseModalOpen(true);
    }
  };

  const handleViewDetails = (eventId: string) => {
    navigate(`/events/${eventId}`);
  };

  const handlePageChange = (page: number) => {
    handleFiltersChange({ page });
  };

  if (error) {
    return (
        <Container size="lg" py="xl">
          <Alert
              icon={<IconAlertCircle size={16} />}
              title="Error"
              color="red"
              variant="light"
          >
            {error.message || 'Failed to load events'}
          </Alert>
        </Container>
    );
  }

  return (
      <Container size="lg" py="xl">
        <Stack gap="xl">
          {/* Header */}
          <div>
            <Title order={1} mb="sm">
              Events
            </Title>
            <Text c="dimmed" size="lg">
              Discover amazing events happening around you
            </Text>
          </div>

          {/* Advanced Filters */}
          <AdvancedEventFilters
              filters={filters}
              filterOptions={filterOptions || null}
              onFiltersChange={handleFiltersChange}
              onReset={handleResetFilters}
              loading={isLoading}
          />

          {/* View Controls and Results Info */}
          <Group justify="space-between" align="center">
            <div>
              {pagination && (
                  <Text size="sm" c="dimmed">
                    Showing {((pagination.page - 1) * pagination.limit) + 1}-
                    {Math.min(pagination.page * pagination.limit, pagination.total)} of {pagination.total} events
                  </Text>
              )}
            </div>

            <SegmentedControl
                value={viewMode}
                onChange={(value) => setViewMode(value as ViewMode)}
                data={[
                  { label: <IconGridDots size={16} />, value: 'grid' },
                  { label: <IconList size={16} />, value: 'list' },
                ]}
            />
          </Group>

          {/* Events Display */}
          {isLoading ? (
              <Center py="xl">
                <Loader size="lg" />
              </Center>
          ) : events.length === 0 ? (
              <Center py="xl">
                <Stack align="center" gap="md">
                  <Text size="lg" c="dimmed">
                    No events found
                  </Text>
                  <Text size="sm" c="dimmed">
                    Try adjusting your filters or search criteria
                  </Text>
                  <Button variant="light" onClick={handleResetFilters}>
                    Clear Filters
                  </Button>
                </Stack>
              </Center>
          ) : (
              <Stack gap="md">
                {viewMode === 'grid' ? (
                    <SimpleGrid
                        cols={{ base: 1, sm: 2, lg: 3 }}
                        spacing="md"
                    >
                      {events.map((event) => (
                          <EventCard
                              key={event.id}
                              event={event}
                              onViewDetails={handleViewDetails}
                              onPurchaseTicket={handlePurchaseTicket}
                          />
                      ))}
                    </SimpleGrid>
                ) : (
                    <Stack gap="md">
                      {events.map((event) => (
                          <EventListItem
                              key={event.id}
                              event={event}
                              onViewDetails={handleViewDetails}
                              onPurchaseTicket={handlePurchaseTicket}
                          />
                      ))}
                    </Stack>
                )}
              </Stack>
          )}

          {/* Pagination */}
          {pagination && pagination.pages > 1 && (
              <Center>
                <Pagination
                    total={pagination.pages}
                    value={pagination.page}
                    onChange={handlePageChange}
                    size="md"
                />
              </Center>
          )}
        </Stack>

        {/* Purchase Modal */}
        {selectedEvent && (
            <TicketPurchaseModal
                event={selectedEvent}
                isOpen={isPurchaseModalOpen}
                onClose={() => {
                  setIsPurchaseModalOpen(false);
                  setSelectedEvent(null);
                }}
            />
        )}
      </Container>
  );
};