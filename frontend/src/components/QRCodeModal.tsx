import React from 'react';
import { Modal, Stack, Text, Center, Button, Group } from '@mantine/core';
import { IconDownload, IconShare } from '@tabler/icons-react';

interface QRCodeModalProps {
  isOpen: boolean;
  onClose: () => void;
  qrCode: string;
  eventName: string;
}

export const QRCodeModal: React.FC<QRCodeModalProps> = ({
  isOpen,
  onClose,
  qrCode,
  eventName,
}) => {
  return (
    <Modal
      opened={isOpen}
      onClose={onClose}
      title="Ticket QR Code"
      centered
      size="sm"
    >
      <Stack gap="md" align="center">
        <Text ta="center" fw={500}>
          {eventName}
        </Text>
        
        {/* In a real app, you'd generate an actual QR code here */}
        <Center
          style={{
            width: 200,
            height: 200,
            border: '2px solid #e9ecef',
            borderRadius: 8,
            backgroundColor: '#f8f9fa',
          }}
        >
          <Stack align="center" gap="xs">
            <Text size="xl" fw={700}>QR</Text>
            <Text size="xs" c="dimmed">{qrCode}</Text>
          </Stack>
        </Center>

        <Text size="sm" c="dimmed" ta="center">
          Show this QR code at the event entrance
        </Text>

        <Group grow>
          <Button
            variant="light"
            leftSection={<IconDownload size={16} />}
          >
            Download
          </Button>
          <Button
            variant="light"
            leftSection={<IconShare size={16} />}
          >
            Share
          </Button>
        </Group>
      </Stack>
    </Modal>
  );
};

// src/hooks/useAuth.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../api/client';
import type { User } from '../types';

export const useCurrentUser = () => {
  return useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () => apiClient.get<User>('/auth/me'),
    retry: false,
  });
};

export const useLogin = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ email, password }: { email: string; password: string }) =>
      apiClient.post<{ token: string; user: User }>('/auth/login', { email, password }),
    onSuccess: (data) => {
      localStorage.setItem('auth_token', data.token);
      queryClient.setQueryData(['auth', 'me'], data.user);
    },
  });
};

export const useLogout = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => apiClient.post('/auth/logout'),
    onSuccess: () => {
      localStorage.removeItem('auth_token');
      queryClient.clear();
    },
  });
};