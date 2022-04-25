<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Shows a list of Segments.
 *
 * @category DisplayController
 * @package Drupal\frocole\Controller
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class DisplaySegmentTableController extends ControllerBase {

  /**
   * Shows a list of Segments.
   *
   * @return Form
   *   Shows a list of Segments.
   */
  public function index() {
    // Create table header.
    $header_table = [
      'SegmentID' => t('Segment ID'),
      'Segment Name' => t('Segment Name'),

        // 'view' => t('View'),
      'delete' => t('Delete'),
      'edit' => t('Edit'),
      'info' => t('Info'),
    ];

    // Get data from database.
    $query = Database::getConnection('default', 'frocole')
      ->select('segments', 's');
    $query
      ->fields('s')
      ->orderBy('s.SegmentName', 'ASC');

    $results = $query->execute()->fetchAll();

    $rows = [];
    foreach ($results as $data) {
      $url_delete = Url::fromRoute('frocole.delete_segment_form', ['id' => $data->SegmentID], []);
      $url_edit   = Url::fromRoute('frocole.add_segment_form', ['id' => $data->SegmentID], []);
      $url_info   = Url::fromRoute('frocole.add_info_form', ['sid' => $data->SegmentID], []);

      // $linkView = Link::fromTextAndUrl(t('View'), $url_view);
      $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
      $linkEdit   = Link::fromTextAndUrl(t('Edit'), $url_edit);
      $linkInfo   = Link::fromTextAndUrl(t('Info'), $url_info);

      // Get data.
      $rows[] = [
        'SegmentID' => $data->SegmentID,
        'SegmentName' => (empty($data->SegmentName) ? "<empty>" : $data->SegmentName),

        // 'view' => $linkView,
        // 'view'   => "View",
        'delete' => $linkDelete,
        'edit'   => $linkEdit,
        'info'   => $linkInfo,
      ];
    }

    $form['links'] = [
      '#type' => 'item',
      '#markup' =>
      '<a href="' . Url::fromRoute('frocole.display_courses')->toString() . '">' . t('Manage Courses') . '</a> | ' .
      t('Manage Segments') . ' | ' .
      '<a href="' . Url::fromRoute('frocole.display_infos')->toString() . '">' . t('Manage Additional Infos') . '</a> | ' .
      '<a href="' . Url::fromRoute('frocole.add_segment_form')->toString() . '">' . t('Add a new Segment') . '</a>',
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
