import React from 'react';
import { Container, Title, Text, Button, Group, Overlay, Box, rem } from '@mantine/core';
import { IconRocket, IconCalendarEvent } from '@tabler/icons-react';

export const Hero: React.FC = () => {
  return (
    <Box
      style={{
        position: 'relative',
        height: rem(500),
        backgroundColor: '#1a1b1e',
        backgroundImage: 'linear-gradient(250deg, rgba(130, 201, 30, 0) 0%, #062343 70%), url("https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80")',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        borderRadius: '0 0 var(--mantine-radius-lg) var(--mantine-radius-lg)',
        overflow: 'hidden',
        display: 'flex',
        alignItems: 'center',
        marginBottom: 'var(--mantine-spacing-xl)',
      }}
    >
      <Overlay
        gradient="linear-gradient(180deg, rgba(0, 0, 0, 0.25) 0%, rgba(0, 0, 0, .65) 40%)"
        opacity={1}
        zIndex={0}
      />
      
      <Container size="xl" style={{ position: 'relative', zIndex: 1, width: '100%' }}>
        <div style={{ maxWidth: rem(600) }}>
          <Title
            style={{
              color: 'white',
              fontSize: rem(60),
              fontWeight: 900,
              lineHeight: 1.1,
            }}
          >
            Discover the <br />
            <Text component="span" inherit variant="gradient" gradient={{ from: 'pink', to: 'yellow' }}>
              Best Events
            </Text>{' '}
            Near You
          </Title>

          <Text size="xl" mt="xl" c="dimmed" style={{ color: '#e9ecef', maxWidth: '500px' }}>
            Join the community, find your next passion, and create unforgettable memories with EventHub.
          </Text>

          <Group mt={40}>
            <Button
              size="xl"
              radius="xl"
              variant="gradient"
              gradient={{ from: 'pink', to: 'yellow' }}
              leftSection={<IconRocket size={20} />}
            >
              Get Started
            </Button>
            
            <Button
              size="xl"
              radius="xl"
              variant="white"
              color="dark"
              leftSection={<IconCalendarEvent size={20} />}
              onClick={() => {
                const element = document.getElementById('events-section');
                element?.scrollIntoView({ behavior: 'smooth' });
              }}
            >
              Browse Events
            </Button>
          </Group>
        </div>
      </Container>
    </Box>
  );
};
