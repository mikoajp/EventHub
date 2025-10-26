<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Application\Service\UserApplicationService;
use App\DTO\UserRegistrationDTO;
use App\Exception\User\UserNotAuthenticatedException;
use App\Infrastructure\Validation\RequestValidatorInterface;
use App\Presenter\UserPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserApplicationService $userApplicationService,
        private RequestValidatorInterface $requestValidator,
        private UserPresenter $userPresenter
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            throw new UserNotAuthenticatedException();
        }
        return $this->json($this->userPresenter->presentLoginResponse($this->userApplicationService->formatLoginResponse($user)));
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            throw new UserNotAuthenticatedException();
        }
        return $this->json($this->userPresenter->presentProfile($this->userApplicationService->getUserProfile($user)));
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->requestValidator->extractJsonData($request);
        
        $registrationDTO = new UserRegistrationDTO(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['phone'] ?? null
        );

        $user = $this->userApplicationService->registerUser($registrationDTO);
        
        return $this->json(
            $this->userPresenter->presentRegistrationResponse($this->userApplicationService->formatRegistrationResponse($user)),
            JsonResponse::HTTP_CREATED
        );
    }
}