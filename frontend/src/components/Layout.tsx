import React from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import {
  AppShell,
  Group,
  Text,
  Button,
  Menu,
  Avatar,
  UnstyledButton,
  Indicator,
  Container,
} from '@mantine/core';
import {
  IconCalendar,
  IconTicket,
  IconPlus,
  IconDashboard,
  IconLogout,
  IconUser,
  IconWifi,
  IconWifiOff,
  IconLogin,
} from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';
import { useRealTime } from '../contexts/RealTimeContext';

export const Layout: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout, isAuthenticated } = useAuth();
  const { isConnected } = useRealTime();

  const isActive = (path: string) => location.pathname === path;

  const handleLogout = () => {
    logout();
    navigate('/', { replace: true });
  };

  const isAdmin = user?.roles?.includes('ROLE_ADMIN') || false;
  const isOrganizer = user?.roles?.includes('ROLE_ORGANIZER') || false;
  const hasOrganizerPrivileges = isAdmin || isOrganizer;

  return (
    <AppShell header={{ height: 60 }} padding="md">
      <AppShell.Header>
        <Group h="100%" px="md" justify="space-between">
          <Group>
            <Text
              size="xl"
              fw={700}
              variant="gradient"
              gradient={{ from: 'blue', to: 'cyan' }}
              style={{ cursor: 'pointer' }}
              onClick={() => navigate('/')}
            >
              EventHub
            </Text>

            <Indicator
              color={isConnected ? 'green' : 'red'}
              size={8}
              position="middle-end"
              title={isConnected ? 'Connected to real-time updates' : 'Disconnected from real-time updates'}
            >
              {isConnected ? <IconWifi size={16} /> : <IconWifiOff size={16} />}
            </Indicator>
          </Group>

          <Group>
            <Button
              variant={isActive('/') || isActive('/events') ? 'filled' : 'light'}
              leftSection={<IconCalendar size={16} />}
              onClick={() => navigate('/')}
            >
              Events
            </Button>

            {isAuthenticated ? (
              <>
                <Button
                  variant={isActive('/my-tickets') ? 'filled' : 'light'}
                  leftSection={<IconTicket size={16} />}
                  onClick={() => navigate('/my-tickets')}
                >
                  My Tickets
                </Button>

                {hasOrganizerPrivileges && (
                  <>
                    <Button
                      variant={isActive('/create-event') ? 'filled' : 'light'}
                      leftSection={<IconPlus size={16} />}
                      onClick={() => navigate('/create-event')}
                    >
                      Create Event
                    </Button>

                    <Button
                      variant={isActive('/dashboard') ? 'filled' : 'light'}
                      leftSection={<IconDashboard size={16} />}
                      onClick={() => navigate('/dashboard')}
                    >
                      Dashboard
                    </Button>
                  </>
                )}

                <Menu shadow="md" width={200}>
                  <Menu.Target>
                    <UnstyledButton>
                      <Group gap="xs">
                        <Avatar size="sm">🎫</Avatar>
                        <Text size="sm">{user?.firstName || 'User'}</Text>
                        {isAdmin && (
                          <Text size="xs" c="blue" fw={500}>(Admin)</Text>
                        )}
                        {isOrganizer && !isAdmin && (
                          <Text size="xs" c="dimmed">(Organizer)</Text>
                        )}
                      </Group>
                    </UnstyledButton>
                  </Menu.Target>

                  <Menu.Dropdown>
                    <Menu.Label>Account</Menu.Label>
                    <Menu.Item
                      leftSection={<span style={{ fontSize: '14px' }}>🎫</span>}
                      onClick={() => navigate('/profile')}
                    >
                      Profile
                    </Menu.Item>
                    <Menu.Divider />
                    <Menu.Item
                      leftSection={<IconLogout size={14} />}
                      onClick={handleLogout}
                    >
                      Logout
                    </Menu.Item>
                  </Menu.Dropdown>
                </Menu>
              </>
            ) : (
              <Button
                variant="filled"
                leftSection={<IconLogin size={16} />}
                onClick={() => navigate('/login')}
              >
                Login
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