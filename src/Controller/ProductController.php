<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\SearchType;
use App\Repository\ProductRepository;
use App\Service\Search;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    /**
     * @Route("/produits", name="products")
     * @param EntityManagerInterface $em
     * @param Request $request
     * @return Response
     */
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $products = $em->getRepository(Product::class)->findAll();
        $search   = new Search();
        $form     = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $products = $em->getRepository(Product::class)->findWithSearch($search);
        }
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form'     => $form->createView()
        ]);
    }

    /**
     * @Route("/produit/{id}", name="product")
     * @param EntityManagerInterface $em
     * @param $id
     * @return Response
     */
    public function show(EntityManagerInterface $em, $id): Response
    {
        $products = $em->getRepository(Product::class)->findBy(['isBest' => 1]);
        $product  = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            $this->redirectToRoute('products');
        }
        return $this->render('product/show.html.twig', [
            'product'  => $product,
            'products' => $products
        ]);
    }
}
