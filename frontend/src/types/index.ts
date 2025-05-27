export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  phone?: string;
  fullName: string;
  roles: string[];
  role?: string;
  authorities?: Array<{ authority: string }>;
  createdAt: string;
}

export interface Event {
  id: string;
  name: string;
  description: string;
  eventDate: string;
  venue: string;
  maxTickets: number;
  status: 'draft' | 'published' | 'cancelled' | 'completed';
  organizer: User;
  createdAt: string;
  updatedAt?: string;
  ticketTypes: TicketType[];
  ticketsSold: number;
  availableTickets: number;
}

export interface TicketType {
  id: string;
  name: string;
  price: number;
  quantity: number;
  available: number;
  priceFormatted: string;
}

export interface Ticket {
  id: string;
  event: Event;
  user: User;
  ticketType: TicketType;
  price: number;
  status: 'reserved' | 'purchased' | 'cancelled' | 'refunded';
  purchasedAt?: string;
  qrCode?: string;
  priceFormatted: string;
  createdAt: string;
}

export interface EventStatistics {
  eventId: string;
  totalTickets: number;
  soldTickets: number;
  availableTickets: number;
  totalRevenue: number;
  salesByType: Array<{
    ticketType: string;
    count: number;
    revenue: number;
    avgPrice: number;
  }>;
  salesTimeline: Array<{
    saleDate: string;
    dailySales: number;
    dailyRevenue: number;
  }>;
  conversionRate: number;
}

export interface CreateEventData {
  name: string;
  description: string;
  eventDate: string;
  venue: string;
  maxTickets: number;
  ticketTypes: Array<{
    name: string;
    price: number;
    quantity: number;
  }>;
}

export interface PurchaseTicketData {
  eventId: string;
  ticketTypeId: string;
  quantity: number;
  paymentMethodId: string;
}

export interface HydraResponse<T> {
  '@context': string;
  '@id': string;
  '@type': string;
  'hydra:member': T[];
  'hydra:totalItems': number;
}

export type EventsResponse = HydraResponse<Event>;
export type TicketsResponse = HydraResponse<Ticket>;
export type UsersResponse = HydraResponse<User>;