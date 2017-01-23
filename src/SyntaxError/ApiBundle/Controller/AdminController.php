<?php

namespace SyntaxError\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use SyntaxError\ApiBundle\Tools\Jsoner;
use SyntaxError\NotificationBundle\Kernel\Storage;

class AdminController extends Controller
{
    public function loggedAction(Request $request)
    {
        $sendForm = -1;
        $subscribers = new Storage();
        if($request->request->has('mailing')) {
            $sendForm = 0;
            foreach($subscribers->getSubscribers() as $subscriber) {
                $welcomeEmail = \Swift_Message::newInstance()
                    ->setFrom([$this->container->getParameter('mailer_user') => 'Stacja pogodowa Skałągi'])
                    ->setTo($subscriber)
                    ->setSubject($request->request->get('subject'))
                    ->setBody($request->request->get('mailing'), 'text/html', 'utf-8')
                    ->setDescription('Wiadomość wygenerowana przez stacje pogodową w Skałągach.');
                /** @noinspection PhpParamsInspection */
                $this->get('mailer')->send($welcomeEmail);
                $sendForm++;
            }
        }
        $admin = $this->get('syntax_error_api.admin');

        if($request->isXmlHttpRequest()) {
            $jsoner = new Jsoner();
            $socketInfo = $admin->createSocketInformer();
            unset($socketInfo['log']);
            $jsoner->createJson($socketInfo);
            return $jsoner->createResponse(null);
        }

        return $this->render('SyntaxErrorApiBundle:Admin:logged.html.twig', [
            'hardware' => $admin->createHardwareInformer(),
            'database' => $admin->createDatabaseInformer(),
            'server' => $admin->createServerInformer(),
            'socket' => $admin->createSocketInformer(),
            'sendForm' => $sendForm,
            'subscribers' => $subscribers->getSubscribers()
        ]);
    }

    public function loginAction()
    {
        if( $this->isGranted('ROLE_ADMIN') ) {
            return $this->redirectToRoute('syntax_error_api_admin_logged');
        }
        $utils = $this->get('security.authentication_utils');

        return $this->render('SyntaxErrorApiBundle:Admin:login.html.twig', [
            'lastUserName' => $utils->getLastUsername(),
            'authError' => $utils->getLastAuthenticationError()
        ]);
    }

    public function loginCheckAction()
    {
    }
}
