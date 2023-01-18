<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Exception;
use Drupal\Core\Link;
use Drupal\Core\Url;

class FrocoleException extends \Exception {}

/**
 * The List of Courses formatted as a Table.
 *
 * @category DisplayController
 * @package Drupal\frocole\Controller
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class DisplayCourseTableController extends ControllerBase {

  /**
   * The List of Courses formatted as a Table.
   *
   * @return form
   *   a List of Courses formatted as a Table.
   */
  public function index() {
    /* Test Code
    try {
      throw new FrocoleException('test message');
    } catch (FrocoleException $fe) {
      \Drupal::logger('frocole')->error($fe);
    }
    */

    // Fails
    // $request = \Drupal::request();
    // if ($route = $request->attributes->
    // get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
    // $route->setDefault('_title', t('All Courses'));
    // }
    // Create table header.
    $header_table = [
      'CourseID' => t('Course ID'),
      'CourseName' => t('Course Name'),
      'IPF_RD_parameters' => t('Individual Performance'),
      'GPF_RD_parameters' => t('Group Performance'),
      'PF_RD_parameters' => t('Product Performance'),
      'SegmentID' => t('Segment'),
      'LeraarUserID' => t('Teacher'),
      'CourseActive' => t('Active'),
      'Deadline' => t('Deadline'),

      'view' => t('View'),
      'delete' => t('Delete'),
      'edit' => t('Edit'),
    ];

    // Get data from database.
    $query = Database::getConnection('default', 'frocole')
      ->select('courses', 'c');
    $query
      ->join('users', 'u', 'c.LeraarUserID=u.UserID');
    $query
      ->join('segments', 's', 'c.SegmentID=s.SegmentID');
    $query
      ->fields('c', [
        'CourseID',
        'CourseName',
        'IPF_RD_parameters',
        'GPF_RD_parameters',
        'PF_RD_parameters',
        'SegmentID',
        'LeraarUserID',
        'CourseActive',
        'Deadline',
      ])
      ->fields('u', ['UserName'])
      ->fields('s', ['SegmentName'])
      ->orderBy('s.SegmentName', 'ASC')
      ->orderBy('c.CourseID', 'ASC');

    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $data) {
      $url_view   = Url::fromRoute('frocole.show_course_form', ['id' => $data->CourseID], []);
      $url_delete = Url::fromRoute('frocole.delete_course_form', ['id' => $data->CourseID], []);
      $url_edit   = Url::fromRoute('frocole.add_course_form', ['id' => $data->CourseID], []);

      $linkView   = Link::fromTextAndUrl(t('View'), $url_view);
      $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
      $linkEdit   = Link::fromTextAndUrl(t('Edit'), $url_edit);

      // [Leraar/Segment]
      $leraar = $data->UserName;
      $segment = $data->SegmentName;

      // Get data.
      $rows[] = [
        'CourseID' => $data->CourseID,
        'CourseName' => $data->CourseName,
        'IPF_RD_parameters' => $data->IPF_RD_parameters,
        'GPF_RD_parameters' => $data->GPF_RD_parameters,
        'PF_RD_parameters' => $data->PF_RD_parameters,
        'SegmentID' => '[' . $data->SegmentID . '] ' . $segment,
        'LeraarUserID' => '[' . $data->LeraarUserID . '] ' . $leraar,
        'CourseActive' => $data->CourseActive,
        'Deadline' => $data->Deadline,

        'view' => $linkView,
        'delete' => $linkDelete,
        'edit' => $linkEdit,
      ];

    }

    $form['links'] = [
      '#type' => 'item',
      '#markup' =>
      t('Manage Courses') . ' | ' .
      '<a href="' . Url::fromRoute('frocole.display_segments')->toString() . '">' . t('Manage Segments') . '</a> | ' .
      '<a href="' . Url::fromRoute('frocole.display_infos')->toString() . '">' . t('Manage Additional Infos') . '</a> | ' .
      '<a href="' . Url::fromRoute('frocole.add_course_form')->toString() . '">' . t('Add a new Course') . '</a>',
    ];

    // Render table.
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No data found'),
    ];

    return $form;

  }

}
