import React from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import {
  AppShell,
  Group,
  Text,
  Button,
  UnstyledButton,
  Container,
} from '@mantine/core';
import {
  IconCalendar,
  IconTicket,
  IconLogin,
} from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';

export const PublicLayout: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { isAuthenticated, user } = useAuth();

  const isActive = (path: string) => location.pathname === path;

  return (
    <AppShell
      header={{ height: 60 }}
      padding="md"
    >
      <AppShell.Header>
        <Group h="100%" px="md" justify="space-between">
          <Group>
            <UnstyledButton onClick={() => navigate('/')}>
              <Group gap="xs">
                <Text size="xl" fw={700} c="blue">
                  EventHub v2.0 - Twoje wydarzenia! 🔥
                </Text>
              </Group>
            </UnstyledButton>
          </Group>

          <Group gap="md">
            <Button
              variant={isActive('/') ? 'light' : 'subtle'}
              leftSection={<IconCalendar size={16} />}
              onClick={() => navigate('/')}
            >
              Wydarzenia
            </Button>

            {isAuthenticated ? (
              <>
                <Button
                  variant={isActive('/my-tickets') ? 'light' : 'subtle'}
                  leftSection={<IconTicket size={16} />}
                  onClick={() => navigate('/my-tickets')}
                >
                  Moje bilety
                </Button>
                
                {user?.roles?.includes('ROLE_ORGANIZER') && (
                  <Button
                    variant="filled"
                    onClick={() => navigate('/dashboard')}
                  >
                    Dashboard
                  </Button>
                )}

                <Text size="sm" c="dimmed">
                  {user?.email}
                </Text>
                
                <Button
                  variant="light"
                  color="red"
                  onClick={() => {
                    // Will be handled by AuthContext
                    navigate('/logout');
                  }}
                >
                  Wyloguj
                </Button>
              </>
            ) : (
              <Button
                variant="filled"
                leftSection={<IconLogin size={16} />}
                onClick={() => navigate('/login')}
              >
                Zaloguj się
              </Button>
            )}
          </Group>
        </Group>
      </AppShell.Header>

      <AppShell.Main>
        <Container size="xl">
          <Outlet />
        </Container>
      </AppShell.Main>
    </AppShell>
  );
};
