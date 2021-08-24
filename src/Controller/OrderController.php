<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Form\OrderType;
use App\Service\Cart;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @Route("/commande", name="order")
     * @param Cart $cart
     * @param Request $request
     * @return Response
     */
    public function index(Cart $cart, Request $request): Response
    {
        if (!$this->getUser()->getAdresses()->getValues()) {
            return $this->redirectToRoute('account_adress_add');
        }
        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart->getFull()
        ]);
    }

    /**
     * @Route("/commande/recapitulatif", name="order_recap")
     * @param Cart $cart
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function add(Cart $cart, Request $request, EntityManagerInterface $em): Response
    {

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $date            = new \DateTimeImmutable();
            $carriers        = $form->get('carriers')->getData();
            $delivery        = $form->get('addresses')->getData();
            $deliveryContent = $delivery->getFirstName() . ' ' . $delivery->getLastName();
            $deliveryContent .= '<br/>' . $delivery->getPhone();
            if ($delivery->getCompany()) {
                $deliveryContent .= '<br/>' . $delivery->getCompany();
            };
            $deliveryContent .= '<br/>' . $delivery->getAdress();
            $deliveryContent .= '<br/>' . $delivery->getPostal() . ' ' . $delivery->getCity();
            $deliveryContent .= '<br/>' . $delivery->getCountry();

            $order     = new Order();
            $reference = $date->format('dmY') . '.' . uniqid();
            $order->setReference($reference);
            $order->setUser($this->getUser());
            $order->setCreatedAt($date);
            $order->setCarrierName($carriers->getName());
            $order->setCarrierPrice($carriers->getPrice());
            $order->setDelivery($deliveryContent);
            $order->setState(0);
            $em->persist($order);
            foreach ($cart->getFull() as $product) {
                $orderDetails = new OrderDetails();
                $orderDetails->setMyOrder($order);
                $orderDetails->setProduct($product['product']->getName());
                $orderDetails->setQuantity($product['quantity']);
                $orderDetails->setPrice($product['product']->getPrice());
                $orderDetails->setTotal($product['product']->getPrice() * $product['quantity']);
                $em->persist($orderDetails);

            }
            $em->flush();

            return $this->render('order/add.html.twig', [
                'cart'      => $cart->getFull(),
                'carrier'   => $carriers,
                'delivery'  => $deliveryContent,
                'reference' => $order->getReference()
            ]);
        }

        return $this->redirectToRoute('cart');
    }
}
