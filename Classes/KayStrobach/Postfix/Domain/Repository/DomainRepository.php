<?php
namespace KayStrobach\Postfix\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use KayStrobach\Postfix\Domain\Model\Domain;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class DomainRepository extends Repository {

    /**
     * @param $name
     * @return Domain
     */
    public function findOneByDomain($name) {
        $query = $this->createQuery();
        $query->matching(
            $query->equals(
                'domain',
                $name
            )
        );
        return $query->execute()->getFirst();
    }
}