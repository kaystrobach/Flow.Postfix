<?php
namespace KayStrobach\Postfix\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use KayStrobach\Postfix\Domain\Model\Domain;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class DomainRepository extends Repository {

    /**
     * @param $name
     * @return Domain
     */
    public function findOneByName($name) {
        $query = $this->createQuery();
        $query->matching(
            $query->equals(
                'name',
                $name
            )
        );
        return $query->execute()->getFirst();
    }
}