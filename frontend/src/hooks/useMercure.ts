import { useEffect, useState, useCallback } from 'react';

interface MercureMessage {
  type: string;
  data: any;
  timestamp?: string;
}

interface UseMercureOptions {
  topics: string[];
  onMessage?: (message: MercureMessage) => void;
  enabled?: boolean;
}

export function useMercure({ topics, onMessage, enabled = true }: UseMercureOptions) {
  const [isConnected, setIsConnected] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastMessage, setLastMessage] = useState<MercureMessage | null>(null);

  useEffect(() => {
    if (!enabled || topics.length === 0) {
      return;
    }

    let eventSource: EventSource | null = null;

    const connect = async () => {
      try {
        // Get Mercure URL from environment
        const mercureUrl = import.meta.env.VITE_MERCURE_URL || 'http://localhost:3000/.well-known/mercure';
        
        // Build URL with topics
        const url = new URL(mercureUrl);
        topics.forEach(topic => {
          url.searchParams.append('topic', topic);
        });

        // Create EventSource for SSE
        eventSource = new EventSource(url.toString(), {
          withCredentials: false
        });

        eventSource.onopen = () => {
          console.log('[Mercure] Connected to:', topics);
          setIsConnected(true);
          setError(null);
        };

        eventSource.onmessage = (event) => {
          try {
            const message: MercureMessage = JSON.parse(event.data);
            console.log('[Mercure] Received:', message);
            
            setLastMessage(message);
            
            if (onMessage) {
              onMessage(message);
            }
          } catch (err) {
            console.error('[Mercure] Failed to parse message:', err);
          }
        };

        eventSource.onerror = (err) => {
          console.error('[Mercure] Connection error:', err);
          setIsConnected(false);
          setError('Connection lost. Reconnecting...');
          
          // EventSource automatically reconnects
        };
      } catch (err) {
        console.error('[Mercure] Setup error:', err);
        setError(err instanceof Error ? err.message : 'Unknown error');
      }
    };

    connect();

    return () => {
      if (eventSource) {
        console.log('[Mercure] Disconnecting from:', topics);
        eventSource.close();
      }
    };
  }, [topics.join(','), enabled, onMessage]);

  return {
    isConnected,
    error,
    lastMessage
  };
}

// Hook for notifications
export function useNotifications(userId?: string) {
  const [notifications, setNotifications] = useState<any[]>([]);

  const topics = userId 
    ? [`notifications/user/${userId}`, 'notifications']
    : ['notifications'];

  const handleMessage = useCallback((message: MercureMessage) => {
    if (message.type === 'notification') {
      setNotifications(prev => [message.data, ...prev]);
      
      // Show browser notification if permitted
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(message.data.title || 'Notification', {
          body: message.data.message,
          icon: '/vite.svg'
        });
      }
    }
  }, []);

  const { isConnected, error } = useMercure({
    topics,
    onMessage: handleMessage,
    enabled: true
  });

  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  return {
    notifications,
    clearNotifications,
    isConnected,
    error
  };
}

// Hook for event updates
export function useEventUpdates() {
  const [events, setEvents] = useState<any[]>([]);

  const handleMessage = useCallback((message: MercureMessage) => {
    if (message.type === 'event.published') {
      setEvents(prev => [message.data, ...prev]);
    }
  }, []);

  const { isConnected, error, lastMessage } = useMercure({
    topics: ['events'],
    onMessage: handleMessage,
    enabled: true
  });

  return {
    events,
    lastMessage,
    isConnected,
    error
  };
}
