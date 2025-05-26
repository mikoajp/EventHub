import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  Container,
  Title,
  Text,
  Group,
  Badge,
  Stack,
  Grid,
  Card,
  Button,
  Divider,
  Progress,
  Loader,
  Center,
} from '@mantine/core';
import {
  IconCalendar,
  IconMapPin,
  IconUsers,
  IconChartBar,
} from '@tabler/icons-react';
import { format } from 'date-fns';
import { useEvent, useEventStatistics } from '../hooks/useEvents';
import { TicketPurchaseModal } from '../components/TicketPurchaseModal';

export const EventDetailsPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [isPurchaseModalOpen, setIsPurchaseModalOpen] = useState(false);

  const { data: event, isLoading: eventLoading } = useEvent(id!);
  const { data: statistics, isLoading: statsLoading } = useEventStatistics(id!);

  if (eventLoading) {
    return (
      <Center h={400}>
        <Loader size="lg" />
      </Center>
    );
  }

  if (!event) {
    return (
      <Container>
        <Text c="red">Event not found.</Text>
      </Container>
    );
  }

  const soldPercentage = (event.ticketsSold / event.maxTickets) * 100;

  return (
    <Container size="xl">
      <Stack gap="xl">
        {/* Header */}
        <div>
          <Group justify="space-between" align="flex-start" mb="md">
            <div style={{ flex: 1 }}>
              <Title order={1} mb="xs">{event.name}</Title>
              <Group gap="md" mb="md">
                <Badge size="lg" variant="light">
                  {event.status.toUpperCase()}
                </Badge>
                <Text c="dimmed">Organized by {event.organizer.fullName}</Text>
              </Group>
            </div>
            {event.status === 'published' && event.availableTickets > 0 && (
              <Button
                size="lg"
                onClick={() => setIsPurchaseModalOpen(true)}
              >
                Buy Tickets
              </Button>
            )}
          </Group>

          <Grid>
            <Grid.Col span={{ base: 12, md: 8 }}>
              <Stack gap="md">
                <Card withBorder padding="lg">
                  <Title order={3} mb="md">Event Details</Title>
                  <Text>{event.description}</Text>
                </Card>

                <Card withBorder padding="lg">
                  <Title order={3} mb="md">Event Information</Title>
                  <Stack gap="md">
                    <Group>
                      <IconCalendar size={20} />
                      <div>
                        <Text fw={500}>Date & Time</Text>
                        <Text c="dimmed">
                          {format(new Date(event.eventDate), 'EEEE, MMMM do, yyyy')}
                        </Text>
                        <Text c="dimmed">
                          {format(new Date(event.eventDate), 'h:mm a')}
                        </Text>
                      </div>
                    </Group>

                    <Group>
                      <IconMapPin size={20} />
                      <div>
                        <Text fw={500}>Venue</Text>
                        <Text c="dimmed">{event.venue}</Text>
                      </div>
                    </Group>

                    <Group>
                      <IconUsers size={20} />
                      <div>
                        <Text fw={500}>Capacity</Text>
                        <Text c="dimmed">{event.maxTickets} total tickets</Text>
                      </div>
                    </Group>
                  </Stack>
                </Card>
              </Stack>
            </Grid.Col>

            <Grid.Col span={{ base: 12, md: 4 }}>
              <Stack gap="md">
                {/* Ticket Sales Progress */}
                <Card withBorder padding="lg">
                  <Title order={4} mb="md">Ticket Sales</Title>
                  <Stack gap="sm">
                    <Group justify="space-between">
                      <Text size="sm">Sold</Text>
                      <Text size="sm" fw={500}>
                        {event.ticketsSold} / {event.maxTickets}
                      </Text>
                    </Group>
                    <Progress value={soldPercentage} size="lg" />
                    <Text size="sm" c="dimmed" ta="center">
                      {soldPercentage.toFixed(1)}% sold
                    </Text>
                  </Stack>
                </Card>

                {/* Ticket Types */}
                <Card withBorder padding="lg">
                  <Title order={4} mb="md">Available Tickets</Title>
                  <Stack gap="sm">
                    {event.ticketTypes.map((ticketType) => (
                      <div key={ticketType.id}>
                        <Group justify="space-between" mb="xs">
                          <Text fw={500}>{ticketType.name}</Text>
                          <Text fw={500}>${ticketType.priceFormatted}</Text>
                        </Group>
                        <Group justify="space-between">
                          <Text size="sm" c="dimmed">
                            {ticketType.available} available
                          </Text>
                          <Badge
                            color={ticketType.available > 0 ? 'green' : 'red'}
                            variant="light"
                            size="sm"
                          >
                            {ticketType.available > 0 ? 'Available' : 'Sold Out'}
                          </Badge>
                        </Group>
                        <Divider my="sm" />
                      </div>
                    ))}
                  </Stack>
                </Card>

                {/* Statistics (if available) */}
                {statistics && !statsLoading && (
                  <Card withBorder padding="lg">
                    <Group mb="md">
                      <IconChartBar size={20} />
                      <Title order={4}>Statistics</Title>
                    </Group>
                    <Stack gap="sm">
                      <Group justify="space-between">
                        <Text size="sm">Total Revenue</Text>
                        <Text size="sm" fw={500}>
                          ${(statistics.totalRevenue / 100).toFixed(2)}
                        </Text>
                      </Group>
                      <Group justify="space-between">
                        <Text size="sm">Conversion Rate</Text>
                        <Text size="sm" fw={500}>
                          {statistics.conversionRate.toFixed(1)}%
                        </Text>
                      </Group>
                    </Stack>
                  </Card>
                )}
              </Stack>
            </Grid.Col>
          </Grid>
        </div>
      </Stack>

      <TicketPurchaseModal
        isOpen={isPurchaseModalOpen}
        onClose={() => setIsPurchaseModalOpen(false)}
        event={event}
      />
    </Container>
  );
};
