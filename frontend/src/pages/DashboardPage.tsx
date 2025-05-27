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
} from '@mantine/core';
import {
  IconEye,
  IconEdit,
  IconChartBar,
  IconCalendar,
  IconTicket,
  IconCurrency,
} from '@tabler/icons-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { format } from 'date-fns';
import { useEvents, usePublishEvent, useEventStatistics } from '../hooks/useEvents';

const StatCard: React.FC<{
  title: string;
  value: string | number;
  icon: React.ReactNode;
  color?: string;
}> = ({ title, value, icon, color = 'blue' }) => (
    <Card withBorder padding="lg">
      <Group justify="space-between">
        <div>
          <Text c="dimmed" size="sm" fw={500} tt="uppercase">
            {title}
          </Text>
          <Text fw={700} size="xl">
            {value}
          </Text>
        </div>
        <div style={{ color }}>{icon}</div>
      </Group>
    </Card>
);

export const DashboardPage: React.FC = () => {
  const [selectedEventId, setSelectedEventId] = useState<string>('');
  const [publishingEventId, setPublishingEventId] = useState<string | null>(null);

  const { data: allEvents, isLoading: eventsLoading } = useEvents();
  const publishEventMutation = usePublishEvent();
  const { data: statistics, isLoading: statsLoading } = useEventStatistics(
      selectedEventId,
      undefined,
      undefined
  );

  const myEvents = allEvents || [];

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
          <Loader size="lg" />
        </Center>
    );
  }

  return (
      <Container size="xl">
        <Stack gap="xl">
          <div>
            <Title order={1} mb="md">Dashboard</Title>
            <Text c="dimmed">Manage your events and view analytics</Text>
          </div>

          {/* Overview Stats */}
          <SimpleGrid cols={{ base: 2, sm: 4 }} spacing="lg">
            <StatCard
                title="Total Events"
                value={myEvents.length}
                icon={<IconCalendar size={24} />}
                color="blue"
            />
            <StatCard
                title="Published Events"
                value={publishedEvents.length}
                icon={<IconEye size={24} />}
                color="green"
            />
            <StatCard
                title="Tickets Sold"
                value={totalTicketsSold}
                icon={<IconTicket size={24} />}
                color="orange"
            />
            <StatCard
                title="Total Revenue"
                value={`$${(totalRevenue / 100).toFixed(2)}`}
                icon={<IconCurrency size={24} />}
                color="teal"
            />
          </SimpleGrid>

          <Grid>
            <Grid.Col span={{ base: 12, lg: 8 }}>
              <Stack gap="lg">
                {/* Event Analytics */}
                <Card withBorder padding="lg">
                  <Group justify="space-between" mb="md">
                    <Group>
                      <IconChartBar size={20} />
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
                        style={{ minWidth: 200 }}
                    />
                  </Group>

                  {selectedEventId && !statsLoading && statistics ? (
                      <Stack gap="md">
                        <SimpleGrid cols={3} spacing="md">
                          <div>
                            <Text size="sm" c="dimmed">Tickets Sold</Text>
                            <Text fw={600} size="lg">{statistics?.soldTickets || 0}</Text>
                          </div>
                          <div>
                            <Text size="sm" c="dimmed">Revenue</Text>
                            <Text fw={600} size="lg">${((statistics?.totalRevenue || 0) / 100).toFixed(2)}</Text>
                          </div>
                          <div>
                            <Text size="sm" c="dimmed">Conversion Rate</Text>
                            <Text fw={600} size="lg">{(statistics?.conversionRate || 0).toFixed(1)}%</Text>
                          </div>
                        </SimpleGrid>

                        {chartData.length > 0 && (
                            <div style={{ height: 300 }}>
                              <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={chartData}>
                                  <CartesianGrid strokeDasharray="3 3" />
                                  <XAxis dataKey="date" />
                                  <YAxis />
                                  <Tooltip />
                                  <Line
                                      type="monotone"
                                      dataKey="sales"
                                      stroke="#8884d8"
                                      strokeWidth={2}
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
                      <Center h={200}>
                        <Text c="dimmed">Select an event to view analytics</Text>
                      </Center>
                  )}
                </Card>

                {/* My Events List */}
                <Card withBorder padding="lg">
                  <Title order={3} mb="md">My Events</Title>
                  {myEvents.length === 0 ? (
                      <Center h={200}>
                        <Stack align="center" gap="md">
                          <Text c="dimmed">No events created yet</Text>
                          <Button onClick={() => window.location.href = '/create-event'}>
                            Create Your First Event
                          </Button>
                        </Stack>
                      </Center>
                  ) : (
                      <Table>
                        <Table.Thead>
                          <Table.Tr>
                            <Table.Th>Event Name</Table.Th>
                            <Table.Th>Date</Table.Th>
                            <Table.Th>Status</Table.Th>
                            <Table.Th>Sold</Table.Th>
                            <Table.Th>Actions</Table.Th>
                          </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                          {myEvents.map((event) => (
                              <Table.Tr key={event.id}>
                                <Table.Td>
                                  <Text fw={500}>{event.name}</Text>
                                  <Text size="sm" c="dimmed">{event.venue}</Text>
                                </Table.Td>
                                <Table.Td>
                                  {format(new Date(event.eventDate), 'MMM dd, yyyy')}
                                </Table.Td>
                                <Table.Td>
                                  <Badge variant="light" color={
                                    event.status === 'published' ? 'green' :
                                        event.status === 'draft' ? 'yellow' : 'red'
                                  }>
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
                                        size="xs"
                                    />
                                  </Stack>
                                </Table.Td>
                                <Table.Td>
                                  <Group gap="xs">
                                    <Button
                                        size="xs"
                                        variant="light"
                                        leftSection={<IconEye size={14} />}
                                        onClick={() => window.location.href = `/events/${event.id}`}
                                    >
                                      View
                                    </Button>
                                    {event.status === 'draft' && (
                                        <Button
                                            size="xs"
                                            variant="light"
                                            color="green"
                                            onClick={() => handlePublishEvent(event.id)}
                                            loading={publishingEventId === event.id}
                                        >
                                          Publish
                                        </Button>
                                    )}
                                    <Button
                                        size="xs"
                                        variant="light"
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
                  )}
                </Card>
              </Stack>
            </Grid.Col>

            <Grid.Col span={{ base: 12, lg: 4 }}>
              <Stack gap="lg">
                {/* Quick Actions */}
                <Card withBorder padding="lg">
                  <Title order={4} mb="md">Quick Actions</Title>
                  <Stack gap="sm">
                    <Button
                        fullWidth
                        onClick={() => window.location.href = '/create-event'}
                    >
                      Create New Event
                    </Button>
                    <Button variant="light" fullWidth>
                      Export Data
                    </Button>
                    <Button variant="light" fullWidth>
                      View Reports
                    </Button>
                  </Stack>
                </Card>

                {/* Recent Activity */}
                <Card withBorder padding="lg">
                  <Title order={4} mb="md">Recent Activity</Title>
                  <Stack gap="sm">
                    <Text size="sm" c="dimmed">
                      • 5 tickets sold for "Summer Music Festival"
                    </Text>
                    <Text size="sm" c="dimmed">
                      • "Tech Conference 2024" published
                    </Text>
                    <Text size="sm" c="dimmed">
                      • Payment processed for $150.00
                    </Text>
                    <Text size="sm" c="dimmed">
                      • New user registered
                    </Text>
                  </Stack>
                </Card>

                {/* Draft Events */}
                {draftEvents.length > 0 && (
                    <Card withBorder padding="lg">
                      <Title order={4} mb="md">Draft Events</Title>
                      <Stack gap="sm">
                        {draftEvents.slice(0, 3).map((event) => (
                            <Group key={event.id} justify="space-between">
                              <Text size="sm" lineClamp={1}>{event.name}</Text>
                              <Button
                                  size="xs"
                                  variant="light"
                                  onClick={() => handlePublishEvent(event.id)}
                                  loading={publishingEventId === event.id}
                              >
                                Publish
                              </Button>
                            </Group>
                        ))}
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