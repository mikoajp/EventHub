import React from 'react';
import {
  Card,
  Text,
  Badge,
  Button,
  Group,
  Stack,
  Progress,
} from '@mantine/core';
import { IconCalendar, IconMapPin, IconTicket, IconUsers } from '@tabler/icons-react';
import type { Event } from '../types';
import { format } from 'date-fns';

interface EventCardProps {
  event: Event;
  onViewDetails: (id: string) => void;
  onPurchaseTicket?: (id: string) => void;
  showActions?: boolean;
}

export const EventCard: React.FC<EventCardProps> = ({
  event,
  onViewDetails,
  onPurchaseTicket,
  showActions = true,
}) => {
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

  return (
    <Card shadow="sm" padding="lg" radius="md" withBorder>
      <Stack gap="md">
        <Group justify="space-between">
          <Text fw={500} size="lg" lineClamp={1}>
            {event.name}
          </Text>
          <Badge color={getStatusColor(event.status)} variant="light">
            {event.status.toUpperCase()}
          </Badge>
        </Group>

        <Text size="sm" c="dimmed" lineClamp={2}>
          {event.description}
        </Text>

        <Stack gap="xs">
          <Group gap="xs">
            <IconCalendar size={16} />
            <Text size="sm">
              {format(new Date(event.eventDate), 'PPP p')}
            </Text>
          </Group>

          <Group gap="xs">
            <IconMapPin size={16} />
            <Text size="sm">{event.venue}</Text>
          </Group>

          <Group gap="xs">
            <IconUsers size={16} />
            <Text size="sm">
              {event.ticketsSold} / {event.maxTickets} tickets sold
            </Text>
          </Group>
        </Stack>

        <div>
          <Group justify="space-between" mb={5}>
            <Text size="sm">Tickets sold</Text>
            <Text size="sm">{soldPercentage.toFixed(1)}%</Text>
          </Group>
          <Progress value={soldPercentage} size="sm" />
        </div>

        {event.ticketTypes.length > 0 && (
          <Group gap="xs">
            <IconTicket size={16} />
            <Text size="sm">
              From ${Math.min(...event.ticketTypes.map(tt => tt.price / 100)).toFixed(2)}
            </Text>
          </Group>
        )}

        {showActions && (
          <Group grow>
            <Button
              variant="light"
              onClick={() => onViewDetails(event.id)}
            >
              View Details
            </Button>
            {event.status === 'published' && event.availableTickets > 0 && onPurchaseTicket && (
              <Button
                onClick={() => onPurchaseTicket(event.id)}
              >
                Buy Tickets
              </Button>
            )}
          </Group>
        )}
      </Stack>
    </Card>
  );
};