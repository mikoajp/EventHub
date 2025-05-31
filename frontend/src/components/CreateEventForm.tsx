import React from 'react';
import {
  TextInput,
  Textarea,
  NumberInput,
  Button,
  Stack,
  Group,
  Card,
  ActionIcon,
  Divider,
  Title,
  rem,
  Paper, Grid,
} from '@mantine/core';
import { DateTimePicker } from '@mantine/dates';
import { useForm } from '@mantine/form';
import { IconPlus, IconTrash, IconTicket, IconCalendarEvent, IconMapPin } from '@tabler/icons-react';
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
      <Paper withBorder p="lg" radius="lg" shadow="sm">
        <Title order={2} mb="xl" style={{ fontSize: rem(28) }}>
          Create New Event
        </Title>

        <form onSubmit={form.onSubmit(handleSubmit)}>
          <Stack gap="xl">
            {/* Basic Information Section */}
            <Card withBorder padding="lg" radius="md">
              <Title order={4} mb="md" style={{ fontSize: rem(18) }}>
                Event Details
              </Title>

              <Stack gap="md">
                <TextInput
                    label="Event Name"
                    required
                    placeholder="Enter event name"
                    size="md"
                    {...form.getInputProps('name')}
                />

                <Textarea
                    label="Description"
                    required
                    placeholder="Describe your event"
                    minRows={4}
                    size="md"
                    {...form.getInputProps('description')}
                />
              </Stack>
            </Card>

            {/* Date & Location Section */}
            <Card withBorder padding="lg" radius="md">
              <Title order={4} mb="md" style={{ fontSize: rem(18) }}>
                Date & Location
              </Title>

              <Grid gutter="md">
                <Grid.Col span={{ base: 12, md: 6 }}>
                  <DateTimePicker
                      label="Event Date & Time"
                      required
                      placeholder="Select date and time"
                      valueFormat="DD/MM/YYYY hh:mm A"
                      size="md"
                      leftSection={<IconCalendarEvent size={18} />}
                      {...form.getInputProps('eventDate')}
                  />
                </Grid.Col>
                <Grid.Col span={{ base: 12, md: 6 }}>
                  <TextInput
                      label="Venue"
                      required
                      placeholder="Enter venue name"
                      size="md"
                      leftSection={<IconMapPin size={18} />}
                      {...form.getInputProps('venue')}
                  />
                </Grid.Col>
              </Grid>
            </Card>

            {/* Tickets Section */}
            <Card withBorder padding="lg" radius="md">
              <Group justify="space-between" mb="md">
                <Title order={4} style={{ fontSize: rem(18) }}>
                  Ticket Options
                </Title>
                <Button
                    leftSection={<IconPlus size={18} />}
                    variant="light"
                    size="sm"
                    onClick={addTicketType}
                >
                  Add Ticket Type
                </Button>
              </Group>

              <NumberInput
                  label="Total Available Tickets"
                  required
                  min={1}
                  size="md"
                  mb="xl"
                  {...form.getInputProps('maxTickets')}
              />

              <Stack gap="md">
                {form.values.ticketTypes.map((_, index) => (
                    <Card key={index} withBorder padding="lg" radius="md">
                      <Group align="flex-end" wrap="nowrap">
                        <TextInput
                            label="Ticket Type"
                            required
                            placeholder="e.g., VIP, General Admission"
                            style={{ flex: 2 }}
                            size="sm"
                            leftSection={<IconTicket size={16} />}
                            {...form.getInputProps(`ticketTypes.${index}.name`)}
                        />
                        <NumberInput
                            label="Price ($)"
                            required
                            min={0}
                            style={{ flex: 1 }}
                            size="sm"
                            leftSection="$"
                            decimalScale={2}
                            {...form.getInputProps(`ticketTypes.${index}.price`, {
                              getInputProps: (value: number) => ({
                                value: value / 100,
                                onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                  const dollars = parseFloat(event.target.value) || 0;
                                  form.setFieldValue(`ticketTypes.${index}.price`, dollars * 100);
                                }
                              })
                            })}
                        />
                        <NumberInput
                            label="Quantity"
                            required
                            min={1}
                            style={{ flex: 1 }}
                            size="sm"
                            {...form.getInputProps(`ticketTypes.${index}.quantity`)}
                        />
                        {form.values.ticketTypes.length > 1 && (
                            <ActionIcon
                                color="red"
                                variant="light"
                                size="lg"
                                onClick={() => removeTicketType(index)}
                            >
                              <IconTrash size={18} />
                            </ActionIcon>
                        )}
                      </Group>
                    </Card>
                ))}
              </Stack>
            </Card>

            <Divider my="sm" />

            <Group justify="flex-end">
              <Button
                  type="submit"
                  loading={createEventMutation.isPending}
                  size="lg"
                  radius="md"
                  style={{ minWidth: rem(200) }}
              >
                Create Event
              </Button>
            </Group>
          </Stack>
        </form>
      </Paper>
  );
};