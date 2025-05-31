import React, { useState } from 'react';
import {
  Container,
  Title,
  SimpleGrid,
  TextInput,
  Select,
  Group,
  Button,
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
import { IconSearch, IconFilter, IconAdjustments } from '@tabler/icons-react';
import { EventCard } from '../components/EventCard';
import { TicketPurchaseModal } from '../components/TicketPurchaseModal';
import { useEvents } from '../hooks/useEvents';
import type { Event } from '../types';

export const HomePage: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

  const { data: events, isLoading, error } = useEvents();

  const filteredEvents = events?.filter(event => {
    const matchesSearch = event.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        event.venue.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = statusFilter === '' || event.status === statusFilter;

    return matchesSearch && matchesStatus;
  }) || [];

  const handlePurchaseTicket = (eventId: string) => {
    const event = events?.find(e => e.id === eventId);
    if (event) {
      setSelectedEvent(event);
      setIsPurchaseModalOpen(true);
    }
  };

  const handleViewDetails = (eventId: string) => {
    window.location.href = `/events/${eventId}`;
  };

  if (isLoading) {
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
      <Container size="xl" py="xl">
        <Stack gap="xl">
          <Box>
            <Title order={1} fw={800} style={{ fontSize: rem(42) }} mb="sm">
              Discover Events
            </Title>
            <Text c="dimmed" size="lg">
              Find and book tickets for amazing events near you
            </Text>
          </Box>

          <Paper withBorder p="md" radius="lg" shadow="sm">
            <Group wrap="nowrap" grow gap="sm">
              <TextInput
                  placeholder="Search events by name or venue..."
                  leftSection={<IconSearch size={18} />}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  radius="md"
                  size="md"
              />

              <Select
                  placeholder="Filter by status"
                  leftSection={<IconFilter size={18} />}
                  data={[
                    { value: '', label: 'All Events' },
                    { value: 'published', label: 'Published' },
                    { value: 'draft', label: 'Draft' },
                    { value: 'cancelled', label: 'Cancelled' },
                  ]}
                  value={statusFilter}
                  onChange={(value) => setStatusFilter(value || '')}
                  clearable
                  radius="md"
                  size="md"
              />

              <Button
                  variant="light"
                  radius="md"
                  size="md"
                  leftSection={<IconAdjustments size={18} />}
              >
                Advanced Filters
              </Button>
            </Group>
          </Paper>

          <Divider my="sm" />

          {filteredEvents.length === 0 ? (
              <Center py="xl">
                <Stack align="center" gap="xs">
                  <Text size="xl" fw={500} c="dimmed">No events found</Text>
                  <Text c="dimmed">Try adjusting your search or filters</Text>
                  <Button
                      variant="subtle"
                      size="sm"
                      onClick={() => {
                        setSearchTerm('');
                        setStatusFilter('');
                      }}
                  >
                    Clear all filters
                  </Button>
                </Stack>
              </Center>
          ) : (
              <>
                <Group justify="space-between">
                  <Text c="dimmed">
                    Showing <Badge variant="light" color="blue" radius="sm">{filteredEvents.length}</Badge> events
                  </Text>
                  <Select
                      placeholder="Sort by"
                      data={[
                        { value: 'date', label: 'Date' },
                        { value: 'name', label: 'Name' },
                        { value: 'price', label: 'Price' },
                      ]}
                      radius="md"
                      size="sm"
                  />
                </Group>

                <SimpleGrid
                    cols={{ base: 1, sm: 2, lg: 3 }}
                    spacing="xl"
                    verticalSpacing="xl"
                >
                  {filteredEvents.map((event) => (
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
  );
};