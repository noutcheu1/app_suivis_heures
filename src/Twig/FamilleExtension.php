<?php

namespace App\Twig;

use App\Entity\Principal\Famille;
use App\Repository\FamilleRepository;
use App\Repository\ParentFamilleRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FamilleExtension extends AbstractExtension
{
    /** @var array<string, string> request-scoped parent cache keyed by numFam */
    private array $parentCache = [];

    /** @var array<string, string> request-scoped family name cache keyed by numFam */
    private array $nomCache = [];

    public function __construct(
        private ParentFamilleRepository $parentRepo,
        private FamilleRepository       $familleRepo,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('famille_label', [$this, 'familleLabel']),
        ];
    }

    /**
     * Returns "Nom Famille / Prénom Nom Parent" or just the family name when no parent exists.
     * Accepts a Famille entity or a numFam string.
     */
    public function familleLabel(Famille|string|null $famille): string
    {
        if ($famille === null) {
            return '';
        }

        if ($famille instanceof Famille) {
            $numFam = $famille->getNumeroFamille() ?? '';
            $nomFam = $famille->getNomFamille() ?? $numFam;
        } else {
            $numFam = $famille;
            if (!isset($this->nomCache[$numFam])) {
                $entity = $this->familleRepo->findByNumero($numFam);
                $this->nomCache[$numFam] = $entity ? ($entity->getNomFamille() ?? $numFam) : $numFam;
            }
            $nomFam = $this->nomCache[$numFam];
        }

        if (!$numFam) {
            return (string) $nomFam;
        }

        if (!isset($this->parentCache[$numFam])) {
            $parents = $this->parentRepo->findByFamille($numFam);
            $parentLabel = '';
            if (!empty($parents)) {
                $p = $parents[0];
                $parentLabel = ($p->getNom() ?? '');
            }
            $this->parentCache[$numFam] = $parentLabel;
        }

        $parentLabel = $this->parentCache[$numFam];

        if ($parentLabel === '') {
            return (string) $nomFam;
        }
        //  ne pas toucher les espaces pour que le label soit aligné dans les tableaux même sans parent
        return  $parentLabel;
    }
}
