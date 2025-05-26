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
} from '@mantine/core';
import { IconSearch, IconFilter } from '@tabler/icons-react';
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
        <div>
          <Title order={1} mb="md">Discover Events</Title>
          <Text c="dimmed">Find and book tickets for amazing events near you</Text>
        </div>

        <Group>
          <TextInput
            placeholder="Search events..."
            leftSection={<IconSearch size={16} />}
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{ flex: 1 }}
          />
          
          <Select
            placeholder="Filter by status"
            leftSection={<IconFilter size={16} />}
            data={[
              { value: '', label: 'All Events' },
              { value: 'published', label: 'Published' },
              { value: 'draft', label: 'Draft' },
              { value: 'cancelled', label: 'Cancelled' },
            ]}
            value={statusFilter}
            onChange={(value) => setStatusFilter(value || '')}
            clearable
          />
          
          <Button variant="light">
            Advanced Filters
          </Button>
        </Group>

        {filteredEvents.length === 0 ? (
          <Center h={200}>
            <Text c="dimmed">No events found matching your criteria.</Text>
          </Center>
        ) : (
          <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
            {filteredEvents.map((event) => (
              <EventCard
                key={event.id}
                event={event}
                onViewDetails={handleViewDetails}
                onPurchaseTicket={handlePurchaseTicket}
              />
            ))}
          </SimpleGrid>
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