import React, { createContext, useContext, useEffect, useState } from 'react';
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

  useEffect(() => {
    // Connect to Mercure hub
    const eventSource = new EventSource('http://localhost:3000/.well-known/mercure?topic=events/published');
    
    eventSource.onopen = () => {
      setIsConnected(true);
      console.log('Connected to real-time updates');
    };

    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        setLastUpdate({
          type: 'event_published',
          data,
          timestamp: new Date().toISOString(),
        });

        // Show notification for new events
        notifications.show({
          title: 'New Event Published!',
          message: data.message,
          color: 'blue',
          autoClose: 5000,
        });
      } catch (error) {
        console.error('Failed to parse real-time update:', error);
      }
    };

    eventSource.onerror = () => {
      setIsConnected(false);
      console.log('Disconnected from real-time updates');
    };

    return () => {
      eventSource.close();
    };
  }, []);

  const value = {
    isConnected,
    lastUpdate,
  };

  return <RealTimeContext.Provider value={value}>{children}</RealTimeContext.Provider>;
};