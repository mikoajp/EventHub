import React, { useState } from 'react';
import {
  Container,
  Title,
  Text,
  Stack,
  Grid,
  Card,
  Group,
  Button,
  Select,
  SimpleGrid,
  Center,
  Loader,
  Table,
  Progress,
  Badge,
  rem,
  Paper,
  Box,
} from '@mantine/core';
import {
  IconEye,
  IconEdit,
  IconChartBar,
  IconCalendar,
  IconTicket,
  IconCurrency,
  IconPlus,
  IconDownload,
  IconReport,
  IconClock,
  IconFlame,
} from '@tabler/icons-react';
import { LineChart, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Line } from 'recharts';
import { format } from 'date-fns';
import { useEvents, usePublishEvent, useEventStatistics } from '../hooks/useEvents';

const StatCard: React.FC<{
  title: string;
  value: string | number;
  icon: React.ReactNode;
  color?: string;
  trend?: 'up' | 'down' | 'neutral';
}> = ({ title, value, icon, color = 'blue', trend }) => (
    <Card withBorder p="lg" radius="lg" shadow="sm" style={{ height: '100%' }}>
      <Group justify="space-between" align="flex-start">
        <div>
          <Text c="dimmed" size="sm" fw={500} tt="uppercase">
            {title}
          </Text>
          <Text fw={700} size={rem(28)} mt={4}>
            {value}
          </Text>
        </div>
        <Box
            p={10}
            style={{
              backgroundColor: `var(--mantine-color-${color}-light)`,
              borderRadius: 'var(--mantine-radius-md)',
              color: `var(--mantine-color-${color}-7)`,
            }}
        >
          {icon}
        </Box>
      </Group>
      {trend && (
          <Text size="sm" mt="sm" c={trend === 'up' ? 'teal' : trend === 'down' ? 'red' : 'dimmed'}>
            <Group gap={4}>
              {trend === 'up' ? '↑' : trend === 'down' ? '↓' : '→'}
              {trend === 'up' ? '12% increase' : trend === 'down' ? '5% decrease' : 'No change'}
            </Group>
          </Text>
      )}
    </Card>
);

