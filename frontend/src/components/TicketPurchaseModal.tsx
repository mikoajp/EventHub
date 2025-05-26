import React, { useState } from 'react';
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
} from '@mantine/core';
import type { Event } from '../types';
import { usePurchaseTicket, useTicketAvailability } from '../hooks/useTickets';

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
  const [paymentMethodId] = useState<string>('pm_card_visa'); // Mock payment method

  const purchaseTicketMutation = usePurchaseTicket();
  const availabilityQuery = useTicketAvailability(event.id, selectedTicketType, quantity);

  const selectedType = event.ticketTypes.find(tt => tt.id === selectedTicketType);
  const totalPrice = selectedType ? selectedType.price * quantity : 0;
  const isAvailable = availabilityQuery.data?.available ?? false;

  const handlePurchase = () => {
    if (!selectedTicketType || !selectedType) return;

    purchaseTicketMutation.mutate({
      eventId: event.id,
      ticketTypeId: selectedTicketType,
      quantity,
      paymentMethodId,
    }, {
      onSuccess: () => {
        onClose();
        setSelectedTicketType('');
        setQuantity(1);
      },
    });
  };

  return (
    <Modal
      opened={isOpen}
      onClose={onClose}
      title={`Purchase Tickets - ${event.name}`}
      size="md"
    >
      <Stack gap="md">
        <Card withBorder padding="md">
          <Text fw={500} mb="xs">{event.name}</Text>
          <Text size="sm" c="dimmed" mb="xs">{event.venue}</Text>
          <Text size="sm">{new Date(event.eventDate).toLocaleString()}</Text>
        </Card>

        <Select
          label="Ticket Type"
          placeholder="Select ticket type"
          data={event.ticketTypes.map(tt => ({
            value: tt.id,
            label: `${tt.name} - $${tt.priceFormatted} (${tt.available} available)`,
          }))}
          value={selectedTicketType}
          onChange={(value) => setSelectedTicketType(value || '')}
        />

        {selectedType && (
          <Card withBorder padding="md">
            <Group justify="space-between" mb="xs">
              <Text fw={500}>{selectedType.name}</Text>
              <Badge color={selectedType.available > 0 ? 'green' : 'red'}>
                {selectedType.available} available
              </Badge>
            </Group>
            <Text size="sm" c="dimmed">
              Price: ${selectedType.priceFormatted} per ticket
            </Text>
          </Card>
        )}

        {selectedTicketType && (
          <NumberInput
            label="Quantity"
            min={1}
            max={selectedType?.available || 1}
            value={quantity}
            onChange={(value) => setQuantity(Number(value))}
          />
        )}

        {selectedTicketType && quantity > 0 && (
          <>
            <Divider />
            <Group justify="space-between">
              <Text fw={500}>Total Amount:</Text>
              <Text fw={500} size="lg">
                ${(totalPrice / 100).toFixed(2)}
              </Text>
            </Group>

            {!isAvailable && (
              <Text c="red" size="sm">
                ⚠️ Selected quantity not available
              </Text>
            )}
          </>
        )}

        <Group justify="flex-end">
          <Button variant="light" onClick={onClose}>
            Cancel
          </Button>
          <Button
            onClick={handlePurchase}
            disabled={!selectedTicketType || !isAvailable || quantity < 1}
            loading={purchaseTicketMutation.isPending}
          >
            Purchase Tickets
          </Button>
        </Group>
      </Stack>
    </Modal>
  );
};