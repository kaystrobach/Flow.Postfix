<?php
namespace KayStrobach\Postfix\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use KayStrobach\Postfix\Domain\Model\Domain;
use KayStrobach\Postfix\Domain\Model\User;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class UserRepository extends Repository {

    /**
     * @param string $email
     * @param Domain $domain
     * @return \Neos\Flow\Persistence\QueryResultInterface
     */
	public function findByUsernameAndDomain($email, Domain $domain) {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                array(
                    $query->equals(
                        'username',
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

    /**
     * @param string $username
     * @param string $domain
     * @return User
     */
    public function findOneByUsernameAndDomainString($username, $domain) {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                array(
                    $query->equals(
                        'username',
                        $username
                    ),
                    $query->equals(
                        'domain.domain',
                        $domain
                    )
                )
            )
        );
        return $query->execute()->getFirst();
    }

    /**
     * @param Domain $domain
     * @return \Neos\Flow\Persistence\QueryResultInterface
     */
    public function findByDomain(Domain $domain) {
        $query = $this->createQuery();
        $query->matching(
            $query->equals(
                'domain',
                $domain
            )
        );
        return $query->execute();
    }

    /**
     * @param string $domain
     * @return \Neos\Flow\Persistence\QueryResultInterface
     */
    public function findByDomainString($domain) {
        $query = $this->createQuery();
        $query->matching(
            $query->equals(
                'domain.domain',
                $domain
            )
        );
        return $query->execute();
    }
}