export const DashboardPage: React.FC = () => {
  const [selectedEventId, setSelectedEventId] = useState<string>('');
  const [publishingEventId, setPublishingEventId] = useState<string | null>(null);

  // Get all events (including drafts) for the dashboard
  const { data: eventsResponse, isLoading: eventsLoading } = useEvents({ 
    status: ['draft', 'published', 'cancelled', 'completed'] 
  });
  const publishEventMutation = usePublishEvent();
  const { data: statistics, isLoading: statsLoading } = useEventStatistics(
      selectedEventId,
      undefined,
      undefined
  );

  const myEvents = eventsResponse?.events || [];
  const draftEvents = myEvents.filter(e => e.status === 'draft');
  const publishedEvents = myEvents.filter(e => e.status === 'published');
  const totalTicketsSold = myEvents.reduce((sum, event) => sum + event.ticketsSold, 0);
  const totalRevenue = statistics?.totalRevenue || 0;

  const handlePublishEvent = async (eventId: string) => {
    setPublishingEventId(eventId);
    try {
      await publishEventMutation.mutateAsync(eventId);
    } catch (error) {
      console.error('Failed to publish event:', error);
    } finally {
      setPublishingEventId(null);
    }
  };

  const chartData = statistics?.salesTimeline?.map(item => ({
    date: format(new Date(item.saleDate), 'MMM dd'),
    sales: item.dailySales,
    revenue: item.dailyRevenue / 100,
  })) || [];

  if (eventsLoading) {
    return (
        <Center h={400}>
          <Loader size="lg" type="dots" />
        </Center>
    );
  }

  return (
      <Container size="xl" py="xl">
        <Stack gap="xl">
          {/* Header */}
          <Group justify="space-between" align="flex-end">
            <div>
              <Title order={1} style={{ fontSize: rem(36) }} mb="xs">
                Event Dashboard
              </Title>
              <Text size="lg" c="dimmed">
                Manage your events and track performance
              </Text>
            </div>
            <Button
                size="lg"
                radius="md"
                leftSection={<IconPlus size={20} />}
                onClick={() => window.location.href = '/create-event'}
            >
              New Event
            </Button>
          </Group>

          {/* Overview Stats */}
          <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} spacing="lg">
            <StatCard
                title="Total Events"
                value={myEvents.length}
                icon={<IconCalendar size={24} />}
                color="blue"
                trend="up"
            />
            <StatCard
                title="Published"
                value={publishedEvents.length}
                icon={<IconEye size={24} />}
                color="green"
                trend="up"
            />
            <StatCard
                title="Tickets Sold"
                value={totalTicketsSold}
                icon={<IconTicket size={24} />}
                color="orange"
                trend="up"
            />
            <StatCard
                title="Total Revenue"
                value={`$${(totalRevenue / 100).toFixed(2)}`}
                icon={<IconCurrency size={24} />}
                color="teal"
                trend="up"
            />
          </SimpleGrid>

          <Grid gutter="xl">
            <Grid.Col span={{ base: 12, lg: 8 }}>
              <Stack gap="lg">
                {/* Event Analytics */}
                <Card withBorder p="lg" radius="lg" shadow="sm">
                  <Group justify="space-between" mb="xl">
                    <Group gap="sm">
                      <Box
                          p={8}
                          style={{
                            background: 'var(--mantine-color-blue-light)',
                            borderRadius: 'var(--mantine-radius-md)',
                          }}
                      >
                        <IconChartBar size={20} color="var(--mantine-color-blue-7)" />
                      </Box>
                      <Title order={3}>Event Analytics</Title>
                    </Group>
                    <Select
                        placeholder="Select event"
                        data={myEvents.map(event => ({
                          value: event.id,
                          label: event.name,
                        }))}
                        value={selectedEventId}
                        onChange={(value) => setSelectedEventId(value || '')}
                        style={{ minWidth: 250 }}
                        radius="md"
                        size="sm"
                    />
                  </Group>

                  {selectedEventId && !statsLoading && statistics ? (
                      <Stack gap="xl">
                        <SimpleGrid cols={3} spacing="md">
                          <div>
                            <Text size="sm" c="dimmed">Tickets Sold</Text>
                            <Text fw={700} size={rem(24)}>{statistics?.soldTickets || 0}</Text>
                          </div>
                          <div>
                            <Text size="sm" c="dimmed">Revenue</Text>
                            <Text fw={700} size={rem(24)}>${((statistics?.totalRevenue || 0) / 100).toFixed(2)}</Text>
                          </div>
                          <div>
                            <Text size="sm" c="dimmed">Conversion</Text>
                            <Text fw={700} size={rem(24)}>{(statistics?.conversionRate || 0).toFixed(1)}%</Text>
                          </div>
                        </SimpleGrid>

                        {chartData.length > 0 && (
                            <div style={{ height: 300 }}>
                              <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={chartData}>
                                  <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                  <XAxis dataKey="date" />
                                  <YAxis />
                                  <Tooltip
                                      contentStyle={{
                                        borderRadius: 'var(--mantine-radius-md)',
                                        boxShadow: 'var(--mantine-shadow-md)',
                                      }}
                                  />
                                  <Line
                                      type="monotone"
                                      dataKey="sales"
                                      stroke="#4d6ef7"
                                      strokeWidth={2}
                                      dot={false}
                                      name="Daily Sales"
                                  />
                                </LineChart>
                              </ResponsiveContainer>
                            </div>
                        )}
                      </Stack>
                  ) : selectedEventId && statsLoading ? (
                      <Center h={200}>
                        <Loader />
                      </Center>
                  ) : (
                      <Center h={200} bg="var(--mantine-color-gray-0)" style={{ borderRadius: 'var(--mantine-radius-md)' }}>
                        <Stack align="center" gap="xs">
                          <IconChartBar size={32} color="var(--mantine-color-gray-5)" />
                          <Text c="dimmed">Select an event to view analytics</Text>
                        </Stack>
                      </Center>
                  )}
                </Card>

                {/* My Events List */}
                <Card withBorder p={0} radius="lg" shadow="sm">
                  <Group p="lg" justify="space-between">
                    <Title order={3}>My Events</Title>
                    <Button variant="subtle" size="sm">
                      View All
                    </Button>
                  </Group>

                  {myEvents.length === 0 ? (
                      <Center h={200}>
                        <Stack align="center" gap="md">
                          <Text c="dimmed">No events created yet</Text>
                          <Button
                              onClick={() => window.location.href = '/create-event'}
                              leftSection={<IconPlus size={16} />}
                          >
                            Create Your First Event
                          </Button>
                        </Stack>
                      </Center>
                  ) : (
                      <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                          <Table.Thead>
                            <Table.Tr>
                              <Table.Th>Event</Table.Th>
                              <Table.Th>Date</Table.Th>
                              <Table.Th>Status</Table.Th>
                              <Table.Th>Sales</Table.Th>
                              <Table.Th>Actions</Table.Th>
                            </Table.Tr>
                          </Table.Thead>
                          <Table.Tbody>
                            {myEvents.map((event) => (
                                <Table.Tr key={event.id}>
                                  <Table.Td>
                                    <Text fw={600}>{event.name}</Text>
                                    <Text size="sm" c="dimmed">{event.venue}</Text>
                                  </Table.Td>
                                  <Table.Td>
                                    <Text size="sm">{format(new Date(event.eventDate), 'MMM dd, yyyy')}</Text>
                                    <Text size="xs" c="dimmed">{format(new Date(event.eventDate), 'h:mm a')}</Text>
                                  </Table.Td>
                                  <Table.Td>
                                    <Badge
                                        variant="light"
                                        color={
                                          event.status === 'published' ? 'green' :
                                              event.status === 'draft' ? 'yellow' : 'red'
                                        }
                                        radius="sm"
                                        size="md"
                                    >
                                      {event.status}
                                    </Badge>
                                  </Table.Td>
                                  <Table.Td>
                                    <Stack gap={4}>
                                      <Text size="sm">
                                        {event.ticketsSold} / {event.maxTickets}
                                      </Text>
                                      <Progress
                                          value={(event.ticketsSold / event.maxTickets) * 100}
                                          size="sm"
                                          radius="xl"
                                          color={
                                            (event.ticketsSold / event.maxTickets) > 0.8 ? 'red' :
                                                (event.ticketsSold / event.maxTickets) > 0.5 ? 'yellow' : 'green'
                                          }
                                      />
                                    </Stack>
                                  </Table.Td>
                                  <Table.Td>
                                    <Group gap="xs" wrap="nowrap">
                                      <Button
                                          size="xs"
                                          variant="subtle"
                                          leftSection={<IconEye size={14} />}
                                          onClick={() => window.location.href = `/events/${event.id}`}
                                      >
                                        View
                                      </Button>
                                      {event.status === 'draft' && (
                                          <Button
                                              size="xs"
                                              variant="subtle"
                                              color="green"
                                              leftSection={<IconFlame size={14} />}
                                              onClick={() => handlePublishEvent(event.id)}
                                              loading={publishingEventId === event.id}
                                          >
                                            Publish
                                          </Button>
                                      )}
                                      <Button
                                          size="xs"
                                          variant="subtle"
                                          leftSection={<IconEdit size={14} />}
                                      >
                                        Edit
                                      </Button>
                                    </Group>
                                  </Table.Td>
                                </Table.Tr>
                            ))}
                          </Table.Tbody>
                        </Table>
                      </Table.ScrollContainer>
                  )}
                </Card>
              </Stack>
            </Grid.Col>

            <Grid.Col span={{ base: 12, lg: 4 }}>
              <Stack gap="lg">
                {/* Quick Actions */}
                <Card withBorder p="lg" radius="lg" shadow="sm">
                  <Title order={4} mb="lg">Quick Actions</Title>
                  <Stack gap="sm">
                    <Button
                        fullWidth
                        variant="light"
                        size="md"
                        leftSection={<IconPlus size={18} />}
                        onClick={() => window.location.href = '/create-event'}
                    >
                      Create Event
                    </Button>
                    <Button
                        fullWidth
                        variant="light"
                        size="md"
                        leftSection={<IconDownload size={18} />}
                    >
                      Export Data
                    </Button>
                    <Button
                        fullWidth
                        variant="light"
                        size="md"
                        leftSection={<IconReport size={18} />}
                    >
                      Generate Report
                    </Button>
                  </Stack>
                </Card>

                {/* Recent Activity */}
                <Card withBorder p="lg" radius="lg" shadow="sm">
                  <Group justify="space-between" mb="md">
                    <Title order={4}>Recent Activity</Title>
                    <IconClock size={20} color="var(--mantine-color-gray-5)" />
                  </Group>
                  <Stack gap="sm">
                    {[
                      { id: 1, text: '5 tickets sold for "Summer Music Festival"', time: '2 hours ago' },
                      { id: 2, text: '"Tech Conference 2024" published', time: '1 day ago' },
                      { id: 3, text: 'Payment processed for $150.00', time: '2 days ago' },
                      { id: 4, text: 'New user registered', time: '3 days ago' },
                    ].map((activity) => (
                        <Paper key={activity.id} p="sm" withBorder radius="md">
                          <Text size="sm">{activity.text}</Text>
                          <Text size="xs" c="dimmed" mt={4}>{activity.time}</Text>
                        </Paper>
                    ))}
                  </Stack>
                </Card>

                {/* Draft Events */}
                {draftEvents.length > 0 && (
                    <Card withBorder p="lg" radius="lg" shadow="sm">
                      <Group justify="space-between" mb="md">
                        <Title order={4}>Drafts ({draftEvents.length})</Title>
                        <Badge variant="light" color="yellow">
                          Needs Attention
                        </Badge>
                      </Group>
                      <Stack gap="sm">
                        {draftEvents.slice(0, 3).map((event) => (
                            <Group key={event.id} justify="space-between" wrap="nowrap">
                              <Text size="sm" lineClamp={1} style={{ flex: 1 }}>{event.name}</Text>
                              <Button
                                  size="xs"
                                  variant="light"
                                  color="green"
                                  onClick={() => handlePublishEvent(event.id)}
                                  loading={publishingEventId === event.id}
                              >
                                Publish
                              </Button>
                            </Group>
                        ))}
                        {draftEvents.length > 3 && (
                            <Button variant="subtle" size="sm" mt="sm">
                              View All Drafts
                            </Button>
                        )}
                      </Stack>
                    </Card>
                )}
              </Stack>
            </Grid.Col>
          </Grid>
        </Stack>
      </Container>
  );
};