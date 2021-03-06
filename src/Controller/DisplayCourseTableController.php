<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class DisplayCourseTableController
 *
 * @package Drupal\frocole\Controller
 */
class DisplayCourseTableController extends ControllerBase
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
            'SegmentID' => t('Segment'),
            'LeraarUserID' => t('Teacher'),
            'CourseActive' => t('Active'),
            'Deadline' => t('Deadline'),

            'view' => t('View'),
            'delete' => t('Delete'),
            'edit' => t('Edit'),
        );

        // get data from database
        $query = Database::getConnection('default', 'frocole')
            ->select('courses', 'c');
        $query
            ->join('users', 'u', 'c.LeraarUserID=u.UserID');
        $query
           ->join('segments', 's', 'c.SegmentID=s.SegmentID');
        $query
            ->fields('c', ['CourseID', 'CourseName', 'IPF_RD_parameters', 'GPF_RD_parameters', 'SegmentID', 'LeraarUserID', 'CourseActive', 'Deadline'])
            ->fields('u', ['UserName'])
            ->fields('s', ['SegmentName'])
            ->orderBy('s.SegmentName','ASC')
            ->orderBy('c.CourseID','ASC');

        $results = $query->execute()->fetchAll();

        $rows = array();
        foreach ($results as $data) {
            $url_view   = Url::fromRoute('frocole.show_course_form', ['id' => $data->CourseID], []);
            $url_delete = Url::fromRoute('frocole.delete_course_form', ['id' => $data->CourseID], []);
            $url_edit   = Url::fromRoute('frocole.add_course_form', ['id' => $data->CourseID], []);

            $linkView   = Link::fromTextAndUrl(t('View'), $url_view);
            $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
            $linkEdit   = Link::fromTextAndUrl(t('Edit'), $url_edit);

            //[Leraar/Segment]
            $leraar = $data->UserName;
            $segment = $data->SegmentName;

            //get data
            $rows[] = array(
                'CourseID' => $data->CourseID,
                'CourseName' => $data->CourseName,
                'IPF_RD_parameters' => $data->IPF_RD_parameters,
                'GPF_RD_parameters' => $data->GPF_RD_parameters,
                'SegmentID' => '['.$data->SegmentID.'] '.$segment,
                'LeraarUserID' => '['.$data->LeraarUserID.'] '.$leraar,
                'CourseActive' => $data->CourseActive,
                'Deadline' => $data->Deadline,
                
                'view' => $linkView,
                'delete' => $linkDelete,
                'edit' =>  $linkEdit,
            );

        }

        $form['links'] = [
            '#type' => 'item',
            '#markup' =>
            t('Manage Courses').' | '.
            '<a href="'.Url::fromRoute('frocole.display_segments')->toString().'">'.t('Manage Segments').'</a> | '.
            '<a href="'.Url::fromRoute('frocole.display_infos')->toString().'">'.t('Manage Additional Infos').'</a> | '.
            '<a href="'.Url::fromRoute('frocole.add_course_form')->toString().'">'.t('Add a new Course').'</a>',
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
