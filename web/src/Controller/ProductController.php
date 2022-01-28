<?php

namespace App\Controller;

use App\Entity\Command;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\CommandType;
use App\Repository\CommandRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    /**
     * @Route("/product", name="product.listProduct")
     */
    public function listProduits(ProductRepository $productRepository): Response
    {
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productRepository->findAll();
        //dd($products);
        return $this->render('product/listProduct.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route("/product/{id}", name="product.showProduct")
     */
    public function produit($id, ProductRepository $productRepository)
    {
        $products = $productRepository->find($id);

        //dd($products);

        return $this->render('product/showProduct.html.twig', [
            'product' => $products
        ]);
    }

    /**
     * @Route("/cart/add/{id}", name="addPanier")
     */
    public function add($id, SessionInterface $session)
    {
        $cart = $session->get('panier', []);
        $cart[$id] = 1;
        $session->set('panier', $cart);

        //dd($session);
    }

    /**
     * @Route("/cart", name="product.Panier")
     */
    public function panier(ProductRepository $productRepository, SessionInterface $session, ManagerRegistry $doct)
    {
        $cart = $session->get('panier', []);

        $paniers = [];
        $total = 0;
        $quantity = 0;
        $em = $doct->getManager();
        foreach($cart as $id => $quantity)
        {
            $products = $productRepository->find($id);
                $paniers[$id]['name'] = $products->getName();
                $paniers[$id]['quantity'] = $quantity;
                $paniers[$id]['price'] = $products->getPrice();
                $paniers[$id]['id'] = $products->getId();

            $total += $products->getPrice() * $quantity;
        } 

        //dd($paniers);
        $command = new Command();

        $commandForm = $this->createForm(CommandType::class, $command);
        $request = Request::createFromGlobals();
        $commandForm -> handleRequest($request);
        
        if ($commandForm->isSubmitted() && $commandForm->isValid()){
            $command->setCreatedAt(new \DateTime);

            foreach($cart as $id => $quantity)
            {
                $product = $productRepository->find($id);
                $command->addProduct($product);

                $total += $products->getPrice() * $quantity;
            }

            $em->persist($command);
            $em->flush();
            return $this->redirectToRoute('product.Panier');

        }
        return $this->render('product/showPanier.html.twig', [
            'panier' => $paniers,
            'total' => $total,
            'productForm' => $commandForm->createView()
        ]);
    }

    /**
     * @Route("/cart/delete/{id}", name="deleteProduct")
     */
    public function deleteProduct(SessionInterface $session, $id)
    {
        $cart = $session->get('panier', []);

        if(!empty($cart[$id])){  
                unset($cart[$id]);
                
        }
        $session->set("panier", $cart);

        return $this->redirectToRoute("product.Panier");
    }

    /**
     * @Route("/", name="product.accueil")
     */
    public function accueil(ProductRepository $productRepository){
        $cheaps = $productRepository->findBy(
            [],
            ['price' => 'ASC' ],
            5
        );
        $recents = $productRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );
        // dd($recent);
        return $this->render('product/accueil.html.twig',[ 'cheaps' => $cheaps, 'recents' => $recents]);      

    }

    /**
     * @Route("/command", name="product.listCommands")
     */
    public function listCommands(CommandRepository $commandRepository): Response
    {
        $commandRepository = $this->getDoctrine()->getRepository(Command::class);
        $commands = $commandRepository->findAll();
        //dd($commands);
        return $this->render('product/listCommands.html.twig', [
            'commands' => $commands
        ]);
    }

    /**
     * @Route("/command/{id}", name="product.showCommands")
     */
    public function showCommand($id, CommandRepository $commandRepository)
    {
        $commands = $commandRepository->find($id);
        $products = $commands->getProducts()->toArray();

        //dd($commands);

        return $this->render('product/showCommands.html.twig', [
            'commands' => $commands,
            'products' => $products
        ]);
    }
}
