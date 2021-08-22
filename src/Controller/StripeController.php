<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session")
     * @param Cart $cart
     * @param EntityManagerInterface $entityManager
     * @param $reference
     * @return Response
     * @throws ApiErrorException
     */
    public function index(Cart $cart, EntityManagerInterface $entityManager, $reference): Response
    {
        $YOUR_DOMAIN    = 'http://127.0.0.1:8000';
        $product_stripe = [];
        Stripe::setApiKey('sk_test_51JQ9T1EDco8ISEWwn8WCfoa6YsE0lSL0nd1eHR8bEfI5tKsPBAJAhK49duEGZMXTAlTvy8Wm6LNHpx3yi561EB7j00CNKcFwsr');
        $order = $entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if (!$order) {
            new JsonResponse(['error' => 'order']);
        }

        foreach ($order->getOrderDetails()->getValues() as $product) {
            $productObjet = $entityManager->getRepository(Product::class)->findOneBy(['name' => $product->getProduct()]);

            $product_stripe[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'   => $product->getProduct(),
                        'images' => [$YOUR_DOMAIN . "/uploads/" . $productObjet->getIllustration()],
                    ],
                    'unit_amount'  => $product->getPrice(),
                ],
                'quantity'   => $product->getQuantity()
            ];
        }
        $product_stripe[] = [
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name'   => $order->getCarrierName(),
                    'images' => [$YOUR_DOMAIN],
                ],
                'unit_amount'  => $order->getCarrierPrice(),
            ],
            'quantity'   => 1
        ];

        $checkout_session = Session::create([
            'customer_email'       => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items'           => [$product_stripe],
            'mode'                 => 'payment',
            'success_url'          => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url'           => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);
        $order->setStripeSessionId($checkout_session->id);
        $entityManager->flush();

        return new JsonResponse(['id' => $checkout_session->id]);
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_success")
     * @param EntityManagerInterface $entityManager
     * @param $stripeSessionId
     * @return Response
     */
    public function stripeSuccess(EntityManagerInterface $entityManager,Cart $cart, $stripeSessionId): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);
        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }
        if(!$order->getIsPaid()){
            $cart->remove();
            $order->setIsPaid(1);
            $entityManager->flush();
        }

        return $this->render('order/order_success.html.twig',[
            'order'=>$order
        ]);
    }

    /**
     * @Route("/commande/erreur/{stripeSessionId}", name="order_cancel")
     * @param EntityManagerInterface $entityManager
     * @param $stripeSessionId
     * @return Response
     */
    public function stripeCancel(EntityManagerInterface $entityManager, $stripeSessionId): Response
    {
        $order = $entityManager->getRepository(Order::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);
        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('order/order_cancel.html.twig',[
            'order'=>$order
        ]);
    }
}
