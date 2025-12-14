<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        UserRepository $users
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $fullName = trim((string) ($payload['full_name'] ?? $payload['fullName'] ?? ''));
        $plainPassword = (string) ($payload['password'] ?? '');

        if ($users->findOneByEmail($email)) {
            return new JsonResponse(['message' => 'User already exists'], Response::HTTP_CONFLICT);
        }

        $user = (new User())
            ->setEmail($email)
            ->setFullName($fullName)
            ->setRoles(['ROLE_USER']);

        $errors = $validator->validate($user);
        $passwordErrors = [];

        if ($plainPassword === '' || strlen($plainPassword) < 8) {
            $passwordErrors[] = 'Password must be at least 8 characters.';
        }

        if ($errors->count() > 0 || $passwordErrors) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }
            if ($passwordErrors) {
                $messages['password'] = $passwordErrors;
            }

            return new JsonResponse([
                'message' => 'Validation failed',
                'errors' => $messages,
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'User registered successfully',
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        SessionInterface $session
    ): JsonResponse {
        try {
            $payload = $request->toArray();
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $plainPassword = (string) ($payload['password'] ?? '');

        $user = $users->findOneByEmail($email);
        if (!$user || !$passwordHasher->isPasswordValid($user, $plainPassword)) {
            return new JsonResponse(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Basic session-based login to pair with the logout endpoint.
        $session->set('user_id', $user->getId());

        return new JsonResponse([
            'message' => 'Login successful',
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(SessionInterface $session): JsonResponse
    {
        $session->invalidate();

        return new JsonResponse(['message' => 'Logged out']);
    }
}
