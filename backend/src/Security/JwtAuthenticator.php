<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JwtAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const HEADER_AUTH = 'Authorization';
    private const TOKEN_PREFIX = 'Bearer ';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenExtractorInterface $tokenExtractor,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly LoggerInterface $logger
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::HEADER_AUTH) &&
               str_starts_with($request->headers->get(self::HEADER_AUTH), self::TOKEN_PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->tokenExtractor->extract($request);
        
        if (!is_string($token) || $token === '') {
            throw new CustomUserMessageAuthenticationException('No JWT token found');
        }
        
        // Decode and validate JWT using Lexik encoder
        try {
            $payload = $this->jwtEncoder->decode($token);
            if (!$payload) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }
            // Use the same claim as configured (user_id_claim: email)
            $userIdentifier = $payload['email'] ?? $payload['username'] ?? null;
            
            if (!$userIdentifier) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }
            
            return new SelfValidatingPassport(
                new UserBadge($userIdentifier, function($userIdentifier) {
                    $user = $this->userRepository->findByEmail($userIdentifier);
                    
                    if (!$user) {
                        throw new CustomUserMessageAuthenticationException('User not found');
                    }
                    
                    return $user;
                })
            );
        } catch (\Exception $e) {
            $this->logger->error('JWT Authentication failed', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 50) . '...',
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw new CustomUserMessageAuthenticationException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => 'Authentication failed: ' . $exception->getMessage()
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
    
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            'error' => [
                'message' => 'Full authentication is required to access this resource.',
                'type' => 'AuthenticationException'
            ]
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
