<?php

namespace App\Controller;

use App\Entity\Header;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    private $em;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(): Response
    {
        $products = $this->em->getRepository(Product::class)->findBy(['isBest' => 1]);
        $headers = $this->em->getRepository(Header::class)->findall();

        return $this->render('Home/index.html.twig', [
            'products' => $products,
            'headers'=>$headers
        ]);
    }
}
