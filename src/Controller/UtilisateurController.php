<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *  @Route("/{_locale}/dashboard")
 */
class UtilisateurController extends AbstractController
{
    /**
     * @Route("/", name="dashboard")
     */
    public function dashboard(Request $request, TranslatorInterface $translator)
    {
        $user = $this->getUser();
        $edit = $this->createForm(UtilisateurType::class, $user);

        // Analyse la requête HTTP - Modifier utilisateur
        $edit->handleRequest($request);
        if ($edit->isSubmitted()) {
            if ($edit->isValid()) {
                // Le formulaire a été envoyé, on le sauvegarde
                $pdo = $this->getDoctrine()->getManager();
                $pdo->persist($user); // prepare
                $pdo->flush();        // execute

                $this->addFlash("success", $translator->trans("flash.success.user_edit"));
                return $this->redirectToRoute('dashboard');
            } else {
                $this->addFlash("danger", $translator->trans("flash.error.user_edit"));
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('utilisateur/dashboard.html.twig', [
            'user' => $user,
            'commandes' => $user->getCommandes(),
            'form_edit_user' => $edit->createView()
        ]);
    }

    /**
     * @Route("/commande/{id}", name="view_commande")
     */
    public function commande(Panier $panier = null, Request $request, TranslatorInterface $translator)
    {
        $user = $this->getUser();

        if ($panier != null) {

            // Le panier appartient à l'utilisateur connecté donc on l'affiche
            if ($user->getId() == $panier->getUtilisateur()->getId()) {
                return $this->render('utilisateur/commande.html.twig', [
                    'panier' => $panier,
                ]);
            } else {
                // Mauvais user
                $this->addFlash("danger", $translator->trans("flash.error.wrong_user_cart"));
                return $this->redirectToRoute('home');
            }
        } else {
            // Produit n'existe pas, on redirige l'internaute
            $this->addFlash("danger", $translator->trans("flash.error.cart_not_found"));
            return $this->redirectToRoute('home');
        }
    }

    /**
     * @Route("/admin", name="admin")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function admin()
    {
        $user = $this->getUser();

        $pdo = $this->getDoctrine()->getManager();
        // Tous les users
        $users = $pdo->getRepository(Utilisateur::class)->findAll();
        // Paniers non achetés
        $paniers = $pdo->getRepository(Panier::class)->findby(['etat' => false]);

        // On prend la date du jour d'avant pour les utilisateurs récents (comparaison twig)
        $date = new \DateTime();
        $date->modify('-1 day');

        return $this->render('utilisateur/admin.html.twig', [
            'user' => $user,
            'users' => $users,
            'hier' => $date,
            'paniers' => $paniers
        ]);
    }
}
