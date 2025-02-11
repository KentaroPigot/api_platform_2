<?php

namespace App\Doctrine;

use App\Entity\CheeseListing;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class CheeseListingSetOwnerListener
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(CheeseListing $cheeseListing)
    {
        if ($cheeseListing->getOwner()) {
            return;
        }

        if ($this->security->getUser()) {
            $cheeseListing->setOwner($this->security->getUser());
        }
    }
}
