import React, { createContext, useContext, useEffect, useState, useRef } from 'react';
import { notifications } from '@mantine/notifications';

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
  const eventSourceRef = useRef<EventSource | null>(null);

  useEffect(() => {
    const mercureUrl = import.meta.env.VITE_MERCURE_URL || 'http://localhost:3001/.well-known/mercure';
    
    // Subscribe to multiple topics
    const topics = [
      'events',
      'notifications',
      'social',
      'tickets'
    ];
    
    const url = new URL(mercureUrl);
    topics.forEach(topic => {
      url.searchParams.append('topic', `https://eventhub.local/${topic}`);
    });

    const eventSource = new EventSource(url.toString());
    eventSourceRef.current = eventSource;

    eventSource.onopen = () => {
      console.log('Connected to Mercure Hub');
      setIsConnected(true);
    };

    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        const updateType = data.type || 'unknown';
        
        setLastUpdate({
          type: updateType,
          data,
          timestamp: new Date().toISOString(),
        });

        // Handle different message types
        switch (updateType) {
          case 'event_published':
            notifications.show({
              title: 'New Event Published!',
              message: data.message || 'A new event has been published',
              color: 'blue',
              autoClose: 5000,
            });
            break;

          case 'event_updated':
            notifications.show({
              title: 'Event Updated',
              message: data.message,
              color: 'yellow',
              autoClose: 5000,
            });
            break;

          case 'event_cancelled':
            notifications.show({
              title: 'Event Cancelled',
              message: data.message,
              color: 'red',
              autoClose: 10000,
            });
            break;

          case 'notification':
            notifications.show({
              title: data.title || 'Notification',
              message: data.message,
              color: data.notificationType === 'error' ? 'red' : 
                     data.notificationType === 'success' ? 'green' : 'blue',
              autoClose: 5000,
            });
            break;

          case 'social_post':
            notifications.show({
              title: 'Social Media Update',
              message: 'Event shared on social media!',
              color: 'grape',
              autoClose: 3000,
            });
            break;

          case 'ticket_purchased':
            notifications.show({
              title: 'Ticket Purchased',
              message: data.message || 'Your ticket has been purchased successfully',
              color: 'green',
              autoClose: 5000,
            });
            break;

          default:
            console.log('Received update:', data);
        }
      } catch (error) {
        console.error('Failed to parse Mercure update:', error);
      }
    };

    eventSource.onerror = (error) => {
      console.error('Mercure connection error:', error);
      setIsConnected(false);
      // EventSource will automatically reconnect
    };

    return () => {
      if (eventSourceRef.current) {
        eventSourceRef.current.close();
      }
    };
  }, []);

  const value = {
    isConnected,
    lastUpdate,
  };

  return <RealTimeContext.Provider value={value}>{children}</RealTimeContext.Provider>;
};