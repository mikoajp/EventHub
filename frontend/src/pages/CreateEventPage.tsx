import React from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Container,
  Title,
  Text,
  Card,
  Stack,
} from '@mantine/core';
import { CreateEventForm } from '../components/CreateEventForm';

export const CreateEventPage: React.FC = () => {
  const navigate = useNavigate();

  const handleSuccess = () => {
    navigate('/dashboard');
  };

  return (
    <Container size="md">
      <Stack gap="xl">
        <div>
          <Title order={1} mb="md">Create New Event</Title>
          <Text c="dimmed">
            Fill out the form below to create and publish your event.
          </Text>
        </div>

        <Card withBorder padding="xl">
          <CreateEventForm onSuccess={handleSuccess} />
        </Card>
      </Stack>
    </Container>
  );
};