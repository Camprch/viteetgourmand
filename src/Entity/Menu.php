<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    private ?string $theme = null;

    #[ORM\Column]
    private ?int $prixMinCentimes = null;

    #[ORM\Column]
    private ?int $personnesMin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsParticulieres = null;

    #[ORM\Column(length: 100)]
    private ?string $regime = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'menu')]
    private Collection $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getPrixMinCentimes(): ?int
    {
        return $this->prixMinCentimes;
    }

    public function setPrixMinCentimes(int $prixMinCentimes): static
    {
        $this->prixMinCentimes = $prixMinCentimes;

        return $this;
    }

    public function getPersonnesMin(): ?int
    {
        return $this->personnesMin;
    }

    public function setPersonnesMin(int $personnesMin): static
    {
        $this->personnesMin = $personnesMin;

        return $this;
    }

    public function getConditionsParticulieres(): ?string
    {
        return $this->conditionsParticulieres;
    }

    public function setConditionsParticulieres(?string $conditionsParticulieres): static
    {
        $this->conditionsParticulieres = $conditionsParticulieres;

        return $this;
    }

    public function getRegime(): ?string
    {
        return $this->regime;
    }

    public function setRegime(string $regime): static
    {
        $this->regime = $regime;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setMenu($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getMenu() === $this) {
                $commande->setMenu(null);
            }
        }

        return $this;
    }
}
