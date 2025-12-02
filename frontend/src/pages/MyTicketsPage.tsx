import React from 'react';
import {
  Container,
  Title,
  Text,
  Stack,
  SimpleGrid,
  Card,
  Group,
  Badge,
  Button,
  Divider,
  Center,
  Loader,
} from '@mantine/core';
import {
  IconTicket,
  IconCalendar,
  IconMapPin,
  IconDownload,
  IconQrcode,
} from '@tabler/icons-react';
import { format } from 'date-fns';
import { useMyTickets } from '../hooks/useTickets';
import type { Ticket } from '../types';

const TicketCard: React.FC<{ ticket: Ticket }> = ({ ticket }) => {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'purchased': return 'green';
      case 'reserved': return 'yellow';
      case 'cancelled': return 'red';
      case 'refunded': return 'gray';
      default: return 'blue';
    }
  };

  const isUpcoming = new Date(ticket.event.eventDate) > new Date();

  return (
    <Card withBorder padding="lg" radius="md">
      <Stack gap="md">
        <Group justify="space-between" align="flex-start">
          <div style={{ flex: 1 }}>
            <Text fw={600} size="lg" lineClamp={1}>
              {ticket.event.name}
            </Text>
            <Text c="dimmed" size="sm">
              {ticket.ticketType.name}
            </Text>
          </div>
          <Badge color={getStatusColor(ticket.status)} variant="light">
            {ticket.status.toUpperCase()}
          </Badge>
        </Group>

        <Stack gap="xs">
          <Group gap="xs">
            <IconCalendar size={16} />
            <Text size="sm">
              {format(new Date(ticket.event.eventDate), 'PPP p')}
            </Text>
          </Group>

          <Group gap="xs">
            <IconMapPin size={16} />
            <Text size="sm">{ticket.event.venue}</Text>
          </Group>

          <Group gap="xs">
            <IconTicket size={16} />
            <Text size="sm">Price: ${ticket.priceFormatted}</Text>
          </Group>

          {ticket.qrCode && (
            <Group gap="xs">
              <IconQrcode size={16} />
              <Text size="sm" c="dimmed">QR: {ticket.qrCode}</Text>
            </Group>
          )}
        </Stack>

        {ticket.status === 'purchased' && isUpcoming && (
          <>
            <Divider />
            <Group grow>
              <Button
                variant="light"
                leftSection={<IconDownload size={16} />}
                size="sm"
              >
                Download PDF
              </Button>
              <Button
                variant="light"
                leftSection={<IconQrcode size={16} />}
                size="sm"
              >
                Show QR Code
              </Button>
            </Group>
          </>
        )}

        {ticket.purchasedAt && (
          <Text size="xs" c="dimmed">
            Purchased: {format(new Date(ticket.purchasedAt), 'PPp')}
          </Text>
        )}
      </Stack>
    </Card>
  );
};

export const MyTicketsPage: React.FC = () => {
  const { data: tickets, isLoading, error } = useMyTickets();

  if (isLoading) {
    return (
      <Center h={400}>
        <Loader size="lg" />
      </Center>
    );
  }

  if (error) {
    const errorMessage = (error as any)?.response?.status === 401 
      ? 'Please log in to view your tickets.' 
      : 'Error loading tickets. Please try again.';
    
    return (
      <Container>
        <Center h={400}>
          <Stack align="center" gap="md">
            <IconTicket size={48} color="gray" />
            <Text c="red" size="lg" fw={500}>{errorMessage}</Text>
            {(error as any)?.response?.status === 401 && (
              <Button onClick={() => window.location.href = '/login'}>
                Go to Login
              </Button>
            )}
            {(error as any)?.response?.status !== 401 && (
              <Button onClick={() => window.location.reload()}>
                Try Again
              </Button>
            )}
          </Stack>
        </Center>
      </Container>
    );
  }

  // Ensure tickets is an array (handle null/undefined)
  const ticketsList = Array.isArray(tickets) ? tickets : [];

  const upcomingTickets = ticketsList.filter(
    ticket => new Date(ticket.event.eventDate) > new Date() && ticket.status === 'purchased'
  );

  const pastTickets = ticketsList.filter(
    ticket => new Date(ticket.event.eventDate) <= new Date() && ticket.status === 'purchased'
  );

  const otherTickets = ticketsList.filter(
    ticket => ticket.status !== 'purchased'
  );

  return (
    <Container size="xl">
      <Stack gap="xl">
        <div>
          <Title order={1} mb="md">My Tickets</Title>
          <Text c="dimmed">Manage your event tickets and downloads</Text>
        </div>

        {ticketsList.length === 0 ? (
          <Center h={300}>
            <Stack align="center" gap="md">
              <IconTicket size={48} color="gray" />
              <Text c="dimmed" ta="center">
                No tickets found. Start exploring events to purchase your first ticket!
              </Text>
              <Button onClick={() => window.location.href = '/'}>
                Browse Events
              </Button>
            </Stack>
          </Center>
        ) : (
          <Stack gap="xl">
            {upcomingTickets.length > 0 && (
              <div>
                <Title order={2} mb="md">Upcoming Events</Title>
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                  {upcomingTickets.map((ticket) => (
                    <TicketCard key={ticket.id} ticket={ticket} />
                  ))}
                </SimpleGrid>
              </div>
            )}

            {pastTickets.length > 0 && (
              <div>
                <Title order={2} mb="md">Past Events</Title>
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                  {pastTickets.map((ticket) => (
                    <TicketCard key={ticket.id} ticket={ticket} />
                  ))}
                </SimpleGrid>
              </div>
            )}

            {otherTickets.length > 0 && (
              <div>
                <Title order={2} mb="md">Other Tickets</Title>
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                  {otherTickets.map((ticket) => (
                    <TicketCard key={ticket.id} ticket={ticket} />
                  ))}
                </SimpleGrid>
              </div>
            )}
          </Stack>
        )}
      </Stack>
    </Container>
  );
};