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

import { Footer } from './Footer';

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
    <AppShell header={{ height: 60 }} padding="0">
      <AppShell.Header>
        <Container size="xl" h="100%">
          <Group h="100%" px="md" justify="space-between">
            <Group>
              <Text
                size="xl"
                fw={700}
                variant="gradient"
                gradient={{ from: 'blue', to: 'violet' }}
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
                variant={isActive('/') || isActive('/events') ? 'filled' : 'subtle'}
                leftSection={<IconCalendar size={16} />}
                onClick={() => navigate('/')}
              >
                Events
              </Button>

              {isAuthenticated ? (
                <>
                  <Button
                    variant={isActive('/my-tickets') ? 'filled' : 'subtle'}
                    leftSection={<IconTicket size={16} />}
                    onClick={() => navigate('/my-tickets')}
                  >
                    My Tickets
                  </Button>

                  {hasOrganizerPrivileges && (
                    <>
                      <Button
                        variant={isActive('/create-event') ? 'filled' : 'subtle'}
                        leftSection={<IconPlus size={16} />}
                        onClick={() => navigate('/create-event')}
                      >
                        Create Event
                      </Button>

                      <Button
                        variant={isActive('/dashboard') ? 'filled' : 'subtle'}
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
                          <Avatar size="sm" radius="xl" color="blue" />
                          <div style={{ flex: 1 }}>
                            <Text size="sm" fw={500}>{user?.firstName || 'User'}</Text>
                            {isAdmin && (
                              <Text size="xs" c="blue" fw={500} style={{ lineHeight: 1 }}>(Admin)</Text>
                            )}
                          </div>
                        </Group>
                      </UnstyledButton>
                    </Menu.Target>

                    <Menu.Dropdown>
                      <Menu.Label>Account</Menu.Label>
                      <Menu.Item
                        leftSection={<IconUser size={14} />}
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
        </Container>
      </AppShell.Header>

      <AppShell.Main>
        <div style={{ display: 'flex', flexDirection: 'column', minHeight: 'calc(100vh - 60px)' }}>
          <div style={{ flex: 1 }}>
            <Outlet />
          </div>
          <Footer />
        </div>
      </AppShell.Main>
    </AppShell>
  );
};