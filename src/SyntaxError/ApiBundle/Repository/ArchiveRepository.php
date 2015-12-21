<?php

namespace SyntaxError\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use SyntaxError\ApiBundle\Tools\Uniter;
use SyntaxError\ApiBundle\Entity\Archive;

/**
 * archiveRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ArchiveRepository extends EntityRepository
{
    /**
     * @return null|Archive
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLast()
    {
        return $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'desc')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * @return float
     */
    public function getLastTempTrend()
    {
        $temperatures = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")->createQueryBuilder('a')
            ->select('a.outTemp')->orderBy('a.dateTime', 'desc')->setMaxResults(12)->getQuery()->getResult();
        return Uniter::getTrend($temperatures, 'outTemp');
    }

    /**
     * @return float
     */
    public function getLastPressTrend()
    {
        $pressures = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")->createQueryBuilder('a')
            ->select('a.pressure')->orderBy('a.dateTime', 'desc')->setMaxResults(12)->getQuery()->getResult();
        return Uniter::getTrend($pressures, 'pressure');
    }

    /**
     * @param \DateTime $dateTime
     * @return array|Archive[]
     */
    public function findByDay(\DateTime $dateTime)
    {
        $from = new \DateTime($dateTime->format("Y-m-d H:i:s"));
        $to = new \DateTime($dateTime->format("Y-m-d H:i:s"));
        $from->setTime(0,0,0);
        $to->setTime(23,59,59);
        return $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")
            ->createQueryBuilder('a')
            ->where('a.dateTime BETWEEN :from AND :to')
            ->setParameter( 'from', $from->getTimestamp() )
            ->setParameter( 'to', $to->getTimestamp() )
            ->getQuery()->getResult();
    }

    /**
     * @param $property
     * @param bool|true $getMax
     * @return mixed|null
     */
    public function findTodayRecord($property, $getMax = true)
    {
        $from = (new \DateTime('now'))->setTime(0,0,0)->getTimestamp();
        $to = (new \DateTime('now'))->setTime(23,59,59)->getTimestamp();
        try {
            $record = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")->createQueryBuilder('a')
                ->select("a.$property, a.dateTime")
                ->where('a.dateTime BETWEEN :from AND :to')
                ->andWhere("a.$property is not NULL")
                ->setParameter('from', $from)->setParameter('to', $to)
                ->orderBy("a.$property", $getMax ? 'desc' : 'asc')->setMaxResults(1)->getQuery()->getOneOrNullResult();
        } catch(NonUniqueResultException $e) {
            return null;
        }
        if(!$record) return null;
        return $record;
    }

    /**
     * @return float|null
     */
    public function findAvgWindToday()
    {
        $from = (new \DateTime('now'))->setTime(0,0,0)->getTimestamp();
        $to = (new \DateTime('now'))->setTime(23,59,59)->getTimestamp();
        $archives = $this->getEntityManager()->getRepository("SyntaxErrorApiBundle:Archive")->createQueryBuilder('a')
            ->select('a.windGustDir')
            ->where('a.dateTime BETWEEN :from AND :to')
            ->setParameter('from', $from)->setParameter('to', $to)
            ->getQuery()->getResult();
        $sum = 0;
        foreach($archives as $archive) {
            $sum += $archive['windGustDir'];
        }
        if(!$sum) return null;
        return $sum/count($archives);
    }
}
