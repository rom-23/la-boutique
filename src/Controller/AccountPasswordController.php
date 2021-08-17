<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AccountPasswordController extends AbstractController
{
    private $em;

    /**
     * @Route("/compte/modifier-mot-de-passe", name="account_password")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $encoder): Response
    {
        $notification = null;
        $user         = $this->getUser();
        $form         = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $old_password = $form->get('old_password')->getData();
            if ($encoder->isPasswordValid($user, $old_password)) {
                $new_password = $form->get('new_password')->getData();
                $password     = $encoder->hashPassword($user, $new_password);
                $user->setPassword($password);
                $em->flush();
                $notification = "Votre mot de passe à été mis à jour.";
            } else{
                $notification = "Votre mot de passe actuel n'est pas le bon";
            }
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView(),
            'notification'=>$notification
        ]);
    }
}
