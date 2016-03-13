<?php

namespace SyntaxError\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use SyntaxError\NotificationBundle\Kernel\Storage;

class InfoController extends Controller
{
    public function sentenceAction(Request $request, $ext)
    {
        $call = $request->query->has('callback') ? $request->query->get('callback') : null;
        $jsoner = $this->get('syntax_error_api.info')->all();

        return $ext == 'json' ? $jsoner->createResponse($call) : $this->render(
            'SyntaxErrorApiBundle:Info:sentence.html.twig', [
            'json' => $jsoner->getJsonString()
        ]);
    }

    public function socketAction()
    {
        return $this->render("SyntaxErrorApiBundle:Info:socket.html.twig");
    }

    public function subscriberAction(Request $request)
    {
        if(!$request->request->has('email')) {
            return new JsonResponse(['status' => 'Require email in POST parameters.', 500]);
        }

        $storage = new Storage();
        $response = new JsonResponse($storage->hasSubscriber(
            $request->request->get('email')
        ));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    public function subscribeAction(Request $request)
    {
        if(!$request->request->has('email')) {
            return new JsonResponse(['status' => 'Require email in POST parameters.', 500]);
        }
        $email = $request->request->get('email');
        if(!strlen($email)) {
            return new JsonResponse(['status' => 'Require email in POST parameters.', 500]);
        }

        $redisStorage = new Storage();
        if($redisStorage->hasSubscriber($email)) {
            $byeEmail = \Swift_Message::newInstance()
                ->setFrom([$this->container->getParameter('mailer_user') => 'Stacja pogodowa Skałągi'])
                ->setTo($email)
                ->setSubject('Anulowałeś subskrybcje powiadomień pogodowych.')
                ->setBody($this->renderView("SyntaxErrorApiBundle:Info:removeMail.html.twig", ['email' => $email]), 'text/html', 'utf-8')
                ->setDescription('Wiadomość wygenerowana przez stacje pogodową w Skałągach.');
            $this->get('mailer')->send($byeEmail);
            $response = $redisStorage->removeSubscriber($request->request->get('email')) ? new JsonResponse(['status' => 'Removed.']) : new JsonResponse(['status' => 'Not found.'], 404);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        $welcomeEmail = \Swift_Message::newInstance()
            ->setFrom([$this->container->getParameter('mailer_user') => 'Stacja pogodowa Skałągi'])
            ->setTo($email)
            ->setSubject('Witamy! Zapisałeś się na bezpłatne powiadomienia pogodowe.')
            ->setBody($this->renderView("SyntaxErrorApiBundle:Info:addMail.html.twig", ['email' => $email]), 'text/html', 'utf-8')
            ->setDescription('Wiadomość wygenerowana przez stacje pogodową w Skałągach.');
        $this->get('mailer')->send($welcomeEmail);

        $response = $redisStorage->addSubscriber($request->request->get('email')) ? new JsonResponse(['status' => 'Added.']) : new JsonResponse(['status' => 'Internal server error.'], 500);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }
}
