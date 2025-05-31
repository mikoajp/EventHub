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
  Progress,
  Loader,
  Center,
  rem,
  Paper,
  Image,
  Box,
  AspectRatio,
} from '@mantine/core';
import {
  IconCalendar,
  IconMapPin,
  IconUsers,
  IconChartBar,
  IconTicket,
  IconArrowLeft,
  IconX,
  IconClock,
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
          <Loader size="lg" type="dots" />
        </Center>
    );
  }

  if (!event) {
    return (
        <Container py="xl">
          <Paper withBorder p="lg" radius="lg" shadow="sm">
            <Text c="red" fw={500} size="lg">Event not found</Text>
            <Button
                variant="subtle"
                leftSection={<IconArrowLeft size={16} />}
                mt="md"
                onClick={() => window.history.back()}
            >
              Back to events
            </Button>
          </Paper>
        </Container>
    );
  }

  const soldPercentage = (event.ticketsSold / event.maxTickets) * 100;

  return (
      <Container size="xl" py="xl">
        <Stack gap="xl">
          {/* Header with back button */}
          <Button
              variant="subtle"
              leftSection={<IconArrowLeft size={18} />}
              onClick={() => window.history.back()}
              w="fit-content"
              px={0}
          >
            Back to events
          </Button>

          {/* Event Header */}
          <Box
              style={{
                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                borderRadius: rem(16),
                overflow: 'hidden',
                position: 'relative',
                marginBottom: rem(24)
              }}
          >
            {/* Background Pattern */}
            <Box
                style={{
                  position: 'absolute',
                  top: 0,
                  left: 0,
                  right: 0,
                  bottom: 0,
                  opacity: 0.1,
                  backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Ccircle cx='7' cy='7' r='2'/%3E%3Ccircle cx='27' cy='7' r='2'/%3E%3Ccircle cx='47' cy='7' r='2'/%3E%3Ccircle cx='7' cy='27' r='2'/%3E%3Ccircle cx='27' cy='27' r='2'/%3E%3Ccircle cx='47' cy='27' r='2'/%3E%3Ccircle cx='7' cy='47' r='2'/%3E%3Ccircle cx='27' cy='47' r='2'/%3E%3Ccircle cx='47' cy='47' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
                }}
            />

            <Box p="xl" style={{ position: 'relative', zIndex: 1 }}>
              <Grid gutter="xl">
                <Grid.Col span={{ base: 12, md: 8 }}>
                  <Stack gap="lg">
                    {/* Status and Availability Badges */}
                    <Group gap="md">
                      <Badge
                          size="xl"
                          variant="filled"
                          style={{
                            background: event.status === 'published'
                                ? 'linear-gradient(45deg, #10b981, #059669)'
                                : event.status === 'cancelled'
                                    ? 'linear-gradient(45deg, #ef4444, #dc2626)'
                                    : 'linear-gradient(45deg, #f59e0b, #d97706)',
                            border: 'none',
                            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.2)',
                            textTransform: 'uppercase',
                            letterSpacing: '0.5px',
                            fontWeight: 700
                          }}
                      >
                        <Group gap="xs">
                          {event.status === 'published' && <IconTicket size={16} />}
                          {event.status === 'cancelled' && <IconX size={16} />}
                          {event.status === 'draft' && <IconClock size={16} />}
                          {event.status}
                        </Group>
                      </Badge>

                      {event.availableTickets > 0 && event.status === 'published' && (
                          <Badge
                              size="lg"
                              variant="light"
                              style={{
                                background: 'rgba(255, 255, 255, 0.25)',
                                color: 'white',
                                backdropFilter: 'blur(10px)',
                                border: '1px solid rgba(255, 255, 255, 0.3)',
                                fontWeight: 600
                              }}
                          >
                            <Group gap="xs">
                              <IconUsers size={14} />
                              {event.availableTickets} Available
                            </Group>
                          </Badge>
                      )}
                    </Group>

                    {/* Event Title */}
                    <Title
                        order={1}
                        style={{
                          fontSize: rem(42),
                          fontWeight: 800,
                          color: 'white',
                          textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
                          lineHeight: 1.2,
                          marginBottom: rem(8)
                        }}
                    >
                      {event.name}
                    </Title>

                    {/* Organizer Info */}
                    <Group gap="sm" align="center">
                      <Box
                          style={{
                            background: 'rgba(255, 255, 255, 0.15)',
                            borderRadius: rem(20),
                            padding: `${rem(4)} ${rem(12)}`,
                            backdropFilter: 'blur(10px)',
                            border: '1px solid rgba(255, 255, 255, 0.2)'
                          }}
                      >
                        <Text c="white" size="sm" fw={500}>
                          Hosted by <Text span fw={700}>{event.organizer?.fullName || 'Event Organizer'}</Text>
                        </Text>
                      </Box>
                    </Group>

                    {/* Quick Info Pills */}
                    <Group gap="md" mt="sm">
                      <Group gap="xs" style={{
                        background: 'rgba(255, 255, 255, 0.15)',
                        borderRadius: rem(20),
                        padding: `${rem(8)} ${rem(12)}`,
                        backdropFilter: 'blur(10px)',
                        border: '1px solid rgba(255, 255, 255, 0.2)'
                      }}>
                        <IconCalendar size={16} color="white" />
                        <Text c="white" size="sm" fw={500}>
                          {format(new Date(event.eventDate), 'MMM dd, yyyy')}
                        </Text>
                      </Group>

                      <Group gap="xs" style={{
                        background: 'rgba(255, 255, 255, 0.15)',
                        borderRadius: rem(20),
                        padding: `${rem(8)} ${rem(12)}`,
                        backdropFilter: 'blur(10px)',
                        border: '1px solid rgba(255, 255, 255, 0.2)'
                      }}>
                        <IconMapPin size={16} color="white" />
                        <Text c="white" size="sm" fw={500}>
                          {event.venue}
                        </Text>
                      </Group>
                    </Group>
                  </Stack>
                </Grid.Col>

                <Grid.Col span={{ base: 12, md: 4 }}>
                  <Stack gap="lg" align="flex-end">
                    {/* Event Image */}
                    <AspectRatio ratio={16 / 9} w="100%">
                      <Image
                          alt={event.name}
                          radius="lg"
                          style={{
                            border: '3px solid rgba(255, 255, 255, 0.3)',
                            boxShadow: '0 8px 24px rgba(0, 0, 0, 0.2)'
                          }}
                      />
                    </AspectRatio>

                    {/* CTA Button */}
                    {event.status === 'published' && event.availableTickets > 0 && (
                        <Button
                            size="xl"
                            radius="xl"
                            onClick={() => setIsPurchaseModalOpen(true)}
                            leftSection={<IconTicket size={20} />}
                            style={{
                              background: 'linear-gradient(45deg, #ffffff, #f8fafc)',
                              color: '#374151',
                              border: 'none',
                              fontWeight: 700,
                              fontSize: rem(16),
                              padding: `${rem(16)} ${rem(32)}`,
                              boxShadow: '0 8px 24px rgba(0, 0, 0, 0.15)',
                              transition: 'all 0.2s ease',
                              '&:hover': {
                                transform: 'translateY(-2px)',
                                boxShadow: '0 12px 32px rgba(0, 0, 0, 0.2)'
                              }
                            }}
                            w={{ base: '100%', md: 'auto' }}
                        >
                          Get Tickets Now
                        </Button>
                    )}

                    {/* Sold Out Badge */}
                    {event.status === 'published' && event.availableTickets === 0 && (
                        <Badge
                            size="xl"
                            variant="filled"
                            color="red"
                            style={{
                              background: 'linear-gradient(45deg, #ef4444, #dc2626)',
                              border: 'none',
                              boxShadow: '0 4px 12px rgba(0, 0, 0, 0.2)',
                              padding: `${rem(12)} ${rem(24)}`,
                              fontSize: rem(14),
                              fontWeight: 700
                            }}
                        >
                          <Group gap="xs">
                            <IconX size={16} />
                            Sold Out
                          </Group>
                        </Badge>
                    )}
                  </Stack>
                </Grid.Col>
              </Grid>
            </Box>
          </Box>

          <Grid gutter="xl">
            <Grid.Col span={{ base: 12, md: 8 }}>
              <Stack gap="lg">
                {/* Event Description */}
                <Card withBorder padding="xl" radius="lg" shadow="sm">
                  <Title order={3} mb="md" style={{ fontSize: rem(24) }}>
                    About This Event
                  </Title>
                  <Text size="lg" style={{ lineHeight: 1.6 }}>
                    {event.description}
                  </Text>
                </Card>

                {/* Event Information */}
                <Card withBorder padding="xl" radius="lg" shadow="sm">
                  <Title order={3} mb="lg" style={{ fontSize: rem(24) }}>
                    Event Information
                  </Title>
                  <Stack gap="xl">
                    <Group align="flex-start">
                      <Box
                          p="sm"
                          style={{
                            background: 'var(--mantine-color-blue-light)',
                            borderRadius: 'var(--mantine-radius-md)',
                          }}
                      >
                        <IconCalendar size={24} color="var(--mantine-color-blue-6)" />
                      </Box>
                      <div>
                        <Text fw={600} size="lg">Date & Time</Text>
                        <Text c="dimmed" mt={4}>
                          {format(new Date(event.eventDate), 'EEEE, MMMM do, yyyy')}
                        </Text>
                        <Text c="dimmed">
                          {format(new Date(event.eventDate), 'h:mm a')}
                        </Text>
                      </div>
                    </Group>

                    <Group align="flex-start">
                      <Box
                          p="sm"
                          style={{
                            background: 'var(--mantine-color-red-light)',
                            borderRadius: 'var(--mantine-radius-md)',
                          }}
                      >
                        <IconMapPin size={24} color="var(--mantine-color-red-6)" />
                      </Box>
                      <div>
                        <Text fw={600} size="lg">Location</Text>
                        <Text c="dimmed" mt={4}>{event.venue}</Text>
                      </div>
                    </Group>

                    <Group align="flex-start">
                      <Box
                          p="sm"
                          style={{
                            background: 'var(--mantine-color-green-light)',
                            borderRadius: 'var(--mantine-radius-md)',
                          }}
                      >
                        <IconUsers size={24} color="var(--mantine-color-green-6)" />
                      </Box>
                      <div>
                        <Text fw={600} size="lg">Capacity</Text>
                        <Text c="dimmed" mt={4}>
                          {event.maxTickets} total tickets ({event.ticketsSold} already sold)
                        </Text>
                      </div>
                    </Group>
                  </Stack>
                </Card>
              </Stack>
            </Grid.Col>

            <Grid.Col span={{ base: 12, md: 4 }}>
              <Stack gap="lg">
                {/* Ticket Sales */}
                <Card withBorder padding="xl" radius="lg" shadow="sm">
                  <Title order={4} mb="lg">Ticket Availability</Title>
                  <Stack gap="sm">
                    <Group justify="space-between">
                      <Text size="sm" c="dimmed">Sold</Text>
                      <Text size="sm" fw={600}>
                        {event.ticketsSold} / {event.maxTickets}
                      </Text>
                    </Group>
                    <Progress
                        value={soldPercentage}
                        size="lg"
                        radius="xl"
                        color={soldPercentage > 90 ? 'red' : soldPercentage > 50 ? 'yellow' : 'green'}
                    />
                    <Text size="sm" c="dimmed" ta="center">
                      {soldPercentage.toFixed(1)}% sold
                    </Text>
                  </Stack>
                </Card>

                {/* Ticket Types */}
                <Card withBorder padding="xl" radius="lg" shadow="sm">
                  <Title order={4} mb="lg">Ticket Options</Title>
                  <Stack gap="md">
                    {event.ticketTypes.map((ticketType) => (
                        <Paper
                            key={ticketType.id}
                            withBorder
                            p="md"
                            radius="md"
                            style={{
                              borderLeft: `4px solid ${
                                  ticketType.quantity > 0 ?
                                      'var(--mantine-color-green-6)' :
                                      'var(--mantine-color-red-6)'
                              }`
                            }}
                        >
                          <Group justify="space-between" mb="xs">
                            <Text fw={600} size="lg">{ticketType.name}</Text>
                            <Text fw={600} size="lg">${ticketType.priceFormatted}</Text>
                          </Group>
                          <Group justify="space-between">
                            <Text size="sm" c="dimmed">
                              {ticketType.quantity} remaining
                            </Text>
                            <Badge
                                color={ticketType.quantity > 0 ? 'green' : 'red'}
                                variant="light"
                                radius="sm"
                                size="lg"
                            >
                              {ticketType.quantity > 0 ? 'Available' : 'Sold Out'}
                            </Badge>
                          </Group>
                        </Paper>
                    ))}
                  </Stack>

                  {event.status === 'published' && event.availableTickets > 0 && (
                      <Button
                          fullWidth
                          size="lg"
                          mt="lg"
                          radius="md"
                          onClick={() => setIsPurchaseModalOpen(true)}
                          leftSection={<IconTicket size={20} />}
                      >
                        Get Tickets Now
                      </Button>
                  )}
                </Card>

                {/* Statistics */}
                {statistics && !statsLoading && (
                    <Card withBorder padding="xl" radius="lg" shadow="sm">
                      <Group mb="md">
                        <Box
                            p="sm"
                            style={{
                              background: 'var(--mantine-color-purple-light)',
                              borderRadius: 'var(--mantine-radius-md)',
                            }}
                        >
                          <IconChartBar size={24} color="var(--mantine-color-purple-6)" />
                        </Box>
                        <Title order={4}>Event Stats</Title>
                      </Group>
                      <Stack gap="sm">
                        <Group justify="space-between">
                          <Text size="sm" c="dimmed">Total Revenue</Text>
                          <Text size="sm" fw={600}>
                            ${((statistics?.totalRevenue || 0) / 100).toFixed(2)}
                          </Text>
                        </Group>
                        <Group justify="space-between">
                          <Text size="sm" c="dimmed">Conversion Rate</Text>
                          <Text size="sm" fw={600}>
                            {(statistics?.conversionRate || 0).toFixed(1)}%
                          </Text>
                        </Group>
                      </Stack>
                    </Card>
                )}
              </Stack>
            </Grid.Col>
          </Grid>
        </Stack>

        <TicketPurchaseModal
            isOpen={isPurchaseModalOpen}
            onClose={() => setIsPurchaseModalOpen(false)}
            event={event}
        />
      </Container>
  );
};