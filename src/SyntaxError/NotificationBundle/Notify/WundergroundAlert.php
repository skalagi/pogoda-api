<?php

namespace SyntaxError\NotificationBundle\Notify;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SyntaxError\NotificationBundle\Kernel\NotifyInterface;

class WundergroundAlert implements NotifyInterface
{
    private $alerts;

    private $name;

    public function isActive(ContainerInterface $container)
    {
        $alerts = $container->get('syntax_error_api.wu')->read('alerts', 60);
        $this->alerts = json_decode($alerts)->alerts;
        return (bool)count($this->alerts);
    }

    public function getName()
    {
        $name = $this->alerts[0]->wtype_meteoalarm_name;
        if(strlen($name) < 1 || strlen($name) > 20) $name = "Oficjalne ostrzeżenie pogodowe dla woj. opolskiego.";

        $this->name = $name;
        return "[ALERT] $name";
    }

    public function getContent(\Twig_Environment $twig, array $additional = [])
    {
        foreach($this->alerts as $alert) {
            $alert->description = preg_replace('/.*polski:/','', $alert->description);
        }
        return $twig->render("Wunderground/alert.html.twig", array_merge([
            'name' => $this->name,
            'alert' => $this->alerts[0]
        ], $additional));
    }

}
