<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface as EM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface as Hasher;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface as Validator;

final class AuthController extends AbstractController
{
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $req, EM $em, Hasher $hasher, Validator $validator): Response
    {
        $data = json_decode($req->getContent(), true) ?? [];
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $firstName = $data['firstName'] ?? null;
        $lastName = $data['lastName'] ?? null;
        $phone = $data['phone'] ?? null;


        if (!$email || !$password) {
        return $this->json(['message' => 'email et password sont requis'], Response::HTTP_BAD_REQUEST);
        }


        $existing = $em->getRepository(User::class)->findOneBy(['email' => strtolower($email)]);
        if ($existing) {
        return $this->json(['message' => 'Un compte existe déjà avec cet email'], Response::HTTP_CONFLICT);
        }


        $user = (new User())
        ->setEmail($email)
        ->setFirstName($firstName)
        ->setLastName($lastName)
        ->setPhone($phone)
        ;
        $user->setPassword($hasher->hashPassword($user, $password));


        $errors = $validator->validate($user);
        if (count($errors) > 0) {
        $messages = [];
        foreach ($errors as $e) { $messages[] = $e->getPropertyPath() . ': ' . $e->getMessage(); }
        return $this->json(['message' => 'Validation error', 'errors' => $messages], Response::HTTP_BAD_REQUEST);
        }


        $em->persist($user);
        $em->flush();


        return $this->json(['id' => $user->getId(), 'email' => $user->getEmail()], Response::HTTP_CREATED);
    }
}
