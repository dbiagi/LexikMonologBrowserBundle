<?php

namespace Lexik\Bundle\MonologBrowserBundle\Controller;

use Doctrine\DBAL\DBALException;
use Lexik\Bundle\MonologBrowserBundle\Form\LogSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class DefaultController extends Controller {

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        $criteria = [
            'order_by'  => $request->query->get('sort', null),
            'direction' => $request->query->get('direction', null),
        ];

        $filter = $this->createForm(LogSearchType::class, $criteria, [
            'method' => 'get',
        ]);

        $filter->handleRequest($request);

        if ($filter->isValid()) {
            $criteria = $filter->getData();
        }

        try {
            $query = $this->getLogRepository()->getLogsQueryBuilder($criteria);
        } catch (DBALException $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            $query = [];
        }

        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->query->get('page', 1),
            $this->container->getParameter('lexik_monolog_browser.logs_per_page')
        );

        return $this->render('LexikMonologBrowserBundle:Default:index.html.twig', [
            'filter'      => $filter->createView(),
            'pagination'  => $pagination,
            'base_layout' => $this->getBaseLayout(),
        ]);
    }

    /**
     * @param Request $request
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function showAction(Request $request, $id) {
        $log = $this->getLogRepository()->getLogById($id);

        if (null === $log) {
            throw $this->createNotFoundException('The log entry does not exist');
        }

        $similarLogsQuery = $this->getLogRepository()->getSimilarLogsQueryBuilder($log);

        $similarLogs = $this->get('knp_paginator')->paginate(
            $similarLogsQuery,
            $request->query->get('page', 1),
            10
        );

        return $this->render('LexikMonologBrowserBundle:Default:show.html.twig', [
            'log'          => $log,
            'similar_logs' => $similarLogs,
            'base_layout'  => $this->getBaseLayout(),
        ]);
    }

    /**
     * @return string
     */
    protected function getBaseLayout() {
        return $this->container->getParameter('lexik_monolog_browser.base_layout');
    }

    /**
     * @return \Lexik\Bundle\MonologBrowserBundle\Model\LogRepository
     */
    protected function getLogRepository() {
        return $this->get('lexik_monolog_browser.model.log_repository');
    }

}
