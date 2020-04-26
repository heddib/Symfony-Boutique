<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Form\ContenuPanierType;
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @Route("/{_locale}")
 */
class ProduitController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        // Récupère Doctrine (service de gestion de BDD)
        $pdo = $this->getDoctrine()->getManager();

        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        // Analyse la requête HTTP
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Le formulaire a été envoyé, on le sauvegarde
            // On récupère le fichier du formulaire
            $fichier = $form->get('photo')->getData();
            // Si un fichier a été uploadé
            if ($fichier) {
                $nomFichier = uniqid() . '.' . $fichier->guessExtension();

                try {
                    $fichier->move(
                        $this->getParameter('upload_dir'),
                        $nomFichier
                    );
                } catch (FileException $e) {
                    $this->addFlash("danger", $translator->trans("flash.error.upload_photo"));
                    return $this->redirectToRoute('home');
                }

                $produit->setPhoto($nomFichier);
            }

            $pdo->persist($produit); // prepare
            $pdo->flush();           // execute

            $this->addFlash("success", $translator->trans("flash.success.added_product"));
        }

        // Récupère tous les produits
        $produits = $pdo->getRepository(Produit::class)->findAll();

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'new_product_form' => $form->createView()
        ]);
    }

    /**
     * @Route("/produit/{id}", name="view_product")
     */
    public function produit(Produit $produit = null, Request $request, TranslatorInterface $translator)
    {
        $user = $this->getUser();

        if ($produit != null) {

            if ($user) {
                // Check si un panier existe
                $panier = $user->getPanier();

                $contenu = new ContenuPanier($produit, $panier);
                $form = $this->createForm(ContenuPanierType::class, $contenu);

                $edit = $this->createForm(ProduitType::class, $produit);

                // Analyse la requête HTTP - Ajout au panier
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    // Le formulaire a été envoyé, on le sauvegarde
                    // Check des conditions pour ajouter au panier
                    if ($contenu->getQte() > 0 && $contenu->getQte() <= $produit->getStock()) {
                        $pdo = $this->getDoctrine()->getManager();
                        $pdo->persist($contenu); // prepare
                        $pdo->flush();          // execute

                        //
                        $panier->addContenuPanier($contenu);
                        $pdo->persist($panier); // prepare
                        $pdo->flush();          // execute

                        $this->addFlash("success", $translator->trans("flash.success.added_product_to_cart"));
                        return $this->redirectToRoute('panier');
                    } else {
                        $this->addFlash("danger", $translator->trans("flash.error.quantity"));
                        return $this->redirectToRoute('home');
                    }
                }

                // Analyse la requête HTTP - Modifier produit
                $edit->handleRequest($request);
                if ($edit->isSubmitted() && $edit->isValid()) {
                    // Le formulaire a été envoyé, on le sauvegarde
                    if ($produit->getStock() > 0) {
                        $pdo = $this->getDoctrine()->getManager();
                        $pdo->persist($produit); // prepare
                        $pdo->flush();          // execute

                        $this->addFlash("success", $translator->trans("flash.success.added_product_to_cart"));
                        return $this->redirectToRoute('home');
                    } else {
                        $this->addFlash("danger", $translator->trans("flash.error.quantity"));
                        return $this->redirectToRoute('home');
                    }
                }

                return $this->render('produit/produit.html.twig', [
                    'produit' => $produit,
                    'form_add_panier' => $form->createView(),
                    'form_edit_produit' => $edit->createView()
                ]);
            }

            return $this->render('produit/produit.html.twig', [
                'produit' => $produit
            ]);
        } else {
            // Produit n'existe pas, on redirige l'internaute
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route("/produit/delete/{id}", name="delete_product")
     */
    public function delete(Produit $produit = null, TranslatorInterface $translator)
    {
        if ($produit != null) {
            $pdo = $this->getDoctrine()->getManager();
            $pdo->remove($produit);
            $pdo->flush();

            if ($produit->getPhoto() != null) {
                // Supprimer si y a une photo
                unlink($this->getParameter('upload_dir') . $produit->getPhoto());
            }

            $this->addFlash("success", $translator->trans("flash.success.deleted_product"));
        } else {
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
        }
        return $this->redirectToRoute('produits');
    }
}
