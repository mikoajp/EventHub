import React, { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Container,
  Title,
  Text,
  SimpleGrid,
  TextInput,
  Select,
  Group,
  Button,
  Stack,
  Card,
  SegmentedControl,
  MultiSelect,
  RangeSlider,
  Loader,
  Center,
  Badge,
  ActionIcon,
  Pagination,
  Switch,
  Accordion,
  NumberInput,
} from '@mantine/core';
import {
  IconSearch,
  IconCalendar,
  IconMapPin,
  IconCurrency,
  IconGridDots,
  IconList,
  IconRefresh,
  IconSortAscending,
  IconSortDescending,
  IconX,
} from '@tabler/icons-react';
import { DatePickerInput } from '@mantine/dates';
import { useDebouncedValue } from '@mantine/hooks';
import { format } from 'date-fns';
import { EventCard } from '../components/EventCard';
import { TicketPurchaseModal } from '../components/TicketPurchaseModal';
import { useEvents } from '../hooks/useEvents';
import type { Event } from '../types';

type ViewMode = 'grid' | 'list';
type SortOption = 'date' | 'name' | 'price' | 'popularity';
type SortDirection = 'asc' | 'desc';

interface FilterOptions {
  searchTerm: string;
  status: string[];
  dateRange: [Date | null, Date | null];
  priceRange: [number, number];
  venues: string[];
  categories: string[];
  showOnlyAvailable: boolean;
}

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
                  Details
                </Button>
                {event.status === 'published' && event.availableTickets > 0 && onPurchaseTicket && (
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
  const [sortBy, setSortBy] = useState<SortOption>('date');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(12);
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

  // Filter state
  const [filters, setFilters] = useState<FilterOptions>({
    searchTerm: '',
    status: [],
    dateRange: [null, null],
    priceRange: [0, 1000],
    venues: [],
    categories: [],
    showOnlyAvailable: false,
  });

  const [debouncedSearchTerm] = useDebouncedValue(filters.searchTerm, 300);

  const { data: events, isLoading, error, refetch } = useEvents();

  // Extract unique venues for filter
  const uniqueVenues = useMemo(() => {
    return Array.from(new Set(events?.map(event => event.venue) || []));
  }, [events]);

  // Filter and sort events
  const filteredAndSortedEvents = useMemo(() => {
    if (!events) return [];

    let filtered = events.filter(event => {
      // Search term
      const searchMatch = event.name.toLowerCase().includes(debouncedSearchTerm.toLowerCase()) ||
                         event.venue.toLowerCase().includes(debouncedSearchTerm.toLowerCase()) ||
                         event.description.toLowerCase().includes(debouncedSearchTerm.toLowerCase());

      // Status filter
      const statusMatch = filters.status.length === 0 || filters.status.includes(event.status);

      // Date range filter
      const eventDate = new Date(event.eventDate);
      const dateMatch = (!filters.dateRange[0] || eventDate >= filters.dateRange[0]) &&
                       (!filters.dateRange[1] || eventDate <= filters.dateRange[1]);

      // Price range filter
      const minPrice = Math.min(...event.ticketTypes.map(tt => tt.price));
      const priceMatch = minPrice >= filters.priceRange[0] * 100 && minPrice <= filters.priceRange[1] * 100;

      // Venue filter
      const venueMatch = filters.venues.length === 0 || filters.venues.includes(event.venue);

      // Available tickets filter
      const availabilityMatch = !filters.showOnlyAvailable || event.availableTickets > 0;

      return searchMatch && statusMatch && dateMatch && priceMatch && venueMatch && availabilityMatch;
    });

    // Sort events
    filtered.sort((a, b) => {
      let comparison = 0;

      switch (sortBy) {
        case 'date':
          comparison = new Date(a.eventDate).getTime() - new Date(b.eventDate).getTime();
          break;
        case 'name':
          comparison = a.name.localeCompare(b.name);
          break;
        case 'price':
          const aMinPrice = Math.min(...a.ticketTypes.map(tt => tt.price));
          const bMinPrice = Math.min(...b.ticketTypes.map(tt => tt.price));
          comparison = aMinPrice - bMinPrice;
          break;
        case 'popularity':
          comparison = b.ticketsSold - a.ticketsSold;
          break;
      }

      return sortDirection === 'desc' ? -comparison : comparison;
    });

    return filtered;
  }, [events, debouncedSearchTerm, filters, sortBy, sortDirection]);

  // Pagination
  const totalPages = Math.ceil(filteredAndSortedEvents.length / itemsPerPage);
  const paginatedEvents = filteredAndSortedEvents.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  const handlePurchaseTicket = (eventId: string) => {
    const event = events?.find(e => e.id === eventId);
    if (event) {
      setSelectedEvent(event);
      setIsPurchaseModalOpen(true);
    }
  };

  const handleViewDetails = (eventId: string) => {
    navigate(`/events/${eventId}`);
  };

  const clearFilters = () => {
    setFilters({
      searchTerm: '',
      status: [],
      dateRange: [null, null],
      priceRange: [0, 1000],
      venues: [],
      categories: [],
      showOnlyAvailable: false,
    });
    setCurrentPage(1);
  };

  const hasActiveFilters = filters.status.length > 0 || 
                          filters.venues.length > 0 || 
                          filters.dateRange[0] || 
                          filters.dateRange[1] || 
                          filters.priceRange[0] > 0 || 
                          filters.priceRange[1] < 1000 ||
                          filters.showOnlyAvailable;

  if (isLoading) {
    return (
      <Center h={400}>
        <Loader size="lg" />
      </Center>
    );
  }

  if (error) {
    return (
      <Container>
        <Text c="red">Error loading events. Please try again.</Text>
      </Container>
    );
  }

  return (
    <Container size="xl">
      <Stack gap="xl">
        {/* Header */}
        <div>
          <Group justify="space-between" align="flex-end" mb="md">
            <div>
              <Title order={1} mb="xs">All Events</Title>
              <Text c="dimmed">
                Discover amazing events happening around you
              </Text>
            </div>
            <Group>
              <ActionIcon
                variant="light"
                onClick={() => refetch()}
                loading={isLoading}
              >
                <IconRefresh size={16} />
              </ActionIcon>
              <SegmentedControl
                value={viewMode}
                onChange={(value) => setViewMode(value as ViewMode)}
                data={[
                  { label: <IconGridDots size={16} />, value: 'grid' },
                  { label: <IconList size={16} />, value: 'list' },
                ]}
              />
            </Group>
          </Group>

          {/* Search and Sort */}
          <Group mb="lg">
            <TextInput
              placeholder="Search events, venues, or keywords..."
              leftSection={<IconSearch size={16} />}
              value={filters.searchTerm}
              onChange={(e) => setFilters(prev => ({ ...prev, searchTerm: e.target.value }))}
              style={{ flex: 1 }}
            />
            
            <Select
              placeholder="Sort by"
              leftSection={sortDirection === 'asc' ? <IconSortAscending size={16} /> : <IconSortDescending size={16} />}
              data={[
                { value: 'date', label: 'Date' },
                { value: 'name', label: 'Name' },
                { value: 'price', label: 'Price' },
                { value: 'popularity', label: 'Popularity' },
              ]}
              value={sortBy}
              onChange={(value) => setSortBy(value as SortOption)}
              style={{ minWidth: 150 }}
            />
            
            <ActionIcon
              variant="light"
              onClick={() => setSortDirection(prev => prev === 'asc' ? 'desc' : 'asc')}
            >
              {sortDirection === 'asc' ? <IconSortAscending size={16} /> : <IconSortDescending size={16} />}
            </ActionIcon>
          </Group>
        </div>

        <Group align="flex-start" gap="xl">
          {/* Filters Sidebar */}
          <Card withBorder style={{ minWidth: 280, position: 'sticky', top: 20 }}>
            <Stack gap="md">
              <Group justify="space-between" align="center">
                <Text fw={600} size="lg">Filters</Text>
                {hasActiveFilters && (
                  <Button
                    variant="light"
                    size="xs"
                    leftSection={<IconX size={12} />}
                    onClick={clearFilters}
                  >
                    Clear
                  </Button>
                )}
              </Group>

              <Accordion defaultValue={['status', 'date', 'price']} multiple>
                <Accordion.Item value="status">
                  <Accordion.Control>Event Status</Accordion.Control>
                  <Accordion.Panel>
                    <MultiSelect
                      placeholder="Select status"
                      data={[
                        { value: 'published', label: 'Published' },
                        { value: 'draft', label: 'Draft' },
                        { value: 'cancelled', label: 'Cancelled' },
                        { value: 'completed', label: 'Completed' },
                      ]}
                      value={filters.status}
                      onChange={(value) => setFilters(prev => ({ ...prev, status: value }))}
                      clearable
                    />
                  </Accordion.Panel>
                </Accordion.Item>

                <Accordion.Item value="date">
                  <Accordion.Control>Date Range</Accordion.Control>
                  <Accordion.Panel>
                    <Stack gap="sm">
                      <DatePickerInput
                        label="From"
                        placeholder="Start date"
                        value={filters.dateRange[0]}
                        onChange={(value) => setFilters(prev => ({
                            ...prev,
                            dateRange: [value ? new Date(value) : null, prev.dateRange[1]]
                          }))}
                        clearable
                      />
                      <DatePickerInput
                        label="To"
                        placeholder="End date"
                        value={filters.dateRange[1]}
                        onChange={(value) => setFilters(prev => ({
                            ...prev,
                            dateRange: [prev.dateRange[0], value ? new Date(value) : null]
                          }))}
                        clearable
                      />
                    </Stack>
                  </Accordion.Panel>
                </Accordion.Item>

                <Accordion.Item value="price">
                  <Accordion.Control>Price Range</Accordion.Control>
                  <Accordion.Panel>
                    <Stack gap="sm">
                      <Group justify="space-between">
                        <Text size="sm">$0</Text>
                        <Text size="sm">$1000+</Text>
                      </Group>
                      <RangeSlider
                        min={0}
                        max={1000}
                        step={10}
                        value={filters.priceRange}
                        onChange={(value) => setFilters(prev => ({ ...prev, priceRange: value }))}
                        marks={[
                          { value: 0, label: '$0' },
                          { value: 250, label: '$250' },
                          { value: 500, label: '$500' },
                          { value: 1000, label: '$1000+' },
                        ]}
                      />
                      <Group grow>
                        <NumberInput
                          placeholder="Min"
                          value={filters.priceRange[0]}
                          onChange={(value) => setFilters(prev => ({
                            ...prev,
                            priceRange: [Number(value) || 0, prev.priceRange[1]]
                          }))}
                          min={0}
                          max={1000}
                        />
                        <NumberInput
                          placeholder="Max"
                          value={filters.priceRange[1]}
                          onChange={(value) => setFilters(prev => ({
                            ...prev,
                            priceRange: [prev.priceRange[0], Number(value) || 1000]
                          }))}
                          min={0}
                          max={1000}
                        />
                      </Group>
                    </Stack>
                  </Accordion.Panel>
                </Accordion.Item>

                <Accordion.Item value="venue">
                  <Accordion.Control>Venue</Accordion.Control>
                  <Accordion.Panel>
                    <MultiSelect
                      placeholder="Select venues"
                      data={uniqueVenues.map(venue => ({ value: venue, label: venue }))}
                      value={filters.venues}
                      onChange={(value) => setFilters(prev => ({ ...prev, venues: value }))}
                      clearable
                      searchable
                    />
                  </Accordion.Panel>
                </Accordion.Item>

                <Accordion.Item value="availability">
                  <Accordion.Control>Availability</Accordion.Control>
                  <Accordion.Panel>
                    <Switch
                      label="Show only available events"
                      checked={filters.showOnlyAvailable}
                      onChange={(event) => setFilters(prev => ({
                        ...prev,
                        showOnlyAvailable: event.currentTarget.checked
                      }))}
                    />
                  </Accordion.Panel>
                </Accordion.Item>
              </Accordion>
            </Stack>
          </Card>

          {/* Events List/Grid */}
          <div style={{ flex: 1 }}>
            <Stack gap="lg">
              {/* Results Summary */}
              <Group justify="space-between" align="center">
                <Text c="dimmed">
                  {filteredAndSortedEvents.length} event{filteredAndSortedEvents.length !== 1 ? 's' : ''} found
                  {hasActiveFilters && ' (filtered)'}
                </Text>
                <Text size="sm" c="dimmed">
                  Page {currentPage} of {totalPages}
                </Text>
              </Group>

              {/* Events Display */}
              {paginatedEvents.length === 0 ? (
                <Center h={300}>
                  <Stack align="center" gap="md">
                    <IconCalendar size={48} color="gray" />
                    <Text c="dimmed" ta="center">
                      No events found matching your criteria.
                    </Text>
                    {hasActiveFilters && (
                      <Button variant="light" onClick={clearFilters}>
                        Clear Filters
                      </Button>
                    )}
                  </Stack>
                </Center>
              ) : viewMode === 'grid' ? (
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                  {paginatedEvents.map((event) => (
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
                  {paginatedEvents.map((event) => (
                    <EventListItem
                      key={event.id}
                      event={event}
                      onViewDetails={handleViewDetails}
                      onPurchaseTicket={handlePurchaseTicket}
                    />
                  ))}
                </Stack>
              )}

              {/* Pagination */}
              {totalPages > 1 && (
                <Group justify="center" mt="xl">
                  <Pagination
                    value={currentPage}
                    onChange={setCurrentPage}
                    total={totalPages}
                    size="md"
                  />
                </Group>
              )}
            </Stack>
          </div>
        </Group>
      </Stack>

      {/* Purchase Modal */}
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
  );
};