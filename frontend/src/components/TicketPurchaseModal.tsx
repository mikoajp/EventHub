import React, { useState, useEffect, useMemo } from 'react';
import {
  Modal,
  Stack,
  Text,
  Group,
  Button,
  NumberInput,
  Select,
  Card,
  Divider,
  Badge,
  Alert,
  Loader,
  Center,
} from '@mantine/core';
import { IconAlertCircle, IconCheck, IconTicket } from '@tabler/icons-react';
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
  if (count === 0) return 'No tickets available';
  if (count === 1) return '1 ticket available';
  return `${count} tickets available`;
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
          event.ticketTypes.find(tt => tt.id === selectedTicketType),
      [event.ticketTypes, selectedTicketType]
  );

  const totalPrice = selectedType ? (selectedType.price * quantity) : 0;
  const availableQuantity = selectedType ? getTicketQuantity(selectedType) : 0;
  const isLocallyAvailable = quantity <= availableQuantity && availableQuantity > 0;
  const isApiAvailable = availabilityQuery.data?.available ?? availabilityQuery.data?.quantity ?? true;
  const isAvailable = isLocallyAvailable && isApiAvailable;

  const availableTicketTypes = useMemo(() =>
          event.ticketTypes.filter(tt => getTicketQuantity(tt) > 0),
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
      setTimeout(() => {
        onClose();
        resetForm();
      }, 2500);
    } catch (error) {
      console.error('Purchase failed:', error);
    }
  };

  const resetForm = () => {
    setSelectedTicketType('');
    setQuantity(1);
    setPurchaseSuccess(false);
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
            <Group>
              <IconTicket size={20} />
              <Text fw={500}>Purchase Tickets - {event.name}</Text>
            </Group>
          }
          size="md"
          closeOnClickOutside={!purchaseTicketMutation.isPending}
          closeOnEscape={!purchaseTicketMutation.isPending}
          centered
      >
        <Stack gap="md">
          {purchaseSuccess && (
              <Alert
                  icon={<IconCheck size={16} />}
                  title="Purchase completed successfully!"
                  color="green"
                  variant="filled"
              >
                <Text size="sm">
                  Your tickets have been purchased. Confirmation will be sent to your email.
                </Text>
              </Alert>
          )}

          {purchaseTicketMutation.isPending && (
              <Card withBorder padding="md" bg="blue.0">
                <Center>
                  <Stack align="center" gap="sm">
                    <Loader size="md" />
                    <Text size="sm" fw={500}>Processing payment...</Text>
                    <Text size="xs" c="dimmed">Please do not close this window</Text>
                  </Stack>
                </Center>
              </Card>
          )}

          {!purchaseSuccess && !purchaseTicketMutation.isPending && (
              <Card withBorder padding="md" bg="gray.0">
                <Text fw={500} mb="xs">{event.name}</Text>
                <Text size="sm" c="dimmed" mb="xs">📍 {event.venue}</Text>
                <Text size="sm">🗓️ {new Date(event.eventDate).toLocaleString('pl-PL')}</Text>
              </Card>
          )}

          {availableTicketTypes.length === 0 && !purchaseSuccess && (
              <Alert
                  icon={<IconAlertCircle size={16} />}
                  title="No tickets available"
                  color="red"
              >
                All tickets for this event have been sold out.
              </Alert>
          )}

          {availableTicketTypes.length > 0 && !purchaseSuccess && !purchaseTicketMutation.isPending && (
              <>
                <Select
                    label="Ticket Type"
                    placeholder="Select ticket type"
                    data={availableTicketTypes.map(tt => ({
                      value: tt.id,
                      label: `${translateTicketType(tt.name)} - $${tt.priceFormatted} (${formatAvailableTickets(getTicketQuantity(tt))})`,
                    }))}
                    value={selectedTicketType}
                    onChange={(value) => {
                      setSelectedTicketType(value || '');
                      setQuantity(1);
                    }}
                    searchable
                    clearable
                />

                {selectedType && (
                    <Card withBorder padding="md">
                      <Group justify="space-between" mb="xs">
                        <Text fw={500}>{translateTicketType(selectedType.name)}</Text>
                        <Badge
                            color={availableQuantity > 0 ? 'green' : 'red'}
                            variant="light"
                            size="lg"
                        >
                          {formatAvailableTickets(availableQuantity)}
                        </Badge>
                      </Group>
                      <Text size="sm" c="dimmed">
                        Price: ${selectedType.priceFormatted} per ticket
                      </Text>
                    </Card>
                )}

                {selectedTicketType && availableQuantity > 0 && (
                    <NumberInput
                        label="Number of tickets"
                        description={`Available: ${availableQuantity} ${availableQuantity === 1 ? 'ticket' : 'tickets'}`}
                        min={1}
                        max={Math.min(availableQuantity, 10)}
                        value={quantity}
                        onChange={(value) => setQuantity(Number(value) || 1)}
                        stepHoldDelay={500}
                        stepHoldInterval={100}
                    />
                )}

                {selectedTicketType && availabilityQuery.isLoading && (
                    <Alert color="blue" variant="light">
                      <Group>
                        <Loader size="xs" />
                        <Text size="sm">Checking availability...</Text>
                      </Group>
                    </Alert>
                )}

                {selectedTicketType && quantity > 0 && !isLocallyAvailable && (
                    <Alert
                        icon={<IconAlertCircle size={16} />}
                        title="Insufficient number of tickets"
                        color="orange"
                    >
                      Selected quantity ({quantity}) exceeds available number ({availableQuantity}).
                    </Alert>
                )}

                {selectedTicketType && quantity > 0 && isLocallyAvailable && !isApiAvailable && (
                    <Alert
                        icon={<IconAlertCircle size={16} />}
                        title="Tickets may be unavailable"
                        color="orange"
                    >
                      Selected tickets may have already been purchased by other users.
                    </Alert>
                )}

                {selectedTicketType && quantity > 0 && isAvailable && (
                    <>
                      <Divider />
                      <Card withBorder padding="sm" bg="blue.0">
                        <Group justify="space-between" mb="xs">
                          <Text size="sm">Ticket type:</Text>
                          <Text size="sm" fw={500}>{translateTicketType(selectedType?.name || '')}</Text>
                        </Group>
                        <Group justify="space-between" mb="xs">
                          <Text size="sm">Quantity:</Text>
                          <Text size="sm" fw={500}>{quantity}</Text>
                        </Group>
                        <Group justify="space-between" mb="xs">
                          <Text size="sm">Price per ticket:</Text>
                          <Text size="sm" fw={500}>${selectedType?.priceFormatted}</Text>
                        </Group>
                        <Divider my="xs" />
                        <Group justify="space-between">
                          <Text fw={600} size="lg">Total amount:</Text>
                          <Text fw={600} size="lg" c="blue">
                            ${(totalPrice / 100).toFixed(2)}
                          </Text>
                        </Group>
                      </Card>
                    </>
                )}
              </>
          )}

          <Group justify="flex-end" mt="md">
            <Button
                variant="light"
                onClick={handleClose}
                disabled={purchaseTicketMutation.isPending}
            >
              {purchaseSuccess ? 'Close' : 'Cancel'}
            </Button>

            {!purchaseSuccess && availableTicketTypes.length > 0 && (
                <Button
                    onClick={handlePurchase}
                    disabled={
                        !selectedTicketType ||
                        !isAvailable ||
                        quantity < 1 ||
                        purchaseTicketMutation.isPending
                    }
                    loading={purchaseTicketMutation.isPending}
                    leftSection={purchaseSuccess ? <IconCheck size={16} /> : <IconTicket size={16} />}
                >
                  Purchase Tickets
                </Button>
            )}
          </Group>

          {!purchaseSuccess && !purchaseTicketMutation.isPending && availableTicketTypes.length > 0 && (
              <Text size="xs" c="dimmed" ta="center">
                Payment is secure and encrypted. You will receive confirmation via email after purchase.
              </Text>
          )}
        </Stack>
      </Modal>
  );
};