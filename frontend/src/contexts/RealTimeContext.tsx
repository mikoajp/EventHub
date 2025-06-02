import React, { createContext, useContext, useEffect, useState, useRef } from 'react';
import { notifications } from '@mantine/notifications';
import { Client } from '@stomp/stompjs';

interface RealTimeUpdate {
  type: string;
  data: any;
  timestamp: string;
}

interface RealTimeContextType {
  isConnected: boolean;
  lastUpdate: RealTimeUpdate | null;
}

const RealTimeContext = createContext<RealTimeContextType | undefined>(undefined);

export const useRealTime = () => {
  const context = useContext(RealTimeContext);
  if (!context) {
    throw new Error('useRealTime must be used within a RealTimeProvider');
  }
  return context;
};

export const RealTimeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isConnected, setIsConnected] = useState(false);
  const [lastUpdate, setLastUpdate] = useState<RealTimeUpdate | null>(null);
  const clientRef = useRef<Client | null>(null);

  useEffect(() => {
    const client = new Client({
      brokerURL: import.meta.env.VITE_STOMP_URL || 'ws://localhost:15674/ws',
      connectHeaders: {
        login: import.meta.env.VITE_RABBITMQ_USER || 'eventhub',
        passcode: import.meta.env.VITE_RABBITMQ_PASS || 'secret',
      },
      debug: (str) => {
        console.log('STOMP Debug:', str);
      },
      reconnectDelay: 5000,
      heartbeatIncoming: 4000,
      heartbeatOutgoing: 4000,
    });

    clientRef.current = client;

    client.onConnect = (frame) => {
      console.log('Connected to STOMP:', frame);
      setIsConnected(true);

      client.subscribe('/exchange/events/draft_created', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'event_draft_created',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: 'Draft Event Created',
            message: data.message,
            color: 'blue',
            autoClose: 3000,
          });
        } catch (error) {
          console.error('Failed to parse draft created update:', error);
        }
      });

      client.subscribe('/exchange/events/published', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'event_published',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: 'New Event Published!',
            message: data.message || 'A new event has been published',
            color: 'blue',
            autoClose: 5000,
          });
        } catch (error) {
          console.error('Failed to parse event published update:', error);
        }
      });

      client.subscribe('/exchange/events/updated', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'event_updated',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: 'Event Updated',
            message: data.message,
            color: 'yellow',
            autoClose: 5000,
          });
        } catch (error) {
          console.error('Failed to parse event updated update:', error);
        }
      });

      client.subscribe('/exchange/events/cancelled', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'event_cancelled',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: 'Event Cancelled',
            message: data.message,
            color: 'red',
            autoClose: 10000,
          });
        } catch (error) {
          console.error('Failed to parse event cancelled update:', error);
        }
      });

      client.subscribe('/exchange/notifications/global', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'global_notification',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: data.title || 'Notification',
            message: data.message,
            color: data.type === 'error' ? 'red' : data.type === 'success' ? 'green' : 'blue',
            autoClose: 5000,
          });
        } catch (error) {
          console.error('Failed to parse global notification:', error);
        }
      });

      // Subscribe to user-specific notifications (you'd need to get current user ID)
      // client.subscribe('/exchange/notifications/user.USER_ID', (message) => {
      //   // Handle user-specific notifications
      // });

      client.subscribe('/exchange/social/post.*', (message) => {
        try {
          const data = JSON.parse(message.body);
          setLastUpdate({
            type: 'social_post',
            data,
            timestamp: new Date().toISOString(),
          });

          notifications.show({
            title: 'Social Media Update',
            message: 'Event shared on social media!',
            color: 'grape',
            autoClose: 3000,
          });
        } catch (error) {
          console.error('Failed to parse social update:', error);
        }
      });
    };

    client.onStompError = (frame) => {
      console.error('STOMP error:', frame.headers['message']);
      console.error('Additional details:', frame.body);
      setIsConnected(false);
    };

    client.onWebSocketError = (error) => {
      console.error('WebSocket error:', error);
      setIsConnected(false);
    };

    client.onDisconnect = () => {
      console.log('Disconnected from STOMP');
      setIsConnected(false);
    };

    client.activate();

    return () => {
      if (clientRef.current) {
        clientRef.current.deactivate();
      }
    };
  }, []);

  const value = {
    isConnected,
    lastUpdate,
  };

  return <RealTimeContext.Provider value={value}>{children}</RealTimeContext.Provider>;
};