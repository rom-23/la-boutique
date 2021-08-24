<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Mail;

class RegisterController extends AbstractController
{
    private $em;

    /**
     * @Route("/inscription", name="register")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $encoder): Response
    {
        $notification = null;
        $user         = new User();
        $form         = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user        = $form->getData();
            $searchEmail = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if (!$searchEmail) {
                $password = $encoder->hashPassword($user, $user->getPassword());
                $user->setPassword($password);
                $em->persist($user);
                $em->flush();
                $mail    = new Mail();
                $content = 'Bonjour' . $user->getFirstName() . '<br/>Bienvenue sur la première boutique Made in France.<br/>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry standard dummy text ever since the 1500, when an unknown printer took a galley of type scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.';
                $mail->send($user->getEmail(), $user->getFirstName(), 'Bienvenue sur La Boutique Francaise', $content);
                $notification = 'Votre inscription s\'est correctement déroulée, vous pouvez vous connecter à votre compte';
            } else {
                $notification = 'L\'email que vous avez renseigné existe déjà.';
            }
        }

        return $this->render('register/index.html.twig', [
            'form'         => $form->createView(),
            'notification' => $notification
        ]);
    }
}
