import React from 'react';
import {
  Card,
  Text,
  Badge,
  Button,
  Group,
  Stack,
  Image,
  AspectRatio,
  rem,
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

  // Placeholder image function (deterministic based on event ID or name length)
  const getEventImage = (id: string) => {
    const images = [
      'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&w=600&q=80',
      'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=600&q=80',
      'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=600&q=80',
      'https://images.unsplash.com/photo-1540039155733-5bb30b53aa14?auto=format&fit=crop&w=600&q=80',
    ];
    // Simple hash to pick an image
    const index = id.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % images.length;
    return images[index];
  };

  return (
    <Card 
      shadow="sm" 
      padding="lg" 
      radius="md" 
      withBorder
      style={{
        transition: 'transform 0.2s ease, box-shadow 0.2s ease',
        cursor: 'pointer',
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.transform = 'translateY(-4px)';
        e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.transform = 'translateY(0)';
        e.currentTarget.style.boxShadow = 'var(--mantine-shadow-sm)';
      }}
      onClick={() => onViewDetails(event.id)}
    >
      <Card.Section>
        <AspectRatio ratio={16 / 9}>
          <Image
            src={getEventImage(event.id)}
            alt={event.name}
            fallbackSrc="https://placehold.co/600x400?text=Event"
          />
        </AspectRatio>
      </Card.Section>

      <Stack gap="md" mt="md">
        <Group justify="space-between" wrap="nowrap" align="flex-start">
          <Text fw={600} size="lg" lineClamp={2} style={{ lineHeight: 1.2 }}>
            {event.name}
          </Text>
          <Badge color={getStatusColor(event.status)} variant="light">
            {event.status.toUpperCase()}
          </Badge>
        </Group>

        <Text size="sm" c="dimmed" lineClamp={2} h={rem(40)}>
          {event.description}
        </Text>

        <Stack gap="xs">
          <Group gap="xs">
            <IconCalendar size={16} style={{ color: 'var(--mantine-color-blue-6)' }} />
            <Text size="sm" fw={500}>
              {format(new Date(event.eventDate), 'MMM dd, yyyy • h:mm a')}
            </Text>
          </Group>

          <Group gap="xs">
            <IconMapPin size={16} style={{ color: 'var(--mantine-color-red-6)' }} />
            <Text size="sm" lineClamp={1}>{event.venue}</Text>
          </Group>
        </Stack>

        <Group justify="space-between" mt="xs">
          <Group gap={4}>
            <IconUsers size={16} style={{ color: 'var(--mantine-color-dimmed)' }} />
            <Text size="sm" c="dimmed">
              {event.availableTickets} left
            </Text>
          </Group>
          {Array.isArray(event.ticketTypes) && event.ticketTypes.length > 0 && (
            <Group gap={4}>
              <IconTicket size={16} style={{ color: 'var(--mantine-color-green-6)' }} />
              <Text size="sm" fw={700} c="green.8">
                From ${Math.min(...event.ticketTypes.map(tt => tt.price / 100)).toFixed(2)}
              </Text>
            </Group>
          )}
        </Group>

        {showActions && (
          <Button
            fullWidth
            variant="light"
            color="blue"
            mt="xs"
            onClick={(e) => {
              e.stopPropagation(); // Prevent card click
              if (event.status === 'published' && event.availableTickets > 0 && onPurchaseTicket) {
                onPurchaseTicket(event.id);
              } else {
                onViewDetails(event.id);
              }
            }}
          >
            {event.status === 'published' && event.availableTickets > 0 ? 'Get Tickets' : 'View Details'}
          </Button>
        )}
      </Stack>
    </Card>
  );
};