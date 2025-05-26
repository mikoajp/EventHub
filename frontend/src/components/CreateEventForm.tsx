import React from 'react';
import {
  TextInput,
  Textarea,
  NumberInput,
  Button,
  Stack,
  Group,
  Card,
  Text,
  ActionIcon,
} from '@mantine/core';
import { DateTimePicker } from '@mantine/dates';
import { useForm } from '@mantine/form';
import { IconPlus, IconTrash } from '@tabler/icons-react';
import type { CreateEventData } from '../types';
import { useCreateEvent } from '../hooks/useEvents';

interface CreateEventFormProps {
  onSuccess?: () => void;
}

export const CreateEventForm: React.FC<CreateEventFormProps> = ({ onSuccess }) => {
  const createEventMutation = useCreateEvent();

  const form = useForm<CreateEventData>({
    initialValues: {
      name: '',
      description: '',
      eventDate: '',
      venue: '',
      maxTickets: 100,
      ticketTypes: [
        { name: 'General Admission', price: 2500, quantity: 80 },
        { name: 'VIP', price: 5000, quantity: 20 },
      ],
    },
    validate: {
      name: (value) => value.length < 3 ? 'Name must be at least 3 characters' : null,
      description: (value) => value.length < 10 ? 'Description must be at least 10 characters' : null,
      venue: (value) => value.length < 3 ? 'Venue must be at least 3 characters' : null,
      maxTickets: (value) => value < 1 ? 'Must have at least 1 ticket' : null,
      eventDate: (value) => {
        const date = new Date(value);
        return date <= new Date() ? 'Event date must be in the future' : null;
      },
      ticketTypes: {
        name: (value) => value.length < 2 ? 'Ticket type name is required' : null,
        price: (value) => value < 0 ? 'Price must be positive' : null,
        quantity: (value) => value < 1 ? 'Quantity must be at least 1' : null,
      },
    },
  });

  const handleSubmit = (values: CreateEventData) => {
    createEventMutation.mutate(values, {
      onSuccess: () => {
        form.reset();
        onSuccess?.();
      },
    });
  };

  const addTicketType = () => {
    form.insertListItem('ticketTypes', { name: '', price: 0, quantity: 0 });
  };

  const removeTicketType = (index: number) => {
    form.removeListItem('ticketTypes', index);
  };

  return (
    <form onSubmit={form.onSubmit(handleSubmit)}>
      <Stack gap="md">
        <TextInput
          label="Event Name"
          required
          {...form.getInputProps('name')}
        />

        <Textarea
          label="Description"
          required
          minRows={3}
          {...form.getInputProps('description')}
        />

        <DateTimePicker
          label="Event Date & Time"
          required
          valueFormat="DD/MM/YYYY HH:mm"
          {...form.getInputProps('eventDate')}
        />

        <TextInput
          label="Venue"
          required
          {...form.getInputProps('venue')}
        />

        <NumberInput
          label="Maximum Tickets"
          required
          min={1}
          {...form.getInputProps('maxTickets')}
        />

        <div>
          <Group justify="space-between" align="center" mb="md">
            <Text fw={500}>Ticket Types</Text>
            <Button
              leftSection={<IconPlus size={16} />}
              variant="light"
              onClick={addTicketType}
            >
              Add Ticket Type
            </Button>
          </Group>

          <Stack gap="md">
            {form.values.ticketTypes.map((_, index) => (
              <Card key={index} withBorder padding="md">
                <Group align="flex-end">
                  <TextInput
                    label="Name"
                    required
                    style={{ flex: 1 }}
                    {...form.getInputProps(`ticketTypes.${index}.name`)}
                  />
                  <NumberInput
                    label="Price (cents)"
                    required
                    min={0}
                    style={{ flex: 1 }}
                    {...form.getInputProps(`ticketTypes.${index}.price`)}
                  />
                  <NumberInput
                    label="Quantity"
                    required
                    min={1}
                    style={{ flex: 1 }}
                    {...form.getInputProps(`ticketTypes.${index}.quantity`)}
                  />
                  {form.values.ticketTypes.length > 1 && (
                    <ActionIcon
                      color="red"
                      variant="light"
                      onClick={() => removeTicketType(index)}
                    >
                      <IconTrash size={16} />
                    </ActionIcon>
                  )}
                </Group>
              </Card>
            ))}
          </Stack>
        </div>

        <Button
          type="submit"
          loading={createEventMutation.isPending}
          size="lg"
        >
          Create Event
        </Button>
      </Stack>
    </form>
  );
};