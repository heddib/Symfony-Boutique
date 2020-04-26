<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @Route("/{_locale}")
 */
class PanierController extends AbstractController
{
    /**
     * @Route("/panier", name="panier")
     */
    public function index()
    {
        $user = $this->getUser();

        if($user) {
            return $this->render('panier/index.html.twig', [
                'panier' => $user->getPanier()
            ]);
        } else {
            return $this->render('panier/index.html.twig');
        }
    }

    /**
     * @Route("/panier/delete/{id}", name="delete_from_panier")
     */
    public function delete(ContenuPanier $contenu = null, TranslatorInterface $translator)
    {
        if ($contenu != null) {
            $pdo = $this->getDoctrine()->getManager();
            $pdo->remove($contenu);
            $pdo->flush();
            $this->addFlash("success", $translator->trans("flash.success.product_deleted_from_cart"));
        } else {
            $this->addFlash("danger", $translator->trans("flash.error.product_not_found"));
        }
        return $this->redirectToRoute('panier');
    }

    /**
     * @Route("/panier/validate/", name="validate_panier")
     */
    public function validate(TranslatorInterface $translator)
    {
        $user = $this->getUser();
        $pdo = $this->getDoctrine()->getManager();
        $panier = $user->getPanier();

        $panier->validate();
        $pdo->persist($panier);
        $pdo->flush();

        $this->addFlash("success", $translator->trans("flash.success.validated_cart"));
        
        return $this->redirectToRoute('panier');
    }
}
