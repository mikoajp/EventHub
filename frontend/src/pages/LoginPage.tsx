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
  Group,
  Tabs,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import { IconInfoCircle, IconLogin, IconUserPlus } from '@tabler/icons-react';
import { useAuth } from '../contexts/AuthContext';

interface LoginFormValues {
  email: string;
  password: string;
}

interface RegisterFormValues {
  email: string;
  password: string;
  confirmPassword: string;
  firstName: string;
  lastName: string;
}

export const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const { login, register, isAuthenticated } = useAuth();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<string | null>('login');

  const loginForm = useForm<LoginFormValues>({
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

  const registerForm = useForm<RegisterFormValues>({
    initialValues: {
      email: '',
      password: '',
      confirmPassword: '',
      firstName: '',
      lastName: '',
    },
    validate: {
      email: (val) => (/^\S+@\S+$/.test(val) ? null : 'Invalid email'),
      password: (val) => (val.length < 6 ? 'Password must be at least 6 characters' : null),
      confirmPassword: (val, values) =>
        val !== values.password ? 'Passwords do not match' : null,
      firstName: (val) => (val.trim().length < 2 ? 'First name must be at least 2 characters' : null),
      lastName: (val) => (val.trim().length < 2 ? 'Last name must be at least 2 characters' : null),
    },
    validateInputOnBlur: true,
  });

  const handleLogin = async (values: LoginFormValues) => {
    setError(null);
    setIsSubmitting(true);
    try {
      await login(values.email, values.password);
      navigate('/', { replace: true });
    } catch (error: any) {
      const errorMessage = error?.message === 'Invalid credentials' ? 'Invalid email or password' : 'Login failed';
      setError(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleRegister = async (values: RegisterFormValues) => {
    setError(null);
    setIsSubmitting(true);
    try {
      await register(values.email, values.password, values.firstName, values.lastName, false);
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
            <Tabs value={activeTab} onChange={setActiveTab} defaultValue="login">
              <Tabs.List grow mb="lg">
                <Tabs.Tab value="login" leftSection={<IconLogin size={16} />}>
                  Sign In
                </Tabs.Tab>
                <Tabs.Tab value="register" leftSection={<IconUserPlus size={16} />}>
                  Sign Up
                </Tabs.Tab>
              </Tabs.List>

              <Tabs.Panel value="login">
                <Stack gap="xl">
                  <div>
                    <Title order={2} ta="center" mb={4}>
                      Welcome back!
                    </Title>
                    <Text c="dimmed" size="sm" ta="center">
                      Sign in to access your dashboard
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
                      <strong>Demo accounts:</strong><br />
                      Admin: admin@example.com / password<br />
                      User: user@example.com / password
                    </Text>
                  </Alert>

                  <form onSubmit={loginForm.onSubmit(handleLogin)}>
                    <Stack gap="lg">
                      <TextInput
                        label="Email"
                        placeholder="your@email.com"
                        required
                        radius="md"
                        size="md"
                        autoComplete="email"
                        {...loginForm.getInputProps('email')}
                      />

                      <PasswordInput
                        label="Password"
                        placeholder="Your password"
                        required
                        radius="md"
                        size="md"
                        autoComplete="current-password"
                        {...loginForm.getInputProps('password')}
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
              </Tabs.Panel>

              <Tabs.Panel value="register">
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

                  <form onSubmit={registerForm.onSubmit(handleRegister)}>
                    <Stack gap="lg">
                      <Group grow>
                        <TextInput
                          label="First Name"
                          placeholder="John"
                          required
                          radius="md"
                          size="md"
                          {...registerForm.getInputProps('firstName')}
                        />
                        <TextInput
                          label="Last Name"
                          placeholder="Doe"
                          required
                          radius="md"
                          size="md"
                          {...registerForm.getInputProps('lastName')}
                        />
                      </Group>

                      <TextInput
                        label="Email"
                        placeholder="your@email.com"
                        required
                        radius="md"
                        size="md"
                        autoComplete="email"
                        {...registerForm.getInputProps('email')}
                      />

                      <PasswordInput
                        label="Password"
                        placeholder="Your password"
                        required
                        radius="md"
                        size="md"
                        autoComplete="new-password"
                        {...registerForm.getInputProps('password')}
                      />

                      <PasswordInput
                        label="Confirm Password"
                        placeholder="Confirm your password"
                        required
                        radius="md"
                        size="md"
                        autoComplete="new-password"
                        {...registerForm.getInputProps('confirmPassword')}
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
                </Stack>
              </Tabs.Panel>
            </Tabs>
          </Paper>
        </Stack>
      </Container>
    </div>
  );
};