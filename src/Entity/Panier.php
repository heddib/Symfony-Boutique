<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PanierRepository")
 */
class Panier
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Utilisateur", inversedBy="panier", cascade={"persist", "remove"})
     */
    private $utilisateur;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $etat;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ContenuPanier", mappedBy="panier", cascade={"persist", "remove"})
     */
    private $contenuPanier;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getContenuPanier(): ?ContenuPanier
    {
        return $this->contenuPanier;
    }

    public function setContenuPanier(?ContenuPanier $contenuPanier): self
    {
        $this->contenuPanier = $contenuPanier;

        // set (or unset) the owning side of the relation if necessary
        $newPanier = null === $contenuPanier ? null : $this;
        if ($contenuPanier->getPanier() !== $newPanier) {
            $contenuPanier->setPanier($newPanier);
        }

        return $this;
    }
}
