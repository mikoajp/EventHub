import React, { useState, useEffect, useMemo } from 'react';
import {
  Modal,
  Stack,
  Text,
  Group,
  Button,
  NumberInput,
  Select,
  Divider,
  Badge,
  Alert,
  Loader,
  Box,
  Paper,
  Progress,
  rem,
  Title,
} from '@mantine/core';
import {
  IconAlertCircle,
  IconCheck,
  IconTicket,
  IconX,
  IconInfoCircle,
  IconCalendarEvent,
  IconMapPin
} from '@tabler/icons-react';
import type { Event } from '../types';
import { usePurchaseTicket, useTicketAvailability } from '../hooks/useTickets';

const ticketTypeTranslations: Record<string, string> = {
  'General Admission': 'General Admission',
  'VIP': 'VIP',
  'Early Bird': 'Early Bird',
  'Standard': 'Standard',
  'Premium': 'Premium',
  'Student': 'Student',
  'Regular': 'Regular',
  'Discounted': 'Discounted'
};

const translateTicketType = (name: string): string => {
  return ticketTypeTranslations[name] || name;
};

const formatAvailableTickets = (count: number): string => {
  if (count === 0) return 'Sold out';
  if (count === 1) return '1 left';
  return `${count} available`;
};

const getTicketQuantity = (ticketType: any): number => {
  return ticketType.quantity ?? ticketType.available ?? 0;
};

interface TicketPurchaseModalProps {
  isOpen: boolean;
  onClose: () => void;
  event: Event;
}

