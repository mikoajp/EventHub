import React from 'react';
import { Outlet, useNavigate, useLocation, Navigate } from 'react-router-dom';
import {
  AppShell,
  Group,
  Text,
  Button,
  Menu,
  Avatar,
  UnstyledButton,
  Indicator,
  Loader,
  Center,
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
} from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';
import { useRealTime } from '../contexts/RealTimeContext';

export const Layout: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout, isAuthenticated, isLoading } = useAuth();
  const { isConnected } = useRealTime();

  if (isLoading) {
    return (
      <Center h="100vh">
        <Loader size="lg" />
      </Center>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  const isActive = (path: string) => location.pathname === path;

  const handleLogout = () => {
    logout();
    navigate('/login', { replace: true });
  };

  const isOrganizer = user?.roles?.includes('ROLE_ORGANIZER') || 
  user?.roles?.includes('ROLE_ADMIN') ||
  user?.authorities?.some((a: { authority: string }) => 
    a.authority === 'ROLE_ORGANIZER' || a.authority === 'ROLE_ADMIN');

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
              variant={isActive('/') ? 'filled' : 'light'}
              leftSection={<IconCalendar size={16} />}
              onClick={() => navigate('/')}
            >
              Events
            </Button>

            <Button
              variant={isActive('/my-tickets') ? 'filled' : 'light'}
              leftSection={<IconTicket size={16} />}
              onClick={() => navigate('/my-tickets')}
            >
              My Tickets
            </Button>

            {isOrganizer && (
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
                    <Avatar size="sm" />
                    <Text size="sm">{user?.fullName || user?.firstName || 'User'}</Text>
                    {isOrganizer && (
                      <Text size="xs" c="dimmed">(Organizer)</Text>
                    )}
                  </Group>
                </UnstyledButton>
              </Menu.Target>

              <Menu.Dropdown>
                <Menu.Label>Account</Menu.Label>
                <Menu.Item leftSection={<IconUser size={14} />}>
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
          </Group>
        </Group>
      </AppShell.Header>

      <AppShell.Main>
        <Outlet />
      </AppShell.Main>
    </AppShell>
  );
};