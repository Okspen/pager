<?php

declare(strict_types=1);

namespace Pager\Service\Bot;

use Pager\Entity\City;
use Pager\System\Doctrine\BotCityFilter;
use Doctrine\ORM\EntityManagerInterface;

class CityContext
{
    /** @var City */
    private $city;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setCity(City $city): void
    {
        $this->city = $city;

        $this->entityManager->getFilters()->enable(BotCityFilter::NAME);

        $filter = $this->entityManager->getFilters()->getFilter(BotCityFilter::NAME);
        $filter->setParameter('cityId', $city->getId());
    }

    public function getCity(): City
    {
        return $this->city;
    }

    public function cityWasSet(): bool
    {
        return null !== $this->city;
    }
}
