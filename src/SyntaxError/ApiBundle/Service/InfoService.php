<?php

namespace SyntaxError\ApiBundle\Service;

use Doctrine\ORM\EntityManager;
use SyntaxError\ApiBundle\Tools\Jsoner;
use SyntaxError\ApiBundle\Tools\Uniter;

/**
 * Class InfoService
 * Create array of strings about weather information.
 *
 * @package SyntaxError\ApiBundle\Service
 */
class InfoService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The newest instance in Archive repository.
     *
     * @var null|\SyntaxError\ApiBundle\Entity\Archive
     */
    private $last;

    /**
     * InfoService constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->last = $this->em->getRepository("SyntaxErrorApiBundle:Archive")->findLast();
    }

    /**
     * @return string
     */
    private function temperature()
    {
        $tempTrend = round($this->em->getRepository("SyntaxErrorApiBundle:Archive")->getLastTrend('outTemp'), 3);
        $tempSentence = "Aktualna temperatura wynosi ".round($this->last->getOutTemp(), 2).Uniter::temp;
        if(!$tempTrend) {
            $tempSentence .= ' i jest stała.';
        } else {
            $tempSentence .= ($tempTrend > 0 ? " i rośnie o " : " i spada o ");
            $tempSentence .= abs($tempTrend).Uniter::temp.Uniter::trend.".";
        }
        return $tempSentence;
    }

    /**
     * @return string
     */
    private function barometer()
    {
        $baroTrend = round($this->em->getRepository("SyntaxErrorApiBundle:Archive")->getLastTrend('barometer'), 3);
        $baroSentence = "Aktualne ciśnienie wynosi ".round($this->last->getBarometer(), 2).Uniter::barometer;
        if(!$baroTrend) {
            $baroSentence .= ' i jest stałe.';
        } else {
            $baroSentence .= ($baroTrend > 0 ? " i rośnie o " : " i spada o ");
            $baroSentence .= abs($baroTrend).Uniter::barometer.Uniter::trend.".";
        }
        return $baroSentence;
    }

    /**
     * @return string
     */
    private function humidity()
    {
        $humidity = round($this->last->getOutHumidity(), 2);
        $sentence = "Aktualna wilgotność wynosi $humidity".Uniter::humidity;

        if($humidity <= 35) $sentence .= " i jest zbyt niska.";
        elseif($humidity > 35 && $humidity < 75) $sentence .= ' i jest odpowiednia.';
        else $sentence .= " i jest zbyt wysoka.";
        return $sentence;
    }

    /**
     * @return array|string
     */
    private function wind()
    {
        $speed = $this->last->getWindSpeed();
        $dir = $this->last->getWindDir();
        if(!$speed) return "Nie odnotowano żadnych podmuchów wiatru.";

        $sentence = "Odnotowano ";
        if($speed <= 5) $sentence .= "powiew ";
        elseif($speed > 5 && $speed < 7) $sentence .= "lekki wiatr ";
        elseif($speed >= 7 && $speed < 10) $sentence .= "umiarkowany wiatr ";
        elseif($speed >= 10 && $speed < 14) $sentence .= "mocny wiatr ";
        elseif($speed >= 14 && $speed < 18) $sentence .= "silny wiatr ";
        elseif($speed > 18 && $speed < 21) $sentence .= "bardzo silny wiatr ";
        else $sentence = "ekstremalnie silny wiatr ";


        $sentence .= "o prędkości ".round($speed, 3).Uniter::speed;

        $gustDir = Uniter::windDirPl( $this->last->getWindGustDir() );
        $gust = "Porywy wiatru $gustDir przekraczają ".round($this->last->getWindGust()-1, 0).Uniter::speed.".";

        return [$sentence." ".Uniter::windDirPl($dir).".", $gust];
    }

    /**
     * @return array
     */
    private function rain()
    {
        $rainRate = round($this->last->getRainRate(), 3);
        if(!$rainRate) $sentence = "Aktualnie nie odnotowano opadów deszczu.";
        elseif($rainRate <= 1) $sentence = "Aktualnie odnotowano delikatne opady ";
        elseif($rainRate > 1 && $rainRate <= 5) $sentence = "Aktualnie odnotowano umiarkowane opady ";
        elseif($rainRate > 5 && $rainRate <= 10) $sentence = "Aktualnie odnotowano silne opady ";
        else $sentence = "Aktualnie odnotowano ekstremalnie silne opady ";

        if($rainRate) {
            $sentence .= $rainRate.Uniter::rain."/h";
        }

        $rain = $this->em->getRepository("SyntaxErrorApiBundle:ArchiveDayRain")->findOneBy([
            'datetime' => (new \DateTIme('now'))->setTime(0,0,0)->getTimestamp()
        ])->getSum();

        return [
            $sentence,
            $rain ? "Dzisiaj spadło ".round($rain, 3).Uniter::rain." deszczu." : "Dzisiaj nie odnotowano opadów deszczu."
        ];
    }

    /**
     * Return serialized array of weather strings as Jsoner instance.
     *
     * @return Jsoner
     */
    public function all()
    {
        $data = [];
        foreach(get_class_methods($this) as $method) {
            if($method != '__construct' && $method != 'all') {
                $returned = call_user_func([$this, $method]);
                if( is_array($returned) ) {
                    $data = array_merge($data, $returned);
                } elseif($returned) {
                    $data[] = $returned;
                }
            }
        }
        $jsoner = new Jsoner();
        $jsoner->createJson($data);
        return $jsoner;
    }
}
