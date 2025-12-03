import React, { useState } from 'react';
import {
  Container,
  Paper,
  Title,
  Text,
  Stack,
  TextInput,
  PasswordInput,
  Button,
  Tabs,
  Group,
  Avatar,
  Badge,
  Divider,
  Grid,
  ThemeIcon,
  Alert,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { useAuth } from '../contexts/AuthContext';
import {
  IconLock,
  IconCheck,
  IconAlertCircle,
  IconUser,
} from '@tabler/icons-react';
import { notifications } from '@mantine/notifications';

interface UpdateProfileFormValues {
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
}

interface ChangePasswordFormValues {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}

export const ProfilePage: React.FC = () => {
  const { user } = useAuth();
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  const profileForm = useForm<UpdateProfileFormValues>({
    initialValues: {
      firstName: user?.firstName || '',
      lastName: user?.lastName || '',
      email: user?.email || '',
      phone: user?.phone || '',
    },
    validate: {
      firstName: (val) => (val.trim().length < 2 ? 'First name must be at least 2 characters' : null),
      lastName: (val) => (val.trim().length < 2 ? 'Last name must be at least 2 characters' : null),
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
    },
  });

  const passwordForm = useForm<ChangePasswordFormValues>({
    initialValues: {
      currentPassword: '',
      newPassword: '',
      confirmPassword: '',
    },
    validate: {
      currentPassword: (val) => (val.length < 1 ? 'Current password is required' : null),
      newPassword: (val) => (val.length < 6 ? 'New password must be at least 6 characters' : null),
      confirmPassword: (val, values) =>
        val !== values.newPassword ? 'Passwords do not match' : null,
    },
  });

  const handleUpdateProfile = async (_values: UpdateProfileFormValues) => {
    setIsUpdatingProfile(true);
    try {
      // TODO: Implement API call to update profile
      notifications.show({
        title: 'Success',
        message: 'Profile updated successfully',
        color: 'green',
        icon: <IconCheck size={16} />,
      });
    } catch (error) {
      notifications.show({
        title: 'Error',
        message: 'Failed to update profile',
        color: 'red',
        icon: <IconAlertCircle size={16} />,
      });
    } finally {
      setIsUpdatingProfile(false);
    }
  };

  const handleChangePassword = async (_values: ChangePasswordFormValues) => {
    setIsChangingPassword(true);
    try {
      // TODO: Implement API call to change password
      notifications.show({
        title: 'Success',
        message: 'Password changed successfully',
        color: 'green',
        icon: <IconCheck size={16} />,
      });
      passwordForm.reset();
    } catch (error) {
      notifications.show({
        title: 'Error',
        message: 'Failed to change password',
        color: 'red',
        icon: <IconAlertCircle size={16} />,
      });
    } finally {
      setIsChangingPassword(false);
    }
  };

  if (!user) {
    return null;
  }

  const roles = user.roles || [];
  const hasOrganizerRole = roles.includes('ROLE_ORGANIZER');
  const hasAdminRole = roles.includes('ROLE_ADMIN');

  return (
    <Container size="lg" py="xl">
      <Stack gap="xl">
        {/* Profile Header */}
        <Paper withBorder p="lg" radius="lg">
          <Group justify="space-between" align="flex-start">
            <Group>
              <Avatar
                size={80}
                radius={80}
                color="blue"
              />
              <div>
                <Title order={2}>
                  {user.firstName} {user.lastName}
                </Title>
                <Text c="dimmed" size="sm">
                  {user.email}
                </Text>
                <Group gap="xs" mt="xs">
                  {hasAdminRole && (
                    <Badge color="red" variant="light">
                      Admin
                    </Badge>
                  )}
                  {hasOrganizerRole && (
                    <Badge color="blue" variant="light">
                      Organizer
                    </Badge>
                  )}
                  {!hasOrganizerRole && !hasAdminRole && (
                    <Badge color="gray" variant="light">
                      User
                    </Badge>
                  )}
                </Group>
              </div>
            </Group>
          </Group>
        </Paper>

        {/* Account Stats */}
        <Grid>
          <Grid.Col span={{ base: 12, sm: 4 }}>
            <Paper withBorder p="lg" radius="lg" ta="center">
              <Text size="3rem" mb="sm">🎫</Text>
              <Text c="dimmed" size="sm">
                Account Status
              </Text>
              <Title order={3}>Active</Title>
            </Paper>
          </Grid.Col>
          <Grid.Col span={{ base: 12, sm: 4 }}>
            <Paper withBorder p="lg" radius="lg" ta="center">
              <ThemeIcon size="lg" radius="lg" variant="light" mb="sm" mx="auto">
                <IconCheck size={24} />
              </ThemeIcon>
              <Text c="dimmed" size="sm">
                Email Verified
              </Text>
              <Title order={3}>Yes</Title>
            </Paper>
          </Grid.Col>
          <Grid.Col span={{ base: 12, sm: 4 }}>
            <Paper withBorder p="lg" radius="lg" ta="center">
              <Text size="3rem" mb="sm">🎫</Text>
              <Text c="dimmed" size="sm">
                Member Since
              </Text>
              <Title order={3}>
                {user.createdAt ? new Date(user.createdAt).getFullYear() : '-'}
              </Title>
            </Paper>
          </Grid.Col>
        </Grid>

        {/* Settings Tabs */}
        <Paper withBorder radius="lg" p="lg">
          <Tabs defaultValue="profile">
            <Tabs.List>
              <Tabs.Tab value="profile" leftSection={<IconUser size={16} />}>
                Edit Profile
              </Tabs.Tab>
              <Tabs.Tab value="password" leftSection={<IconLock size={16} />}>
                Change Password
              </Tabs.Tab>
            </Tabs.List>

            <Tabs.Panel value="profile" pt="lg">
              <Stack gap="lg">
                <Alert
                  icon={<IconAlertCircle size={16} />}
                  title="Coming Soon"
                  color="blue"
                  variant="light"
                >
                  Profile edit functionality will be available soon. You can already view your current information below.
                </Alert>

                <form onSubmit={profileForm.onSubmit(handleUpdateProfile)}>
                  <Stack gap="lg">
                    <Group grow>
                      <TextInput
                        label="First Name"
                        placeholder="Your first name"
                        disabled
                        {...profileForm.getInputProps('firstName')}
                      />
                      <TextInput
                        label="Last Name"
                        placeholder="Your last name"
                        disabled
                        {...profileForm.getInputProps('lastName')}
                      />
                    </Group>

                    <TextInput
                      label="Email"
                      placeholder="your@email.com"
                      disabled
                      {...profileForm.getInputProps('email')}
                    />

                    <TextInput
                      label="Phone (Optional)"
                      placeholder="Your phone number"
                      disabled
                      value={user.phone || ''}
                    />

                    <Button
                      type="submit"
                      loading={isUpdatingProfile}
                      disabled
                    >
                      Update Profile
                    </Button>
                  </Stack>
                </form>
              </Stack>
            </Tabs.Panel>

            <Tabs.Panel value="password" pt="lg">
              <Stack gap="lg">
                <Alert
                  icon={<IconAlertCircle size={16} />}
                  title="Coming Soon"
                  color="blue"
                  variant="light"
                >
                  Password change functionality will be available soon.
                </Alert>

                <form onSubmit={passwordForm.onSubmit(handleChangePassword)}>
                  <Stack gap="lg">
                    <PasswordInput
                      label="Current Password"
                      placeholder="Enter your current password"
                      disabled
                      {...passwordForm.getInputProps('currentPassword')}
                    />

                    <Divider />

                    <PasswordInput
                      label="New Password"
                      placeholder="Enter new password"
                      disabled
                      {...passwordForm.getInputProps('newPassword')}
                    />

                    <PasswordInput
                      label="Confirm New Password"
                      placeholder="Confirm new password"
                      disabled
                      {...passwordForm.getInputProps('confirmPassword')}
                    />

                    <Button
                      type="submit"
                      loading={isChangingPassword}
                      disabled
                    >
                      Change Password
                    </Button>
                  </Stack>
                </form>
              </Stack>
            </Tabs.Panel>
          </Tabs>
        </Paper>

        {/* Danger Zone */}
        <Paper withBorder p="lg" radius="lg" style={{ borderColor: '#fa5252' }}>
          <Title order={3} c="red" mb="lg">
            Danger Zone
          </Title>
          <Alert
            icon={<IconAlertCircle size={16} />}
            title="Delete Account"
            color="red"
            variant="light"
          >
            <Stack gap="sm">
              <Text size="sm">
                Deleting your account is permanent and cannot be undone. All your data will be lost.
              </Text>
              <Button color="red" variant="light" disabled>
                Delete Account
              </Button>
            </Stack>
          </Alert>
        </Paper>
      </Stack>
    </Container>
  );
};
