import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { Loader, Center, Container, Stack, Text, Button, Alert, Group } from '@mantine/core';
import { IconAlertCircle, IconUserPlus } from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requireRole?: string;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requireRole 
}) => {
  const { isAuthenticated, isLoading, user } = useAuth();
  const location = useLocation();

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

  // Check if user has required role
  if (requireRole && !user?.roles?.includes(requireRole)) {
    // Show a nice message instead of just redirecting
    return (
      <Container size="sm" mt={100}>
        <Stack align="center" gap="xl">
          <Alert 
            icon={<IconAlertCircle size={24} />} 
            title="Access Denied" 
            color="red"
            variant="light"
            radius="md"
          >
            <Stack gap="md">
              <Text size="sm">
                {requireRole === 'ROLE_ORGANIZER' 
                  ? "You need organizer permissions to access this page. Only event organizers can create and manage events."
                  : "You don't have permission to access this page."}
              </Text>
              <Text size="sm" fw={500}>
                Your current roles: {user?.roles?.join(', ') || 'ROLE_USER'}
              </Text>
              {requireRole === 'ROLE_ORGANIZER' && (
                <>
                  <Text size="sm" c="dimmed">
                    To become an organizer, you need to register a new account with the "I want to organize events" option checked.
                  </Text>
                  <Group gap="sm">
                    <Button 
                      component="a" 
                      href="/register" 
                      variant="light" 
                      leftSection={<IconUserPlus size={16} />}
                    >
                      Register as Organizer
                    </Button>
                    <Button 
                      component="a" 
                      href="/" 
                      variant="subtle"
                    >
                      Go to Home
                    </Button>
                  </Group>
                </>
              )}
            </Stack>
          </Alert>
        </Stack>
      </Container>
    );
  }

  return <>{children}</>;
};