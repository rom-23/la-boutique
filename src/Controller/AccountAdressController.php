<?php

namespace App\Controller;

use App\Entity\Adress;
use App\Form\AdressType;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountAdressController extends AbstractController
{
    private $em;

    /**
     * AccountAdressController constructor.
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/compte/adresses", name="account_adress")
     */
    public function index(): Response
    {
        return $this->render('account/adress.html.twig', [
        ]);
    }

    /**
     * @Route("/compte/ajouter-une-adresse", name="account_adress_add")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request, Cart $cart): Response
    {
        $adress = new Adress();
        $form   = $this->createForm(AdressType::class, $adress);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adress->setUser($this->getUser());
            $this->em->persist($adress);
            $this->em->flush();
            if ($cart->get()) {
                return $this->redirectToRoute('order');
            } else {
                return $this->redirectToRoute('account_adress');
            }
        }
            return $this->render('account/adress_form.html.twig', [
                'form' => $form->createView()
            ]);
        }

        /**
         * @Route("/compte/modifier-une-adresse/{id}", name="account_adress_edit")
         * @param Request $request
         * @param $id
         * @return Response
         */
        public
        function edit(Request $request, $id): Response
        {
            $adress = $this->em->getRepository(Adress::class)->find($id);
            if (!$adress || $adress->getUser() != $this->getUser()) {
                return $this->redirectToRoute('account_adress');
            }
            $form = $this->createForm(AdressType::class, $adress);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->em->flush();

                return $this->redirectToRoute('account_adress');
            }

            return $this->render('account/adress_form.html.twig', [
                'form' => $form->createView()
            ]);
        }

        /**
         * @Route("/compte/supprimer-une-adresse/{id}", name="account_adress_delete")
         * @param $id
         * @return Response
         */
        public
        function delete($id): Response
        {
            $adress = $this->em->getRepository(Adress::class)->find($id);
            if ($adress && $adress->getUser() == $this->getUser()) {
                $this->em->remove($adress);
                $this->em->flush();
            }

            return $this->redirectToRoute('account_adress');
        }
    }
