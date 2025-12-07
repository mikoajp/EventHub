import React from 'react';
import { Container, Group, ActionIcon, Text, rem, Stack, Anchor } from '@mantine/core';
import { IconBrandTwitter, IconBrandYoutube, IconBrandInstagram, IconBrandGithub } from '@tabler/icons-react';

export const Footer: React.FC = () => {
  return (
    <div style={{ marginTop: rem(60), borderTop: '1px solid var(--mantine-color-gray-2)', backgroundColor: 'var(--mantine-color-gray-0)' }}>
      <Container size="xl" py="xl">
        <Group justify="space-between" align="start">
          <Stack gap="xs" style={{ maxWidth: 300 }}>
             <Text
              size="lg"
              fw={700}
              variant="gradient"
              gradient={{ from: 'blue', to: 'violet' }}
            >
              EventHub
            </Text>
            <Text size="sm" c="dimmed">
              Discover and book the best events near you. Join our community and create unforgettable memories.
            </Text>
          </Stack>

          <Group gap={rem(50)} align="start">
            <Stack gap="xs">
              <Text size="lg" fw={700}>About</Text>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Features</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Pricing</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Support</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Forums</Anchor>
            </Stack>
            <Stack gap="xs">
              <Text size="lg" fw={700}>Project</Text>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Contribute</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Media assets</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Changelog</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Releases</Anchor>
            </Stack>
            <Stack gap="xs">
              <Text size="lg" fw={700}>Community</Text>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Join Discord</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Follow on Twitter</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>Email newsletter</Anchor>
              <Anchor href="#" c="dimmed" size="sm" style={{ lineHeight: 1 }}>GitHub discussions</Anchor>
            </Stack>
          </Group>
        </Group>

        <Group justify="space-between" align="center" mt="xl" pt="xl" style={{ borderTop: '1px solid var(--mantine-color-gray-3)' }}>
          <Text c="dimmed" size="sm">
            © 2025 EventHub. All rights reserved.
          </Text>

          <Group gap="xs" justify="flex-end" wrap="nowrap">
            <ActionIcon size="lg" variant="default" radius="xl">
              <IconBrandTwitter size={18} stroke={1.5} />
            </ActionIcon>
            <ActionIcon size="lg" variant="default" radius="xl">
              <IconBrandYoutube size={18} stroke={1.5} />
            </ActionIcon>
            <ActionIcon size="lg" variant="default" radius="xl">
              <IconBrandInstagram size={18} stroke={1.5} />
            </ActionIcon>
             <ActionIcon size="lg" variant="default" radius="xl">
              <IconBrandGithub size={18} stroke={1.5} />
            </ActionIcon>
          </Group>
        </Group>
      </Container>
    </div>
  );
};
