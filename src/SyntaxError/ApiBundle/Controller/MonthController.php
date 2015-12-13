<?php

namespace SyntaxError\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use SyntaxError\ApiBundle\Tools\Jsoner;

class MonthController extends Controller
{
    public function recordsAction(Request $request)
    {
        $dateTime = new \DateTime(
            $request->query->has('date') ? $request->query->get('date')." 00:00:00" : 'now'
        );
        $request->query->remove('date');
        $day = $this->get('syntax_error_api.month');
        $data = [];
        if( !$request->query->count() ) {
            foreach(get_class_methods('SyntaxError\ApiBundle\Service\MonthService') as $method) {
                if( preg_match('/create/', $method) ) {
                    $key = strtolower( str_replace('create', '', $method) );
                    $data[$key] = call_user_func_array([$day, $method], [$dateTime]);
                }
            }
        }

        foreach($request->query->all() as $key => $param) {
            $methodName = "create".ucfirst($key);
            if( method_exists($day, $methodName) ) {
                $data[$key] = call_user_func_array([$day, $methodName], [$dateTime]);
            }
        }
        $jsoner = new Jsoner();
        $jsoner->createJson($data);

        return $request->isXmlHttpRequest() ? $jsoner->createResponse() : $this->render(
            "SyntaxErrorApiBundle:Month:records.html.twig", [
            'title' => 'Month records: '.$dateTime->format("Y M") ,
            'json' => $jsoner->getJsonString()
        ]);
    }

    public function chartsAction(Request $request, $type)
    {
        $dateTime = new \DateTime(
            $request->query->has('date') ? $request->query->get('date')." 00:00:00" : 'now'
        );
        $month = $this->get('syntax_error_api.month');

        $jsoner = new Jsoner();
        $jsoner->createJson( $month->highDoubleFormatter($dateTime, ucfirst(strtolower($type)) ) );

        return $request->isXmlHttpRequest() ? $jsoner->createResponse() : $this->render(
            "SyntaxErrorApiBundle:Month:charts.html.twig", [
            'title' => 'Month '.ucfirst($type).": ".$dateTime->format("Y M"),
            'json' => $jsoner->getJsonString()
        ]);
    }
}