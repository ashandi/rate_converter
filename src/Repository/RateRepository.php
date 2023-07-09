<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Rate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rate>
 *
 * @method Rate|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rate|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rate[]    findAll()
 * @method Rate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rate::class);
    }

    public function save(Rate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Rate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findDirectRate(int $currencyFromId, int $currencyToId): ?float
    {
        $rate = $this->findOneBy(
            ['currencyFrom' => $currencyFromId, 'currencyTo' => $currencyToId],
            ['datetime' => 'DESC']
        );

        return $rate == null ? null : $rate->getRate();
    }

    public function findRateThroughThirdCurrency(int $currencyFromId, int $currencyToId): ?float
    {
        try {
            return $this->createQueryBuilder('r1')
                ->innerJoin(Rate::class, 'r2', Join::WITH, 'r1.currencyTo = r2.currencyFrom')
                ->select('r1.rate * r2.rate')
                ->andWhere('r1.currencyFrom = :currencyFromId')
                ->andWhere('r2.currencyTo = :currencyToId')
                ->orderBy('r1.datetime', 'DESC')
                ->addOrderBy('r2.datetime', 'DESC')
                ->setParameter('currencyFromId', $currencyFromId)
                ->setParameter('currencyToId', $currencyToId)
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult()
                ;
        } catch (NoResultException $exception) {
            return null;
        }
    }
}
