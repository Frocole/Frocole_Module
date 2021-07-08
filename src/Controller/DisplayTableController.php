<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class DisplayTableController
 *
 * @package Drupal\frocole\Controller
 */
class DisplayTableController extends ControllerBase
{
    public function index()
    {
        // Fails
        //$request = \Drupal::request();
        //if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
        //    $route->setDefault('_title', t('All Courses'));
        //}

        // create table header
        $header_table = array(
            'CourseID' => t('Course ID'),
            'CourseName' => t('Course Name'),
            'IPF_RD_parameters' => t('Individual Performance'),
            'GPF_RD_parameters' => t('Group Performance'),
            'LeraarUserID' => t('Teacher'),
            'CourseActive' => t('Active'),

            'view' => t('View'),
            'delete' => t('Delete'),
            'edit' => t('Edit'),
        );

        // get data from database
        $query = Database::getConnection('default', 'frocole')->select('courses', 'c');
        $query->fields('c', ['CourseID', 'CourseName', 'IPF_RD_parameters', 'GPF_RD_parameters', 'LeraarUserID', 'CourseActive']);
        $results = $query->execute()->fetchAll();

        $rows = array();
        foreach ($results as $data) {
            $url_delete = Url::fromRoute('frocole.delete_form', ['id' => $data->CourseID], []);
            $url_edit = Url::fromRoute('frocole.add_form', ['id' => $data->CourseID], []);
            $url_view = Url::fromRoute('frocole.show_data', ['id' => $data->CourseID], []);

            $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
            $linkEdit = Link::fromTextAndUrl(t('Edit'), $url_edit);
            $linkView = Link::fromTextAndUrl(t('View'), $url_view);

            //get data
            $rows[] = array(
                'CourseID' => $data->CourseID,
                'CourseName' => $data->CourseName,
                'IPF_RD_parameters' => $data->IPF_RD_parameters,
                'GPF_RD_parameters' => $data->GPF_RD_parameters,
                'LeraarUserID' => $data->LeraarUserID,
                'CourseActive' => $data->CourseActive,

                'view' => $linkView,
                'delete' => $linkDelete,
                'edit' =>  $linkEdit,
            );

        }

        $url = Url::fromRoute('frocole.add_form');

        $form['add'] = [
          '#type' => 'item',
          '#markup' => '<a href="'.$url->toString().'">'.t('Add a new Course').'</a>',
        ];
        
        // render table
        $form['table'] = [
        '#type' => 'table',
        '#header' => $header_table,
        '#rows' => $rows,
        '#empty' => t('No data found'),
        ];

        return $form;

    }
}
