import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconInfoCircle, IconLogin } from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';

interface LoginFormValues {
  email: string;
  password: string;
}

export const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const { login, isAuthenticated } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const form = useForm<LoginFormValues>({
    initialValues: {
      email: 'admin@example.com',
      password: 'password',
    },
    validate: {
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
      password: (val) => (val.length < 6 ? 'Password must be at least 6 characters' : null),
    },
    validateInputOnBlur: true,
  });

  const handleSubmit = async (values: LoginFormValues) => {
    setIsSubmitting(true);
    try {
      await login(values.email, values.password);
      navigate('/', { replace: true });
    } catch (error: any) {
      const isAuthError = error?.message === 'Invalid credentials' || /Unauthenticated/i.test(error?.message || '');
      form.setErrors({
        email: isAuthError ? 'Invalid email or password' : 'Login failed',
        password: isAuthError ? 'Invalid email or password' : 'Login failed',
      });
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
            EventHub
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
                  Welcome back!
                </Title>
                <Text c="dimmed" size="sm" ta="center">
                  Sign in to access your dashboard
                </Text>
              </div>

              <Alert 
                variant="light" 
                color="indigo" 
                icon={<IconInfoCircle size={18} />}
                radius="md"
              >
                <Text size="sm">
                  <strong>Demo accounts:</strong><br />
                  Admin: admin@example.com / password<br />
                  User: user@example.com / password
                </Text>
              </Alert>

              <form onSubmit={form.onSubmit(handleSubmit)}>
                <Stack gap="lg">
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
                    autoComplete="current-password"
                    {...form.getInputProps('password')}
                  />

                  <Button
                    type="submit"
                    fullWidth
                    size="md"
                    radius="md"
                    loading={isSubmitting}
                    leftSection={<IconLogin size={18} />}
                    mt="sm"
                  >
                    {isSubmitting ? 'Signing in...' : 'Sign in'}
                  </Button>
                </Stack>
              </form>
            </Stack>
          </Paper>
        </Stack>
      </Container>
    </div>
  );
};