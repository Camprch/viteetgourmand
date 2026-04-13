<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Menu $menu = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CommuneLivraison $communeLivraison = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCommande = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $datePrestation = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heurePrestation = null;

    #[ORM\Column(length: 255)]
    private ?string $adressePrestation = null;

    #[ORM\Column(length: 200)]
    private ?string $nomPrenomClient = null;

    #[ORM\Column(length: 30)]
    private ?string $gsmClient = null;

    #[ORM\Column]
    private ?int $prixMenuTotalCentimes = null;

    #[ORM\Column]
    private ?int $fraisLivraisonCentimes = null;

    #[ORM\Column]
    private ?int $reductionAppliqueeCentimes = null;

    #[ORM\Column]
    private ?int $prixTotalCentimes = null;

    #[ORM\Column]
    private ?int $nbPersonnes = null;

    #[ORM\Column]
    private ?bool $pretMateriel = null;

    /**
     * @var Collection<int, CommandeStatut>
     */
    #[ORM\OneToMany(targetEntity: CommandeStatut::class, mappedBy: 'commande')]
    private Collection $commandeStatuts;

    public function __construct()
    {
        $this->commandeStatuts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getCommuneLivraison(): ?CommuneLivraison
    {
        return $this->communeLivraison;
    }

    public function setCommuneLivraison(?CommuneLivraison $communeLivraison): static
    {
        $this->communeLivraison = $communeLivraison;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeImmutable $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getDatePrestation(): ?\DateTimeImmutable
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(\DateTimeImmutable $datePrestation): static
    {
        $this->datePrestation = $datePrestation;

        return $this;
    }

    public function getHeurePrestation(): ?\DateTimeImmutable
    {
        return $this->heurePrestation;
    }

    public function setHeurePrestation(\DateTimeImmutable $heurePrestation): static
    {
        $this->heurePrestation = $heurePrestation;

        return $this;
    }

    public function getAdressePrestation(): ?string
    {
        return $this->adressePrestation;
    }

    public function setAdressePrestation(string $adressePrestation): static
    {
        $this->adressePrestation = $adressePrestation;

        return $this;
    }

    public function getNomPrenomClient(): ?string
    {
        return $this->nomPrenomClient;
    }

    public function setNomPrenomClient(string $nomPrenomClient): static
    {
        $this->nomPrenomClient = $nomPrenomClient;

        return $this;
    }

    public function getGsmClient(): ?string
    {
        return $this->gsmClient;
    }

    public function setGsmClient(string $gsmClient): static
    {
        $this->gsmClient = $gsmClient;

        return $this;
    }

    public function getPrixMenuTotalCentimes(): ?int
    {
        return $this->prixMenuTotalCentimes;
    }

    public function setPrixMenuTotalCentimes(int $prixMenuTotalCentimes): static
    {
        $this->prixMenuTotalCentimes = $prixMenuTotalCentimes;

        return $this;
    }

    public function getFraisLivraisonCentimes(): ?int
    {
        return $this->fraisLivraisonCentimes;
    }

    public function setFraisLivraisonCentimes(int $fraisLivraisonCentimes): static
    {
        $this->fraisLivraisonCentimes = $fraisLivraisonCentimes;

        return $this;
    }

    public function getReductionAppliqueeCentimes(): ?int
    {
        return $this->reductionAppliqueeCentimes;
    }

    public function setReductionAppliqueeCentimes(int $reductionAppliqueeCentimes): static
    {
        $this->reductionAppliqueeCentimes = $reductionAppliqueeCentimes;

        return $this;
    }

    public function getPrixTotalCentimes(): ?int
    {
        return $this->prixTotalCentimes;
    }

    public function setPrixTotalCentimes(int $prixTotalCentimes): static
    {
        $this->prixTotalCentimes = $prixTotalCentimes;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;

        return $this;
    }

    public function isPretMateriel(): ?bool
    {
        return $this->pretMateriel;
    }

    public function setPretMateriel(bool $pretMateriel): static
    {
        $this->pretMateriel = $pretMateriel;

        return $this;
    }

    /**
     * @return Collection<int, CommandeStatut>
     */
    public function getCommandeStatuts(): Collection
    {
        return $this->commandeStatuts;
    }

    public function addCommandeStatut(CommandeStatut $commandeStatut): static
    {
        if (!$this->commandeStatuts->contains($commandeStatut)) {
            $this->commandeStatuts->add($commandeStatut);
            $commandeStatut->setCommande($this);
        }

        return $this;
    }

    public function removeCommandeStatut(CommandeStatut $commandeStatut): static
    {
        if ($this->commandeStatuts->removeElement($commandeStatut)) {
            if ($commandeStatut->getCommande() === $this) {
                $commandeStatut->setCommande(null);
            }
        }

        return $this;
    }
}
