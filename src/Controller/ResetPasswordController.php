<?php

namespace App\Controller;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use App\Service\Mail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{

    private $em;

    /**
     * ResetPasswordController constructor.
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        if ($request->get('email')) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);
            if ($user) {
                // Enregistrer en base la demande de reset_password
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user);
                $resetPassword->setCreatedAt(new \DateTimeImmutable());
                $resetPassword->setToken(uniqid());
                $this->em->persist($resetPassword);
                $this->em->flush();
                // Envoyer un email à utilisateur avec lien pour mettre a jour le password
                $url     = $this->generateUrl('update_password', [
                    'token' => $resetPassword->getToken()
                ]);
                $content = 'Bonjour' . $user->getFirstName() . '<br/>Vous avez demande de reinitialiser votre mot de passe<br/> ';
                $content .= 'Merci de bien vouloir cliquer sur le lien suivant pour <a href=' . $url . '>reinitialiser votre mot de passe</a>';
                $mail    = new Mail();
                $mail->send($user->getEmail(), $user->getFirstName(), 'Reinitialiser votre mot de passe', $content);
                $this->addFlash('notice', 'Vous allez recevoir un mail pour reinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }
        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mot-de-passe/{token}", name="update_password")
     * @param Request $request
     * @param UserPasswordHasherInterface $encoder
     * @param $token
     * @return Response
     */
    public function update(Request $request,UserPasswordHasherInterface $encoder, $token)
    {
        $resetPassword = $this->em->getRepository(ResetPassword::class)->findOneBy(['token' => $token]);
        if (!$resetPassword) {
            return $this->redirectToRoute('reset_password');
        }
        // Verifier si le createdAt = now - 3h
        $now = new \DateTimeImmutable();
        if ($now > $resetPassword->getCreatedAt()->modify('+ 3 hours')) {
            // le token a expiré
            $this->addFlash('notice', 'Votre demande de mot de passe a expirée. Merci de la renouveller.');
            return $this->redirectToRoute('reset_password');
        }

        // Rendre une vue avec mot de passe et confirmez votre mot de passe
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Encodage des mots de passe
            $newPassword = $form->get('new_password')->getData();
            $password     = $encoder->hashPassword($resetPassword->getUser(), $newPassword);
            $resetPassword->getUser()->setPassword($password);
            $this->em->flush();
            $this->addFlash('notice','Votre mot de passe a bien été mis à jour');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
