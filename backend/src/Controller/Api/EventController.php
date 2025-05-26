<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $events = $this->eventRepository->findBy(['status' => 'published']);
            
            $data = array_map(function (Event $event) {
                return [
                    'id' => $event->getId()->toString(),
                    'name' => $event->getName(),
                    'description' => $event->getDescription(),
                    'eventDate' => $event->getEventDate()->format('c'),
                    'venue' => $event->getVenue(),
                    'maxTickets' => $event->getMaxTickets(),
                    'ticketsSold' => $event->getTicketsSold(),
                    'availableTickets' => $event->getAvailableTickets(),
                    'status' => $event->getStatus(),
                    'organizer' => [
                        'id' => $event->getOrganizer()->getId()->toString(),
                        'fullName' => $event->getOrganizer()->getFullName(),
                    ],
                    'ticketTypes' => array_map(function ($ticketType) {
                        return [
                            'id' => $ticketType->getId()->toString(),
                            'name' => $ticketType->getName(),
                            'price' => $ticketType->getPrice(),
                            'priceFormatted' => number_format($ticketType->getPrice() / 100, 2),
                            'quantity' => $ticketType->getQuantity(),
                            'available' => $ticketType->getAvailableQuantity(),
                        ];
                    }, $event->getTicketTypes()->toArray()),
                ];
            }, $events);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch events',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_events_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $event = $this->eventRepository->find($id);
            
            if (!$event) {
                return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
            }

            $data = [
                'id' => $event->getId()->toString(),
                'name' => $event->getName(),
                'description' => $event->getDescription(),
                'eventDate' => $event->getEventDate()->format('c'),
                'venue' => $event->getVenue(),
                'maxTickets' => $event->getMaxTickets(),
                'ticketsSold' => $event->getTicketsSold(),
                'availableTickets' => $event->getAvailableTickets(),
                'status' => $event->getStatus(),
                'organizer' => [
                    'id' => $event->getOrganizer()->getId()->toString(),
                    'fullName' => $event->getOrganizer()->getFullName(),
                ],
                'ticketTypes' => array_map(function ($ticketType) {
                    return [
                        'id' => $ticketType->getId()->toString(),
                        'name' => $ticketType->getName(),
                        'price' => $ticketType->getPrice(),
                        'priceFormatted' => number_format($ticketType->getPrice() / 100, 2),
                        'quantity' => $ticketType->getQuantity(),
                        'available' => $ticketType->getAvailableQuantity(),
                    ];
                }, $event->getTicketTypes()->toArray()),
            ];

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch event',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_events_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function create(
        Request $request, 
        #[CurrentUser] ?User $user
    ): JsonResponse {
        try {
            if (!$user) {
                return $this->json([
                    'error' => 'User not authenticated',
                    'debug' => [
                        'getUser' => $this->getUser() ? 'exists' : 'null',
                        'token' => $request->headers->get('Authorization') ? 'present' : 'missing'
                    ]
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $event = new Event();
            $event->setName($data['name'] ?? '')
                  ->setDescription($data['description'] ?? '')
                  ->setEventDate(new \DateTimeImmutable($data['eventDate'] ?? 'now'))
                  ->setVenue($data['venue'] ?? '')
                  ->setMaxTickets($data['maxTickets'] ?? 0)
                  ->setOrganizer(1)
                  ->setStatus('draft');

            $errors = $this->validator->validate($event);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return $this->json([
                'id' => $event->getId()->toString(),
                'message' => 'Event created successfully',
                'organizer' => [
                    'id' => $user->getId()->toString(),
                    'fullName' => $user->getFullName()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create event',
                'message' => $e->getMessage(),
                'trace' => $this->getParameter('kernel.environment') === 'dev' ? $e->getTraceAsString() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/publish', name: 'api_events_publish', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZER')]
    public function publish(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        try {
            if (!$user) {
                return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
            }

            $event = $this->eventRepository->find($id);
            
            if (!$event) {
                return $this->json(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
            }

            if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $event->setStatus('published');
            $this->entityManager->flush();

            return $this->json(['message' => 'Event published successfully']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to publish event',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}