export const TicketPurchaseModal: React.FC<TicketPurchaseModalProps> = ({
                                                                          isOpen,
                                                                          onClose,
                                                                          event,
                                                                        }) => {
  const [selectedTicketType, setSelectedTicketType] = useState<string>('');
  const [quantity, setQuantity] = useState<number>(1);
  const [paymentMethodId] = useState<string>('pm_card_visa');
  const [purchaseSuccess, setPurchaseSuccess] = useState(false);

  const purchaseTicketMutation = usePurchaseTicket();
  const availabilityQuery = useTicketAvailability(event.id, selectedTicketType, quantity);

  const selectedType = useMemo(() =>
          event.ticketTypes?.find(tt => tt.id === selectedTicketType),
      [event.ticketTypes, selectedTicketType]
  );

  const totalPrice = selectedType ? (selectedType.price * quantity) : 0;
  const availableQuantity = selectedType ? getTicketQuantity(selectedType) : 0;
  const isLocallyAvailable = quantity <= availableQuantity && availableQuantity > 0;
  const isApiAvailable = availabilityQuery.data?.available ?? availabilityQuery.data?.quantity ?? true;
  const isAvailable = isLocallyAvailable && isApiAvailable;

  const availableTicketTypes = useMemo(() =>
          event.ticketTypes?.filter(tt => getTicketQuantity(tt) > 0) || [],
      [event.ticketTypes]
  );

  useEffect(() => {
    if (isOpen) {
      setSelectedTicketType('');
      setQuantity(1);
      setPurchaseSuccess(false);
    }
  }, [isOpen]);

  useEffect(() => {
    if (isOpen && !selectedTicketType && availableTicketTypes.length === 1) {
      setSelectedTicketType(availableTicketTypes[0].id);
    }
  }, [isOpen, selectedTicketType, availableTicketTypes]);

  useEffect(() => {
    if (selectedType) {
      const maxQuantity = getTicketQuantity(selectedType);
      if (quantity > maxQuantity) {
        setQuantity(Math.min(1, maxQuantity));
      }
    }
  }, [selectedTicketType, selectedType, quantity]);

  const handlePurchase = async () => {
    if (!selectedTicketType || !selectedType || !isAvailable) return;

    try {
      await purchaseTicketMutation.mutateAsync({
        eventId: event.id,
        ticketTypeId: selectedTicketType,
        quantity,
        paymentMethodId,
      });

      setPurchaseSuccess(true);
    } catch (error) {
      console.error('Purchase failed:', error);
    }
  };

  const handleClose = () => {
    if (!purchaseTicketMutation.isPending) {
      onClose();
    }
  };

  return (
      <Modal
          opened={isOpen}
          onClose={handleClose}
          title={
            <Group gap="sm">
              <IconTicket size={24} color="var(--mantine-color-blue-6)" />
              <Title order={3} style={{ fontSize: rem(20) }}>
                Purchase Tickets
              </Title>
            </Group>
          }
          size="md"
          radius="lg"
          padding="xl"
          closeOnClickOutside={!purchaseTicketMutation.isPending}
          closeOnEscape={!purchaseTicketMutation.isPending}
          centered
      >
        <Stack gap="lg">
          {purchaseSuccess ? (
              <Paper withBorder p="lg" radius="md" bg="green.0">
                <Stack align="center" gap="sm">
                  <Box
                      p={12}
                      style={{
                        backgroundColor: 'var(--mantine-color-green-1)',
                        borderRadius: '50%',
                      }}
                  >
                    <IconCheck size={32} color="var(--mantine-color-green-6)" />
                  </Box>
                  <Text fw={600} size="lg" c="green">
                    Purchase Successful!
                  </Text>
                  <Text size="sm" ta="center">
                    Your tickets for <Text span fw={500}>{event.name}</Text> have been confirmed.
                  </Text>
                  <Text size="sm" c="dimmed" ta="center">
                    A confirmation email has been sent to your address.
                  </Text>
                  <Button
                      fullWidth
                      mt="md"
                      onClick={handleClose}
                      radius="md"
                  >
                    Close
                  </Button>
                </Stack>
              </Paper>
          ) : purchaseTicketMutation.isPending ? (
              <Paper withBorder p="lg" radius="md">
                <Stack align="center" gap="md">
                  <Loader size="lg" />
                  <Text fw={500}>Processing your payment</Text>
                  <Progress value={100} animated size="sm" w="100%" />
                  <Text size="sm" c="dimmed" ta="center">
                    Please wait while we secure your tickets...
                  </Text>
                </Stack>
              </Paper>
          ) : (
              <>
                <Paper withBorder p="md" radius="md">
                  <Stack gap="xs">
                    <Text fw={600} size="lg">{event.name}</Text>
                    <Group gap="sm">
                      <IconCalendarEvent size={16} color="var(--mantine-color-gray-6)" />
                      <Text size="sm">
                        {new Date(event.eventDate).toLocaleString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit'
                        })}
                      </Text>
                    </Group>
                    <Group gap="sm">
                      <IconMapPin size={16} color="var(--mantine-color-gray-6)" />
                      <Text size="sm">{event.venue}</Text>
                    </Group>
                  </Stack>
                </Paper>

                {availableTicketTypes.length === 0 ? (
                    <Paper withBorder p="lg" radius="md" bg="red.0">
                      <Stack align="center" gap="sm">
                        <IconX size={32} color="var(--mantine-color-red-6)" />
                        <Text fw={600} c="red">All tickets sold out</Text>
                        <Text size="sm" ta="center">
                          There are no available tickets for this event.
                        </Text>
                      </Stack>
                    </Paper>
                ) : (
                    <>
                      <Select
                          label="Select ticket type"
                          placeholder="Choose an option"
                          data={availableTicketTypes.map(tt => ({
                            value: tt.id,
                            label: `${translateTicketType(tt.name)} - $${(tt.price / 100).toFixed(2)}`,
                            description: `${formatAvailableTickets(getTicketQuantity(tt))}`,
                          }))}
                          value={selectedTicketType}
                          onChange={(value) => {
                            setSelectedTicketType(value || '');
                            setQuantity(1);
                          }}
                          searchable
                          clearable
                          radius="md"
                          size="md"
                          nothingFoundMessage="No matching tickets found"
                      />

                      {selectedType && (
                          <Paper withBorder p="md" radius="md">
                            <Group justify="space-between" mb="xs">
                              <Text fw={500}>{translateTicketType(selectedType.name)}</Text>
                              <Badge
                                  color={availableQuantity > 0 ? 'green' : 'red'}
                                  variant="light"
                                  size="lg"
                                  radius="sm"
                              >
                                {formatAvailableTickets(availableQuantity)}
                              </Badge>
                            </Group>
                            <Text size="sm" c="dimmed">
                              ${(selectedType.price / 100).toFixed(2)} per ticket
                            </Text>
                          </Paper>
                      )}

                      {selectedTicketType && availableQuantity > 0 && (
                          <NumberInput
                              label="Number of tickets"
                              description={`Max ${Math.min(availableQuantity, 10)} per order`}
                              min={1}
                              max={Math.min(availableQuantity, 10)}
                              value={quantity}
                              onChange={(value) => setQuantity(Number(value) || 1)}
                              stepHoldDelay={500}
                              stepHoldInterval={100}
                              radius="md"
                              size="md"
                          />
                      )}

                      {selectedTicketType && availabilityQuery.isLoading && (
                          <Alert color="blue" variant="light" radius="md">
                            <Group>
                              <Loader size="xs" />
                              <Text size="sm">Verifying availability...</Text>
                            </Group>
                          </Alert>
                      )}

                      {selectedTicketType && quantity > 0 && !isLocallyAvailable && (
                          <Alert icon={<IconAlertCircle size={18} />} color="orange" radius="md">
                            <Text fw={500}>Not enough tickets</Text>
                            <Text size="sm">
                              You requested {quantity} but only {availableQuantity} are available.
                            </Text>
                          </Alert>
                      )}

                      {selectedTicketType && quantity > 0 && isLocallyAvailable && !isApiAvailable && (
                          <Alert icon={<IconAlertCircle size={18} />} color="orange" radius="md">
                            <Text fw={500}>Limited availability</Text>
                            <Text size="sm">
                              These tickets may have been purchased by others. Try a lower quantity.
                            </Text>
                          </Alert>
                      )}

                      {selectedTicketType && quantity > 0 && isAvailable && (
                          <Paper withBorder p="lg" radius="md" bg="blue.0">
                            <Stack gap="xs">
                              <Group justify="space-between">
                                <Text size="sm" c="dimmed">Ticket type</Text>
                                <Text size="sm" fw={500}>{translateTicketType(selectedType?.name || '')}</Text>
                              </Group>
                              <Group justify="space-between">
                                <Text size="sm" c="dimmed">Quantity</Text>
                                <Text size="sm" fw={500}>{quantity}</Text>
                              </Group>
                              <Group justify="space-between">
                                <Text size="sm" c="dimmed">Price each</Text>
                                <Text size="sm" fw={500}>${selectedType?.priceFormatted}</Text>
                              </Group>
                              <Divider my="xs" />
                              <Group justify="space-between">
                                <Text size="md" fw={600}>Total</Text>
                                <Text size="md" fw={600} c="blue">
                                  ${(totalPrice / 100).toFixed(2)}
                                </Text>
                              </Group>
                            </Stack>
                          </Paper>
                      )}
                    </>
                )}
              </>
          )}

          {!purchaseSuccess && !purchaseTicketMutation.isPending && availableTicketTypes.length > 0 && (
              <Group justify="space-between" mt="sm">
                <Button
                    variant="default"
                    onClick={handleClose}
                    radius="md"
                    size="md"
                >
                  Cancel
                </Button>
                <Button
                    onClick={handlePurchase}
                    disabled={
                        !selectedTicketType ||
                        !isAvailable ||
                        quantity < 1
                    }
                    loading={purchaseTicketMutation.isPending}
                    radius="md"
                    size="md"
                    leftSection={<IconTicket size={18} />}
                >
                  Confirm Purchase
                </Button>
              </Group>
          )}

          {!purchaseSuccess && !purchaseTicketMutation.isPending && (
              <Group gap="xs" justify="center">
                <IconInfoCircle size={16} color="var(--mantine-color-dimmed)" />
                <Text size="xs" c="dimmed">
                  Secure payment processing. All transactions are encrypted.
                </Text>
              </Group>
          )}
        </Stack>
      </Modal>
  );
};