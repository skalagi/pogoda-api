<?php

namespace SyntaxError\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ArchiveDayRainRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArchiveDayRainRepository extends EntityRepository
{
    /**
     * @param \DateTime $dateTime
     * @return float
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findMonthSum(\DateTime $dateTime)
    {
        $from = (new \DateTime( $dateTime->format('Y-m-01 00:00:00') ))->getTimestamp()+3600*20;
        $to = (new \DateTime( $dateTime->format('Y-m-t 23:59:59') ))->getTimestamp()+3600*20;
        $records = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:ArchiveDayRain")->createQueryBuilder('a')
            ->select('a.sum')
            ->where('a.datetime BETWEEN :from AND :to')
            ->setParameter('from', $from)->setParameter('to', $to)->getQuery()->getResult();
        $sum = 0;
        foreach($records as $record) {
            $sum += $record['sum'];
        }
        return $sum;
    }
}
