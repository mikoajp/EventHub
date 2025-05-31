<?php

namespace App\Security;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
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

class JwtAuthenticator extends AbstractAuthenticator
{
    private const HEADER_AUTH = 'Authorization';
    private const TOKEN_PREFIX = 'Bearer ';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenExtractorInterface $tokenExtractor
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::HEADER_AUTH) &&
               str_starts_with($request->headers->get(self::HEADER_AUTH), self::TOKEN_PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->tokenExtractor->extract($request);
        
        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('No JWT token found');
        }
        
        // Parse JWT token and extract user identifier
        // This is a simplified example - in a real implementation, 
        // you would use the JWT service to decode and validate the token
        try {
            $payload = $this->decodeJwtToken($token);
            $userIdentifier = $payload['username'] ?? null;
            
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
    
    /**
     * Simple JWT token decoder
     * In a real implementation, you would use the JWT service
     */
    private function decodeJwtToken(string $token): array
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT token format');
        }
        
        $payload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
        $payloadData = json_decode($payload, true);
        
        if (!$payloadData) {
            throw new \InvalidArgumentException('Invalid JWT payload');
        }
        
        return $payloadData;
    }
}
