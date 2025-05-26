import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { MantineProvider, createTheme } from '@mantine/core';
import { Notifications } from '@mantine/notifications';
import { DatesProvider } from '@mantine/dates';
import { ModalsProvider } from '@mantine/modals';

import '@mantine/core/styles.css';
import '@mantine/notifications/styles.css';
import '@mantine/dates/styles.css';

import { Layout } from './components/Layout';
import { ProtectedRoute } from './components/ProtectedRoute';
import { HomePage } from './pages/HomePage';
import { EventsPage } from './pages/EventsPage';
import { EventDetailsPage } from './pages/EventDetailsPage';
import { CreateEventPage } from './pages/CreateEventPage';
import { MyTicketsPage } from './pages/MyTicketsPage';
import { DashboardPage } from './pages/DashboardPage';
import { LoginPage } from './pages/LoginPage';
import { AuthProvider } from './contexts/AuthContext';
import { RealTimeProvider } from './contexts/RealTimeContext';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      refetchOnWindowFocus: false,
      retry: (failureCount, error: any) => {
        if (error?.response?.status === 401) {
          return false;
        }
        return failureCount < 3;
      },
    },
  },
});

const theme = createTheme({
  primaryColor: 'blue',
  fontFamily: 'Inter, sans-serif',
});

export const App: React.FC = () => {
  return (
    <QueryClientProvider client={queryClient}>
      <MantineProvider theme={theme}>
        <DatesProvider settings={{ firstDayOfWeek: 1 }}>
          <ModalsProvider>
            <Notifications position="top-right" />
            <AuthProvider>
              <RealTimeProvider>
                <Router>
                  <Routes>
                    {/* Public routes */}
                    <Route path="/login" element={<LoginPage />} />
                    
                    {/* Protected routes */}
                    <Route 
                      path="/" 
                      element={
                        <ProtectedRoute>
                          <Layout />
                        </ProtectedRoute>
                      }
                    >
                      <Route index element={<HomePage />} />
                      <Route path="events" element={<EventsPage />} />
                      <Route path="events/:id" element={<EventDetailsPage />} />
                      <Route path="my-tickets" element={<MyTicketsPage />} />
                      
                      {/* Organizer-only routes */}
                      <Route 
                        path="create-event" 
                        element={
                          <ProtectedRoute requireRole="ROLE_ORGANIZER">
                            <CreateEventPage />
                          </ProtectedRoute>
                        } 
                      />
                      <Route 
                        path="dashboard" 
                        element={
                          <ProtectedRoute requireRole="ROLE_ORGANIZER">
                            <DashboardPage />
                          </ProtectedRoute>
                        } 
                      />
                    </Route>
                    
                    {/* Catch all route */}
                    <Route path="*" element={<Navigate to="/" replace />} />
                  </Routes>
                </Router>
              </RealTimeProvider>
            </AuthProvider>
          </ModalsProvider>
        </DatesProvider>
      </MantineProvider>
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  );
};