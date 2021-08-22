<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountOrderController extends AbstractController
{
    /**
     * @Route("/compte/mes-commandes", name="account_order")
     * @param EntityManagerInterface $entityManager
     * @param $user
     * @return Response
     */
    public function index(EntityManagerInterface $entityManager): Response
    {
        $orders = $entityManager->getRepository(Order::class)->findSuccessOrders($this->getUser());
        return $this->render('account/order.html.twig', [
            'orders' => $orders
        ]);
    }

    /**
     * @Route("/compte/mes-commandes/{reference}", name="account_order_show")
     * @param EntityManagerInterface $entityManager
     * @param $reference
     * @return Response
     */
    public function show(EntityManagerInterface $entityManager, $reference): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if(!$order||$order->getUser() != $this->getUser()){
            return $this->redirectToRoute('account_order');
        }
        return $this->render('account/order_show.html.twig', [
            'order' => $order
        ]);
    }
}
