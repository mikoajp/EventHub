import React, { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import {
  Container,
  Paper,
  TextInput,
  PasswordInput,
  Button,
  Title,
  Text,
  Stack,
  Alert,
  Group,
  Checkbox,
  Divider,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconInfoCircle, IconUserPlus, IconCalendarEvent } from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';

interface RegisterFormValues {
  email: string;
  password: string;
  confirmPassword: string;
  firstName: string;
  lastName: string;
  wantToBeOrganizer: boolean;
}

export const RegisterPage: React.FC = () => {
  const navigate = useNavigate();
  const { register, isAuthenticated } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const form = useForm<RegisterFormValues>({
    initialValues: {
      email: '',
      password: '',
      confirmPassword: '',
      firstName: '',
      lastName: '',
      wantToBeOrganizer: false,
    },
    validate: {
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
      password: (val) => (val.length < 8 ? 'Password must be at least 8 characters' : null),
      confirmPassword: (val, values) =>
        val !== values.password ? 'Passwords do not match' : null,
      firstName: (val) => (val.trim().length < 2 ? 'First name must be at least 2 characters' : null),
      lastName: (val) => (val.trim().length < 2 ? 'Last name must be at least 2 characters' : null),
    },
    validateInputOnBlur: true,
  });

  const handleSubmit = async (values: RegisterFormValues) => {
    setError(null);
    setIsSubmitting(true);
    try {
      await register(
        values.email,
        values.password,
        values.firstName,
        values.lastName,
        values.wantToBeOrganizer
      );
      navigate('/', { replace: true });
    } catch (error: any) {
      const errorMessage = error?.message || 'Registration failed';
      setError(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  useEffect(() => {
    if (isAuthenticated) {
      navigate('/', { replace: true });
    }
  }, [isAuthenticated, navigate]);

  return (
    <div style={{
      display: 'flex',
      minHeight: '100vh',
      alignItems: 'center',
      justifyContent: 'center',
      background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
    }}>
      <Container size="xs" w="100%" maw={460} px="sm">
        <Stack align="center" gap="xl">
          <Title
            order={1}
            ta="center"
            fw={800}
            style={{
              background: 'linear-gradient(to right, #4b6cb7, #182848)',
              WebkitBackgroundClip: 'text',
              WebkitTextFillColor: 'transparent',
            }}
          >
            🎫 EventHub
          </Title>

          <Paper 
            withBorder 
            shadow="lg" 
            p={32} 
            radius="lg" 
            w="100%"
            style={{
              border: '1px solid #e0e0e0',
              backgroundColor: 'rgba(255, 255, 255, 0.98)',
            }}
          >
            <Stack gap="xl">
              <div>
                <Title order={2} ta="center" mb={4}>
                  Create Account
                </Title>
                <Text c="dimmed" size="sm" ta="center">
                  Join EventHub and discover amazing events
                </Text>
              </div>

              {error && (
                <Alert 
                  variant="light" 
                  color="red" 
                  icon={<IconInfoCircle size={18} />}
                  radius="md"
                >
                  <Text size="sm">{error}</Text>
                </Alert>
              )}

              <Alert 
                variant="light" 
                color="indigo" 
                icon={<IconInfoCircle size={18} />}
                radius="md"
              >
                <Text size="sm">
                  Create your account to access all features and purchase tickets
                </Text>
              </Alert>

              <form onSubmit={form.onSubmit(handleSubmit)}>
                <Stack gap="lg">
                  <Group grow>
                    <TextInput
                      label="First Name"
                      placeholder="John"
                      required
                      radius="md"
                      size="md"
                      {...form.getInputProps('firstName')}
                    />
                    <TextInput
                      label="Last Name"
                      placeholder="Doe"
                      required
                      radius="md"
                      size="md"
                      {...form.getInputProps('lastName')}
                    />
                  </Group>

                  <TextInput
                    label="Email"
                    placeholder="your@email.com"
                    required
                    radius="md"
                    size="md"
                    autoComplete="email"
                    {...form.getInputProps('email')}
                  />

                  <PasswordInput
                    label="Password"
                    placeholder="Your password"
                    required
                    radius="md"
                    size="md"
                    autoComplete="new-password"
                    {...form.getInputProps('password')}
                  />

                  <PasswordInput
                    label="Confirm Password"
                    placeholder="Confirm your password"
                    required
                    radius="md"
                    size="md"
                    autoComplete="new-password"
                    {...form.getInputProps('confirmPassword')}
                  />

                  <Divider label="Account Type" labelPosition="center" />

                  <Checkbox
                    label={
                      <div>
                        <Group gap="xs" wrap="nowrap">
                          <IconCalendarEvent size={18} style={{ color: '#4b6cb7', marginTop: 2 }} />
                          <div>
                            <Text size="sm" fw={500}>I want to organize events</Text>
                            <Text size="xs" c="dimmed">
                              Enable event creation and management features
                            </Text>
                          </div>
                        </Group>
                      </div>
                    }
                    {...form.getInputProps('wantToBeOrganizer', { type: 'checkbox' })}
                  />

                  <Button
                    type="submit"
                    fullWidth
                    size="md"
                    radius="md"
                    loading={isSubmitting}
                    leftSection={<IconUserPlus size={18} />}
                    mt="sm"
                  >
                    {isSubmitting ? 'Creating account...' : 'Create Account'}
                  </Button>
                </Stack>
              </form>

              <Text size="sm" c="dimmed" ta="center">
                Already have an account?{' '}
                <Link to="/login" style={{ color: 'var(--mantine-color-blue-6)', textDecoration: 'none', fontWeight: 500 }}>
                  Sign in here
                </Link>
              </Text>
            </Stack>
          </Paper>
        </Stack>
      </Container>
    </div>
  );
};
