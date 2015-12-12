<?php

namespace SyntaxError\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ArchiveDayWindgustdirRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArchiveDayWindgustdirRepository extends EntityRepository
{
    public function avgMonth(\DateTime $dateTime)
    {
        $from = (new \DateTime( $dateTime->format('Y-m-01 00:00:00') ))->getTimestamp()+3600*20;
        $to = (new \DateTime( $dateTime->format('Y-m-t 23:59:59') ))->getTimestamp()+3600*20;
        $records = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:ArchiveDayWindgustdir")
            ->createQueryBuilder('a')->select('a.sum, a.count')
            ->where('a.datetime BETWEEN :from AND :to')
            ->setParameter('from', $from)->setParameter('to', $to)
            ->getQuery()->getResult();
        $days = [];
        foreach($records as $record) {
            $days[] = $record['count'] ? $record['sum'] / $record['count'] : 0;
        }
        $count = count($days);
        if(!$count) return null;
        return array_sum($days) / $count;
    }

}
