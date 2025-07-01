import React from 'react';
import {
  Card,
  Stack,
  Group,
  TextInput,
  MultiSelect,
  Select,
  Button,
  Accordion,
  Switch,
  NumberInput,
  Text,
  Badge,
} from '@mantine/core';
import { DatePickerInput } from '@mantine/dates';
import {
  IconSearch,
  IconFilter,
  IconRefresh,
  IconCalendar,
  IconMapPin,
  IconCurrency,
  IconSortAscending,
} from '@tabler/icons-react';
import type { EventFilters, FilterOptions } from '../types';

interface AdvancedEventFiltersProps {
  filters: EventFilters;
  filterOptions: FilterOptions | null;
  onFiltersChange: (filters: Partial<EventFilters>) => void;
  onReset: () => void;
  loading?: boolean;
}

export const AdvancedEventFilters: React.FC<AdvancedEventFiltersProps> = ({
                                                                            filters,
                                                                            filterOptions,
                                                                            onFiltersChange,
                                                                            onReset,
                                                                            loading = false,
                                                                          }) => {
  const sortOptions = [
    { value: 'date', label: 'Event Date' },
    { value: 'name', label: 'Name' },
    { value: 'price', label: 'Price' },
    { value: 'popularity', label: 'Popularity' },
    { value: 'created_at', label: 'Created Date' },
    { value: 'venue', label: 'Venue' },
  ];

  const getActiveFiltersCount = () => {
    let count = 0;
    if (filters.search) count++;
    if (filters.status && filters.status.length > 1) count++;
    if (filters.venue && filters.venue.length > 0) count++;
    if (filters.date_from || filters.date_to) count++;
    if (filters.price_min !== undefined || filters.price_max !== undefined) count++;
    if (filters.has_available_tickets) count++;
    return count;
  };

  const activeFiltersCount = getActiveFiltersCount();

  return (
      <Card withBorder padding="md" radius="md">
        <Stack gap="md">
          {/* Search and Quick Actions */}
          <Group gap="md">
            <TextInput
                placeholder="Search events, venues, descriptions..."
                value={filters.search || ''}
                onChange={(e) => onFiltersChange({ search: e.target.value })}
                leftSection={<IconSearch size={16} />}
                style={{ flex: 1 }}
                disabled={loading}
            />

            <Group gap="xs">
              <Button
                  variant="light"
                  leftSection={<IconRefresh size={16} />}
                  onClick={onReset}
                  disabled={loading}
              >
                Reset
              </Button>

              {activeFiltersCount > 0 && (
                  <Badge variant="filled" color="blue">
                    {activeFiltersCount} filter{activeFiltersCount !== 1 ? 's' : ''}
                  </Badge>
              )}
            </Group>
          </Group>

          {/* Advanced Filters */}
          <Accordion variant="contained" radius="md">
            <Accordion.Item value="filters">
              <Accordion.Control icon={<IconFilter size={16} />}>
                Advanced Filters
              </Accordion.Control>
              <Accordion.Panel>
                <Stack gap="md">
                  {/* Status Filter */}
                  <MultiSelect
                      label="Event Status"
                      placeholder="Select statuses"
                      data={filterOptions?.statuses || []}
                      value={filters.status || []}
                      onChange={(value) => onFiltersChange({ status: value })}
                      disabled={loading}
                  />

                  {/* Venue Filter */}
                  <MultiSelect
                      label="Venues"
                      placeholder="Select venues"
                      data={filterOptions?.venues.map(venue => ({ value: venue, label: venue })) || []}
                      value={filters.venue || []}
                      onChange={(value) => onFiltersChange({ venue: value })}
                      leftSection={<IconMapPin size={16} />}
                      searchable
                      disabled={loading}
                  />

                  {/* Date Range */}
                  <Group grow>
                    <DatePickerInput
                        label="From Date"
                        placeholder="Select start date"
                        value={filters.date_from ? new Date(filters.date_from) : null}
                        onChange={(date) =>
                            onFiltersChange({
                              date_from: date ? date.toString().split('T')[0] : undefined
                            })
                        }
                        leftSection={<IconCalendar size={16} />}
                        disabled={loading}
                        clearable
                    />
                    <DatePickerInput
                        label="To Date"
                        placeholder="Select end date"
                        value={filters.date_to ? new Date(filters.date_to) : null}
                        onChange={(date) =>
                            onFiltersChange({
                              date_to: date ? date.toString().split('T')[0] : undefined
                            })
                        }
                        leftSection={<IconCalendar size={16} />}
                        disabled={loading}
                        clearable
                    />
                  </Group>

                  {/* Price Range */}
                  {filterOptions?.priceRange && (
                      <Stack gap="xs">
                        <Text size="sm" fw={500}>
                          Price Range (${filterOptions.priceRange.min} - ${filterOptions.priceRange.max})
                        </Text>
                        <Group grow>
                          <NumberInput
                              label="Min Price"
                              placeholder="Min"
                              value={filters.price_min}
                              onChange={(value) => onFiltersChange({
                                price_min: typeof value === 'number' ? value : undefined
                              })}
                              leftSection={<IconCurrency size={16} />}
                              min={filterOptions.priceRange.min}
                              max={filterOptions.priceRange.max}
                              disabled={loading}
                          />
                          <NumberInput
                              label="Max Price"
                              placeholder="Max"
                              value={filters.price_max}
                              onChange={(value) => onFiltersChange({
                                price_max: typeof value === 'number' ? value : undefined
                              })}
                              leftSection={<IconCurrency size={16} />}
                              min={filterOptions.priceRange.min}
                              max={filterOptions.priceRange.max}
                              disabled={loading}
                          />
                        </Group>
                      </Stack>
                  )}

                  {/* Available Tickets Only */}
                  <Switch
                      label="Show only events with available tickets"
                      checked={filters.has_available_tickets || false}
                      onChange={(e) => onFiltersChange({ has_available_tickets: e.currentTarget.checked })}
                      disabled={loading}
                  />
                </Stack>
              </Accordion.Panel>
            </Accordion.Item>

            <Accordion.Item value="sorting">
              <Accordion.Control icon={<IconSortAscending size={16} />}>
                Sorting Options
              </Accordion.Control>
              <Accordion.Panel>
                <Group grow>
                  <Select
                      label="Sort By"
                      data={sortOptions}
                      value={filters.sort_by || 'date'}
                      onChange={(value) => onFiltersChange({ sort_by: value as never })}
                      disabled={loading}
                  />
                  <Select
                      label="Direction"
                      data={[
                        { value: 'asc', label: 'Ascending' },
                        { value: 'desc', label: 'Descending' },
                      ]}
                      value={filters.sort_direction || 'asc'}
                      onChange={(value) => onFiltersChange({ sort_direction: value as 'asc' | 'desc' })}
                      disabled={loading}
                  />
                </Group>
              </Accordion.Panel>
            </Accordion.Item>
          </Accordion>
        </Stack>
      </Card>
  );
};