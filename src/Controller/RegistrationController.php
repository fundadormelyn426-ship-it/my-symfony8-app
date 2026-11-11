<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        if ($this->getUser()) {
        return $this->redirectToRoute('home');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $name = trim($request->request->get('name', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            if (!$email || !$name || !$password) {
                $error = 'All fields are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                // Check kung naa nay user sa database
                $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                
                if ($existingUser) {
                    $error = 'This email is already registered.';
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                    $user->setRoles(['ROLE_USER']);

                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Account created! Please log in.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'error' => $error,
        ]);
    }
}