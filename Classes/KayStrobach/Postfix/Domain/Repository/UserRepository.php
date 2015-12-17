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
class UserRepository extends Repository {

    /**
     * @param string $email
     * @param Domain $domain
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
	public function findByEmailAndDomain($email, Domain $domain) {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                array(
                    $query->equals(
                        'email',
                        $email
                    ),
                    $query->equals(
                        'domain',
                        $domain
                    )
                )
            )
        );
        return $query->execute();
    }

}